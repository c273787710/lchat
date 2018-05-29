<?php
/**
 * Msgbox.php
 * 文件描述
 * Created by PhpStorm.
 * Create On  2018/5/258:55
 * Create by cyj
 */

namespace app\index\controller;


use app\index\common\controller\Base;

class Msgbox extends Base
{
    //信息框展示页面
    public function index()
    {
        $this->assign([
            'uid' => $this->user['id'],
            'username' => $this->user['user_name'],
            'avatar' => $this->user['avatar'],
            'sign' => $this->user['sign']
        ]);
        return $this->fetch();
    }
    //申请好友
    public function applyFriend()
    {
        if(request()->isAjax()){
            $uid = $this->user['id'];
            $param = input('post.');
            //检测是否被要添加的用户加入了黑名单，若加入，则无法申请添加该好友
            $inBlack = db('blacktab')->where('put_uid=' . $uid . ' and user_id=' . intval($param['uid']))
                ->find();
            if(!empty($inBlack)){
                return json(['code' => -2, 'data' => '', 'msg' => '对方已将你加入黑名单']);
            }
            //入库系统消息
            $msg = [
                'content' => '申请添加你为好友',
                'uid' => $param['uid'],
                'from' => $uid, //发起好友申请的uid
                'remark' => $param['remark'],
                'from_group' => $param['from_group'],
                'type' => 1,
                'read' => 1,
                'time' => time()
            ];

            $flag = db('message')->insert($msg);
            if(empty($flag)){
                return json(['code' => -1, 'data' => '', 'msg' => '系统错误']);
            }

            return json(['code' => 0, 'data' => '', 'msg' => 'success']);
        }
        $this->error('非法访问');
    }
    //获取当前用户有多少个未读通知
    public function getNoRead()
    {
        if(request()->isAjax()){
            $uid = $this->user['id'] ;
            $tips = db('message')->where('`uid`=' . $uid . ' and `read`=1')->count();
            return json(['code' => 1, 'data' => $tips, 'msg' => 'success']);
        }
        $this->error('非法访问');
    }

    //获取通知消息
    public function getMsg()
    {
        if(request()->isAjax()){

            $msg = db('message')->where('uid', $this->user['id'])->order('time desc')->paginate(5);
            //拼装发送人信息
            if(empty($msg)){
                return json(['code' => 0, 'pages' => 0, 'data' => '', 'msg' => '']);
            }
            $pages = $msg->lastPage();

            $msg = objToArr($msg);
            //dump($msg);die;
            foreach($msg as $key=>$vo){
                $msg[$key]['time'] = date('Y-m-d H:i');
                $user = db('user')->field('avatar,user_name,sign')->where('id', $vo['from'])->find();
                $msg[$key]['user'] = [
                    'id' => $vo['from'],
                    'avatar' => $user['avatar'],
                    'username' => $user['user_name'],
                    'sign' => $user['sign']
                ];
            }

            return json(['code' => 0, 'pages' => $pages, 'data' => $msg, 'msg' => '']);

        }
        $this->error('非法访问');
    }
    //标记当前推送的消息为已读状态
    public function read()
    {
        if(request()->isAjax()){
            $read = input('post.read');
            db('message')->where('uid=' . $this->user['id'])->setField('read', $read);
            return true;
        }
        $this->error('非法访问');
    }
    //同意好友申请
    public function agreeFriend()
    {
        if(request()->isAjax()){
            $param = input('post.');
            $uid = $this->user['id'];
            //建立好友关系
            //1、将我与请求人建立关系
            $myFriend = [
                'user_id' => $uid,
                'friend_id' => $param['uid'],
                'group_id' => $param['group']
            ];

            $flag = db('friends')->insert($myFriend);
            if(empty($flag)){
                return json(['code' => -1, 'data' => '', 'msg' => '系统错误']);
            }
            unset($myFriend);

            //2、将请求人与我建立关系
            $yourFriend = [
                'user_id' => $param['uid'],
                'friend_id' => $uid,
                'group_id' => $param['from_group']
            ];

            $flag = db('friends')->insert($yourFriend);
            if(empty($flag)){
                return json(['code' => -2, 'data' => '', 'msg' => '系统错误']);
            }
            unset($yourFriend);

            //入库系统消息
            $msg = [
                'content' => $this->user['user_name'] . ' 已经同意你的好友申请',
                'uid' => $param['uid'],
                'from' => $this->user['id'] ,
                'from_group' => $param['from_group'],
                'type' => 2,
                'read' => 1,
                'time' => time() ,
                'agree' => 1 ,
            ];

            $flag = db('message')->insert($msg);
            if(empty($flag)){
                return json(['code' => -3, 'data' => '', 'msg' => '系统错误']);
            }

            //将此消息标记为已经同意
            $flag = db('message')->where('id', $param['id'])->setField('agree', 1);
            if(empty($flag)){
                return json(['code' => -4, 'data' => '', 'msg' => '系统错误']);
            }

            return json(['code' => 0, 'data' => '', 'msg' => 'success']);
        }
        $this->error('非法访问');
    }
    //拒绝好友申请
    public function refuseFriend()
    {
        if(request()->isAjax()){

            $param = input('post.');

            //将此消息标记为拒绝
            $flag = db('message')->where('id', $param['id'])->setField('agree', 2);
            if(empty($flag)){
                return json(['code' => -1, 'data' => '', 'msg' => '系统错误']);
            }

            //入库系统消息
            $msg = [
                'content' => session('f_user_name') . ' 拒绝了你的好友申请',
                'uid' => $param['uid'],
                'from' => $this->user['id'] ,
                'type' => 2,
                'read' => 1,
                'time' => time() ,
                'agree'=> 2 ,
            ];

            $flag = db('message')->insert($msg);
            if(empty($flag)){
                return json(['code' => -2, 'data' => '', 'msg' => '系统错误']);
            }

            return json(['code' => 0, 'data' => '', 'msg' => 'success']);
        }
        $this->error('非法访问');
    }
}