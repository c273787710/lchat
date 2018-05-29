<?php
namespace app\index\controller;

use app\index\common\controller\Base;

class Index extends Base
{
    public function index()
    {
        $this->assign('uinfo',$this->user);
        return $this->fetch();
    }
    public function getList(){
        $uid = $this->user['id'] ;
        $mine = db('user')->where('id', $uid)->find();
        //查询当前用户的所处的群组
        $groupArr = db('groupdetail')->alias('j')->field('c.group_name groupname,c.id,c.avatar')
            ->join('im_group c', 'j.group_id = c.id')->where('j.user_id', $uid)
            ->group('j.group_id')->select();
        $online = 0;
        $group = [];  //记录分组信息
        $userGroup = config('user_group');
        $list = [];  //群组成员信息
        $i = 0;
        $j = 0;
        //查询该用户的好友
        $friends = db('friends')->alias('f')->field('c.user_name,c.id,c.avatar,c.sign,c.status,f.group_id')
            ->join('im_user c', 'c.id = f.friend_id')
            ->where('f.user_id', $uid)->select();
        foreach( $userGroup as $key=>$vo ){
            $group[$i] = [
                'groupname' => $vo,
                'id' => $key,
                'online' => 0,
                'list' => []
            ];
            $i++;
        }
        unset( $userGroup );
        foreach( $group as $key=>$vo ){

            foreach( $friends as $k=>$v ) {

                if ($vo['id'] == $v['group_id']) {

                    $list[$j]['username'] = $v['user_name'];
                    $list[$j]['id'] = $v['id'];
                    $list[$j]['avatar'] = $v['avatar'] ? $v['avatar'] : 'http://yg.ch3ney.com/static/common/images/common.jpg';
                    $list[$j]['sign'] = $v['sign'];
                    $list[$j]['status'] = $v['status'] ? 'online' : 'offline';

                    if (1 == $v['status']) {
                        $online++;
                    }

                    $group[$key]['online'] = $online;
                    $group[$key]['list'] = $list;

                    $j++;
                }
            }
            $j = 0;
            $online = 0;
            unset($list);
        }
        unset( $friends );
        $return = [
            'code' => 0,
            'msg'=> '',
            'data' => [
                'mine' => [
                    'username' => $mine['user_name'],
                    'id' => $mine['id'],
                    'status' => 'online',
                    'sign' => $mine['sign'],
                    'avatar' => $mine['avatar']
                ],
                'friend' => $group,
                'group' => $groupArr
            ],
        ];

        return json( $return );
    }
    //获取组员信息
    public function getMembers()
    {
        $id = input('param.id',0,'intval');
        //群主信息
        $owner = db('group')->field('owner_name,owner_id,owner_avatar,owner_sign')->where('id',$id)->find();
        //群成员信息
        $list = db('groupdetail')->field('user_id id,user_name username,user_avatar avatar,user_sign sign')
            ->where('group_id = ' . $id)->select();

        $return = [
            'code' => 0,
            'msg' => '',
            'data' => [
                'owner' => [
                    'username' => $owner['owner_name'],
                    'id' => $owner['owner_id'],
                    'owner_id' => $owner['owner_avatar'],
                    'sign' => $owner['owner_sign']
                ],
                'list' => $list
            ]
        ];

        return json( $return );
    }
}
