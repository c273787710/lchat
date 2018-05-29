<?php
/**
 * User.php
 * 文件描述
 * Created by PhpStorm.
 * Create On  2018/5/2311:20
 * Create by cyj
 */

namespace app\admin\controller;


use app\admin\common\controller\Base;

class User extends Base
{
    //用户列表
    public function index()
    {

        $where = [];
        $userName = input('param.user_name');
        if(!empty($userName)){
            $where['user_name'] = htmlspecialchars($userName);
        }
        $model = new \app\common\model\User();
        $list = $model->where($where)->paginate(10);
        $this->assign([
            'list' => $list,
            'uname' => empty($userName) ? '' : $userName,
            'total' => $list->total(),
            'sex' => ['1' => '男', '-1' => '女']
        ]);
        return $this->fetch();
    }
}