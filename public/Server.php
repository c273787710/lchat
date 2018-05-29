<?php
/**
 * Server.php
 * 服务器文件
 * Created by PhpStorm.
 * Create On  2018/5/2314:15
 * Create by cyj
 */
class Server{
    public $server;
    private $redis = null ;
    public function __construct()
    {
        $this->server = new swoole_websocket_server('0.0.0.0',8282);
        $this->server->set(array(
            'worker_num'  => 100 ,
            'task_worker_num' => 10 ,
        ));
        $this->server->on('task',[$this,'onTask']);
        $this->server->on('open',[$this,'onOpen']);
        $this->server->on('message',[$this,'onMessage']);
        $this->server->on('close',[$this,'onClose']);
        $this->server->on('finish',[$this,'onFinish']);
        $this->server->start();
    }

    //开始task任务
    function onTask($server, $task_id, $from_id, $data){
       $data = json_decode($data,true);
       static $pdo = null ;
       if($pdo == null){
           $dsn = "mysql:host=localhost;dbname=lchat";
           $user = "root";
           $passwd = "root";
           try{
               $pdo = @new PDO($dsn,$user,$passwd);
           }catch (PDOException $e){
               $message = [
                   'type' => 'error' ,
                   'msg'  => 'ER:'.$e->getMessage() ,
                   'fd'   => $data['fd'] ,
               ];
               $server->finish(json_encode($message));
           }
       }
        if($this->redis == null){
            $this->redis = new Redis();
            $this->redis->connect('127.0.0.1',6379);
        }
        switch ($data['type']){
            case "bind" :
                $status = 1;
                $stmt = $pdo->prepare("UPDATE `im_user` SET `fd`=:fd,`status`=:status,`update_time`=:times WHERE `id`=:uid ;");
                $stmt->bindParam(':fd',intval($data['fd']));
                $stmt->bindParam(':uid',intval($data['uid']));
                $stmt->bindParam(':status',$status);
                $stmt->bindParam(':times',time());
                $res = $stmt->execute();
                if(!$res){
                    $message = [
                        'type' => 'error' ,
                        'msg'  => 'uid:['.$data['uid'].'],绑定fd:['.$data['fd'].']错误' ,
                        'fd'   => $data['fd'] ,
                    ];
                    $server->finish(json_encode($message));
                }else{
                    //讲数据写入redis中，键：uid,值：fd
                    $this->redis->set("uid".$data['uid'],$data['fd']);
                    $this->redis->set("fd".$data['fd'],$data['uid']);
                    //通知所有好友已经上线了
                    $sql = "SELECT `user_id` from `im_friends` WHERE `friend_id`= :uid ;";
                    $stmt = $pdo->prepare($sql);
                    $stmt->bindParam(':uid',$data['uid']) ;
                    $stmt->execute();
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                        $f_id = $row['user_id'] ;   //好友ID
                        //判断好友是否在线
                        $f_fd = $this->redis->get("uid".$f_id);
                        if($f_fd){
                            //在线，通知用户
                            $online_message = [
                                'type' => 'online',
                                'id' => $data['uid'],
                            ];
                            $this->server->push($f_fd,json_encode($online_message));
                        }
                    }
                }
                //检测是否含有离线数据
                $sql = "SELECT `id`,`from_id`,`from_name`,`from_avatar`,`timeline`,`content` FROM `im_chatlog` WHERE `to_id`=:toid AND timeline > :times AND `type`='friend' AND `need_send`=1;";
                $stmt = $pdo->prepare($sql);
                $times = time()-7*3600*24 ;
                $stmt->bindParam(':toid',$data['uid']);
                $stmt->bindParam(':times',$times);
                $stmt->execute();
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                    $log_message = [
                        'type' => 'logMessage',
                        'data' => [
                            'username' => $row['from_name'],
                            'avatar' => $row['from_avatar'],
                            'id' => $row['from_id'],
                            'type' => 'friend',
                            'content' => htmlspecialchars($row['content']),
                            'timestamp' => $row['timeline'] * 1000,
                        ]
                    ];
                    $this->server->push($data['fd'],json_encode($log_message));
                    $update_sql = "UPDATE `im_chatlog` SET `need_send`=0 WHERE `id`= :id";
                    $stmts = $pdo->prepare($update_sql);
                    $stmts->bindParam(':id',$row['id']);
                    $stmts->execute();
                }
                break;
            case "close" :   //退出登录
                $fd = $data['fd'] ;   //fd的值
                $uid = $this->redis->get('fd'.$fd);  //用户ID
                $stmt = $pdo->prepare("UPDATE `im_user` SET `fd`=-1,`status`=0,`update_time`=:times WHERE `id`=:uid ;");
                $stmt->bindParam(':times',time());
                $stmt->bindParam(':uid',$uid);
                $stmt->execute();
                $this->redis->delete("fd".$fd);
                $this->redis->delete("uid".$uid);
                //通知已经下线
                $sql = "SELECT `user_id` from `im_friends` WHERE `friend_id`= :uid ;";
                $stmts = $pdo->prepare($sql);
                $stmts->bindParam(':uid',$uid) ;
                $stmts->execute();
                while ($row = $stmts->fetch(PDO::FETCH_ASSOC)){
                    $f_id = $row['user_id'] ;   //好友ID
                    $f_fd = $this->redis->get("uid".$f_id);
                    $offline_message = [
                        'type' => 'offline',
                        'id' => $uid,
                    ];
                    if($f_fd){
                        $this->server->push($f_fd,json_encode($offline_message));
                    }
                }
                break;
            case "online" :
                //切换在线和隐身状态
                if($data['status'] == 'hide'){
                    $status = 0 ;
                    $type = "offline";
                }else{
                    $status = 1 ;
                    $type = "online" ;
                }
                //用户选择在线
                $sql = "UPDATE `im_user` SET `status`=:status,`time`=:times WHERE `id`=:uid";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':uid',$data['uid']) ;
                $stmt->bindParam(':times',time()) ;
                $stmt->bindParam(':status',$status) ;
                $stmt->execute();
                //通知他的好友
                $sql = "SELECT `user_id` from `im_friends` WHERE `friend_id`= :uid ;";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':uid',$data['uid']) ;
                $stmt->execute();
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                    $f_id = $row['user_id'] ;   //好友ID
                    //判断好友是否在线
                    $f_fd = $this->redis->get("uid".$f_id);
                    if($f_fd){
                        $offline_message = [
                            'type' => $type,
                            'id' => $data['uid'],
                        ];
                        $this->server->push($f_fd,json_encode($offline_message));
                    }
                }
                break;
            case "addFriend" :
                $uid = $data['uid'] ;
                $fd = $this->redis->get('uid'.$uid);
                $message = [
                    'type' => 'addFriend' ,
                    'data' => [
                        'username'  =>  $data['username'] ,
                        'avatar' => $data['avatar'],
                        'id' => $data['id'],
                        'type' => 'friend',
                        'sign' => $data['sign'],
                        'groupid' => $data['groupid'],
                    ],
                ];
                if($fd){
                    $this->server->push($fd,json_encode($message));
                }
                break;
            case "addGroup":
                $fd = $this->redis->get('uid'.$data['id']);
                $message = [
                    'type' => 'addGroup' ,
                    'data' => $data['data'] ,
                ];
                $this->server->push($fd,json_encode($message));
                break;
            case "chatMessage" :
                //好友聊天
                $from = $data['from'] ;  //来自谁的消息
                $to   = $data['to'] ;    //发送给谁的，群组/个人
                $need_send = 0 ;
                $to_message = [
                    'type' => 'chatMessage' ,
                    'data' => [
                        'username' => $from['username'] ,
                        'avatar'   => $from['avatar'] ,
                        'id'       => $to['type'] == 'friend' ? $from['id'] : $to['id'] ,
                        'content'  => htmlspecialchars($from['content']) ,
                        'timestamp'=> time()*1000 ,
                        'type'  => $to['type'] ,
                    ]
                ];
                switch ($to['type']){
                    case  "friend" :    //私聊
                        $to_fd = $this->redis->get('uid'.$to['id']);
                        if($to_fd){
                            $this->server->push($to_fd,json_encode($to_message));
                        }else{
                            $need_send = 1 ;
                        }
                        break;
                    case "group" :  //群聊
                        $group_id = $to['id'] ;  //群ID
                        $sql_group = "SELECT `user_id` FROM `im_groupdetail` WHERE `group_id`=? AND `user_id`!=?";
                        $stmt_group = $pdo->prepare($sql_group);
                        $stmt_group->bindParam(1,$group_id);
                        $stmt_group->bindParam(2,$from['id']);
                        $stmt_group->execute();
                        while ($row = $stmt_group->fetch(PDO::FETCH_ASSOC)){
                            $fd = $this->redis->get('uid'.$row['user_id']);
                            if($fd){
                                $this->server->push($fd,json_encode($to_message));
                            }
                        }
                        break;
                }
                $sql = "INSERT INTO `im_chatlog` (`from_id`,`from_name`,`from_avatar`,`to_id`,`content`,`timeline`,`type`,`need_send`) VALUES (?,?,?,?,?,?,?,?)";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(1,$from['id']);
                $stmt->bindParam(2,$from['username']);
                $stmt->bindParam(3,$from['avatar']);
                $stmt->bindParam(4,$to['id']);
                $stmt->bindParam(5,htmlspecialchars($from['content']));
                $stmt->bindParam(6,time());
                $stmt->bindParam(7,$to['type']);
                $stmt->bindParam(8,$need_send);
                $stmt->execute();
                break;
            case "applyGroup" :
                //申请加入群聊 $data
                $to_fd = $this->redis->get('uid'.$data['data']['to_id']);
                $apply_data = [
                    'type'  => 'applyGroup' ,
                    'data'  => [
                        'uid'  => $data['data']['to_id'] ,
                        'groupname' => $data['data']['groupname'],
                        'groupid'   => $data['data']['groupid'] ,
                        'groupavatar' => $data['data']['groupavatar'] ,
                        'joinid'  => $data['data']['from_id'] ,
                        'joinname' => $data['data']['from_name'] ,
                        'joinsign' => $data['data']['from_sign'] ,
                        'joinavatar' => $data['data']['from_avatar'] ,
                        'remark' => $data['data']['remark'] ,
                    ],
                ];
                if($to_fd){
                    $this->server->push($to_fd,json_encode($apply_data));
                }else{
                    //管理员不在事返回信息给用户
                    $from_id = $data['data']['from_id'] ;
                    $from_fd = $this->redis->get("uid".$from_id);
                    if($from_fd){
                        $message_data = [
                            'type' => 'NotLine' ,
                            'message' => '管理员不在线，下次再加入群聊'
                        ];
                        $this->server->push($from_fd,json_encode($message_data));
                    }
                }
                break;
            case "joinGroup" :
                //同意加入群聊
                $id = $data['data']['join_id'] ;
                $fd = $this->redis->get('uid'.$id) ;
                $message_data = [
                    'type' => "addGroup" ,
                    'data' => [
                        'type' => 'group' ,
                        'avatar' => $data['data']['group_avatar'] ,
                        'id'     => $data['data']['group_id'] ,
                        'groupname' => $data['data']['group_name']
                    ],
                ];
                if($fd){
                    $this->server->push($fd,json_encode($message_data));
                }
                break;
            case "removeMember" :
                $uid = $data['remove_id'] ;
                $fd = $this->redis->get("uid".$uid);
                $message_data = [
                    'type' => 'delGroup' ,
                    'data' => $data['data']
                ];
                if($fd){
                    $this->server->push($fd,json_encode($message_data));
                }
                break;
            case "breakUp":
                $uids = explode(',',$data['uids']);
                $group = $data['group_id'] ;
                foreach ($uids as $v){
                    $fd = $this->redis->get("uid".$v);
                    if($fd){
                        $message_data = [
                            'type' => 'delGroup' ,
                            'data' => [
                                'type' => 'group' ,
                                'id'   => $group ,
                            ],
                        ];
                        $this->server->push($fd,json_encode($message_data));
                    }
                }
                break;
            case "black" :
                $to_id = $data['to_id'] ;
                $del_id = $data['del_id'] ;
                $fd = $this->redis->get("uid".$to_id);
                $to_message = [
                    'type' => 'black' ,
                    'data' => [
                        'id'  => $del_id ,
                    ],
                ];
                if($fd){
                    $this->server->push($fd,json_encode($to_message));
                }
                break;
            case "delFriend" :
                $to_id = $data['to_id'] ;
                $fd = $this->redis->get("uid".$to_id);
                $to_message = [
                    'type' => "delFriend" ,
                    'data' => [
                        'id' => $data['del_id'],
                    ],
                ];
                if($fd){
                    $this->server->push($fd,json_encode($to_message));
                }
                break;
        }
    }

    //接收到数据
    function onMessage($server,$frame){
        $message = json_decode($frame->data,true);  //用户发送的消息
        $fd = $frame->fd ;       //用户fd ,
        $uid = $message['id'] ;   //用户id ,
        switch ($message['type']){
            case "init" :
                //登录事件
                $arr = [
                    'uid'  => $uid ,
                    'fd'   => $fd ,
                    'type' => 'bind' ,
                ];
                $this->server->task(json_encode($arr));
                break;
            case "online" :
                //切换成隐身和在线状态
                $arr = [
                    'uid' => $uid ,
                    'status' => $message['status'] ,
                    'fd' => $fd ,
                    'type' => 'online' ,
                ];
                $this->server->task(json_encode($arr));
                break;
            case "addFriend" :
                //同意添加好友
                $arr = [
                    'uid'  => $message['toid'] ,
                    'type' => 'addFriend',
                    'avatar' => $message['avatar'],
                    'id' => $message['id'],
                    'sign' => $message['sign'],
                    'groupid' => $message['groupid'],
                    'username'=>$message['username'] ,
                ];
                $this->server->task(json_encode($arr));
                break;
            case "chatMessage" :
                $arr = [
                    'type' => "chatMessage" ,
                    "from" => $message['data']['mine'] ,
                    "to"   => $message['data']['to'] ,
                ];
                $this->server->task(json_encode($arr));
                break;
            case "addGroup" :
                //创建群组
                $add_message = [
                    'type'  => 'addGroup' ,
                    'data' => [
                        'type' => 'group',
                        'avatar'   => $message['avatar'],
                        'id'       => $message['id'],
                        'groupname'     => $message['groupname']
                    ] ,
                    'id' => $message['join_id'] ,
                ];
                $this->server->task(json_encode($add_message));
                break;
            case "applyGroup" :
                //申请加入群聊
                $data = [
                    'type' => 'applyGroup' ,
                    'data' => [
                        'to_id'  => $message['to_id'] ,
                        'from_id' => $message['join_id'] ,
                        'groupname' => $message['groupname'] ,
                        'groupid'   => $message['groupid'] ,
                        'from_name' => $message['join_name'] ,
                        'remark' => $message['remark'] ,
                        'groupavatar' => $message['groupavatar'] ,
                        'from_sign'  => $message['join_sign'] ,
                        'from_avatar' => $message['join_avatar'] ,
                    ],
                ];
                $this->server->task(json_encode($data));
                break;
            case "joinGroup" :
                //同意获取拒接
                $data = [
                    'type'  => 'joinGroup' ,
                    'data'  => [
                        'join_id'  => $message['join_id'] ,
                        'group_id' => $message['group_id'] ,
                        'group_avatar' => $message['group_avatar'] ,
                        'group_name'  => $message['group_name'] ,
                    ],
                ];
                $this->server->task(json_encode($data));
                break;
            case "removeMember" :
                $data = [
                    'type' => 'removeMember' ,
                    'remove_id' => $message['remove_id'] ,
                    'data' => [
                        'type' => 'group' ,
                        'id'   => $message['group_id'] ,
                    ],
                ];
                $this->server->task(json_encode($data));
                break;
            case "breakUp" :
                //删除群组
                //var_dump($message);
                $this->server->task(json_encode($message));
                break;
            case "black":
                $this->server->task(json_encode($message));
                break;
            case "delFriend" :
                $this->server->task(json_encode($message));
                break;
        }
    }
    //关闭连接
    function onClose($ser, $fd){
        $arr = [
            'fd' => $fd ,
            'type' => 'close' ,
        ];
        $this->server->task(json_encode($arr));
    }
    //task完成
    function onFinish($server, $task_id, $data){
        $msg = json_decode($data,true);
        $response = array();
        switch ($msg['type']){
            case 'error' :
                //task执行出现错误
                //错误记录日志
                $file = "/www/log/".date('YmdH').".txt";
                $log = @fopen($file,'a');
                @fwrite($log,'['.$msg['type'].']'.$msg['msg']."\n");
                @fclose($log);
                $response['type'] = 'err' ;
                $response['msg']  = '操作出现错误' ;
                break;
        }
        $this->server->push($msg['fd'],json_encode($response));
    }

    //握手成功
    function onOpen(swoole_websocket_server $server, swoole_http_request $req){}
}
new Server();