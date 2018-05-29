<?php
/**
 * Tools.php
 * 文件描述
 * Created by PhpStorm.
 * Create On  2018/5/2614:39
 * Create by cyj
 */

namespace app\index\controller;


use app\index\common\controller\Base;

class Tools extends Base
{
    //离开群组
    public function leaveGroup()
    {
        if(request()->isAjax()){

            $delStr = 'layim-group';
            $groupId = input('group_id');
            $groupId = substr($groupId, strlen($delStr), strlen($groupId));

            $me = $this->user['id'];
            //检测是不是管理员要退出该群组
            $group = db('group')->field('owner_id')->where('id', $groupId)->find();
            if($me == $group['owner_id']){
                return json(['code' => -2, 'data' => '', 'msg' => '管理员不可以直接退出']);
            }

            $flag = db('groupdetail')->where('user_id=' . $me . ' and group_id=' . $groupId)->delete();
            if(empty($flag)){
                return json(['code' => -1, 'data' => '', 'msg' => '系统错误']);
            }

            $return = [
                'uid' => $me,
                'group_id' => $groupId
            ];
            return json(['code' => 1, 'data' => $return, 'msg' => '退出成功']);
        }
        $this->error('非法访问');
    }

    //加入黑名单
    public function joinBlack()
    {
        if(request()->isAjax()){

            $delStr = 'layim-friend';
            $friendId = input('fid');
            $friendId = substr($friendId, strlen($delStr), strlen($friendId));

            $param = [
                'user_id' => $this->user['id'],
                'put_uid' => $friendId,
                'addtime' => time()
            ];

            $flag = db('blacktab')->insert($param);
            if(empty($flag)){
                return json(['code' => -1, 'data' => '', 'msg' => '加入黑名单失败']);
            }

            $mine = $this->user['id'];
            //将其从好友中删除
            $flag = db('friends')->where('user_id = ' . $mine . ' and friend_id = ' . $friendId)->delete();

            if(empty($flag)){
                return json(['code' => -2, 'data' => '', 'msg' => '加入黑名单失败']);
            }
            //并从被拉黑的好友中，将我删除
            $flag = db('friends')->where('user_id = ' . $friendId . ' and friend_id = ' . $mine)->delete();

            if(empty($flag)){
                return json(['code' => -3, 'data' => '', 'msg' => '加入黑名单失败']);
            }

            $return = [
                'to_id' => $friendId,
                'del_id' => $mine
            ];
            return json(['code' => 1, 'data' => $return, 'msg' => '加入成功']);
        }
        $this->error('非法请求');
    }
    //获取要分组的用户信息
    public function getNowUser()
    {
        if(request()->isAjax()){

            $delStr = 'layim-friend';
            $friendId = input('fid');
            $friendId = substr($friendId, strlen($delStr), strlen($friendId));

            //获取该用户的信息
            $userInfo = db('user')->field('id,user_name,avatar,sign')->where('id', $friendId)->find();
            if(empty($userInfo)){
                return json(['code' => -1, 'data' => '', 'msg' => '不存在该好友']);
            }

            return json(['code' => 1, 'data' => $userInfo, 'msg' => 'suceess']);

        }
        $this->error('非法请求');
    }
    //移动分组
    public function changeGroup()
    {
        if(request()->isAjax()){

            //更新用户的分组信息
            $groupId = input('post.group_id');
            $userId = input('post.user_id');
            $mine = $this->user['id'];

            $flag = db('friends')->where('user_id = ' . $mine . ' and friend_id = ' . $userId)
                ->setField('group_id', $groupId);

            if(false === $flag){
                return json(['code' => -1, 'data' => '', 'msg' => '移动分组失败']);
            }

            if(empty($flag)){
                return json(['code' => -2, 'data' => '', 'msg' => '该用户已经在这个分组了']);
            }

            return json(['code' => 1, 'data' => $userId, 'msg' => '移动成功']);
        }
        $this->error('非法访问');
    }
    //移除好友
    public function removeFriend()
    {
        if(request()->isAjax()){
            //删除好友
            $userId = input('post.user_id');
            $delStr = 'layim-friend';
            $userId = substr($userId, strlen($delStr), strlen($userId));

            $mine = $this->user['id'];

            //先将该用户从操作者的好友中删除
            $flag = db('friends')->where('user_id=' . $mine . ' and friend_id=' . $userId)->delete();
            if(empty($flag)){
                return json(['code' => -1, 'data' => '', 'msg' => '删除失败']);
            }
            //再从该用户中删除操作者
            $flag = db('friends')->where('user_id=' . $userId . ' and friend_id=' . $mine)->delete();
            if(empty($flag)){
                return json(['code' => -2, 'data' => '', 'msg' => '删除失败']);
            }

            $return = [
                'to_id' => $userId,
                'del_id' => $mine
            ];
            return json(['code' => 1, 'data' => $return, 'msg' => '删除成功']);
        }
        $this->error('非法访问');
    }
    //举报好友
    public function reportFriend()
    {
        $userId = input('user_id');
        $delStr = 'layim-friend';
        $userId = substr($userId, strlen($delStr), strlen($userId));

        $user = db('user')->field('id,user_name')->where('id', $userId)->find();
        $reportType = config('report_type');
        $reportDetail = config('report_detail');

        $this->assign([
            'user' => $user,
            'retype' => $reportType,
            'redetail' => $reportDetail
        ]);

        return $this->fetch();
    }
    //处理举报好友
    public function doReport()
    {
        if(request()->isAjax()){

            $param = input('post.');
            $param['report_uid'] = $this->user['id'];
            $param['report_user'] = $this->user['user_name'];
            $param['addtime'] = time();

            //查询与这个用户的5条聊天记录作为证据
            $log = db('chatlog')->field('content,timeline')
                ->where('from_id=' . $param['reported_uid'] . ' and to_id=' . $param['report_uid'])
                ->limit(5)->order('timeline desc')->select();

            $param['content'] = serialize($log);

            $flag = db('report')->insert($param);
            if(empty($flag)){
                return json(['code' => -1, 'data' => '', 'msg' => '举报失败']);
            }

            return json(['code' => 1, 'data' => '', 'msg' => '举报成功，耐心等待处理消息。']);
        }
        $this->error('非法访问');
    }
}