<?php
/**
 * Chatlog.php
 * 文件描述
 * Created by PhpStorm.
 * Create On  2018/5/2615:16
 * Create by cyj
 */

namespace app\index\controller;


use app\index\common\controller\Base;

class Chatlog extends Base
{
    //聊天记录
    public function index()
    {
        $this->assign([
            'perPage' => config('log_page')
        ]);
        return $this->fetch();
    }
    //聊天记录详情
    public function detail()
    {
        if(request()->isAjax()) {

            $perPage = config('log_page');
            $id = input('id');
            $type = input('type');
            $flag = input('flag');  //此处为标识是否获取总数

            $uid = $this->user['id'];

            $field = 'from_name username,from_id id,from_avatar avatar,timeline timestamp,content';
            if('friend' == $type) {

                $where = "((from_id={$uid} and to_id={$id}) or (from_id={$id} and to_id={$uid})) and type='friend'";

                if(!empty($flag)){
                    $result = db('chatlog')->field('id')->where($where)->count();
                }else{
                    $result = db('chatlog')->field($field)
                        ->where($where)->order('timeline desc')->paginate($perPage);
                }

                if(empty($result)) {
                    return json(['code' => -1, 'data' => '', 'msg' => '没有记录']);
                }

                return json(['code' => 1, 'data' => $result, 'msg' => 'success']);

            } else if('group' == $type) {

                if(!empty($flag)){
                    $result = db('chatlog')->field('id')->where("to_id={$id} and type='group'")->count();
                }else{
                    $result = db('chatlog')->field($field)->where("to_id={$id} and type='group'")->order('timeline desc')
                        ->paginate($perPage);
                }

                if(empty($result)) {
                    return json(['code' => -1, 'data' => '', 'msg' => '没有记录']);
                }

                return json(['code' => 1, 'data' => $result, 'msg' => 'success']);
            }
        }
        $this->error('非法访问');
    }
}