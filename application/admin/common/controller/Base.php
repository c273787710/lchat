<?php
namespace app\admin\common\controller;
use think\Controller;
use think\Session;

/**
 * Base.php
 * 后台基本类
 * Created by PhpStorm.
 * Create On  2018/5/2116:31
 * Create by cyj
 */

class Base extends Controller
{
    public $user_info = null ;
    public function _initialize()
    {
        if(!Session::has('user_auth') || !Session::has('user_auth_sign')){
            $this->redirect(url('admin/login/index'));
        }
        $user = Session::get('user_auth');
        $user_sign = Session::get('user_auth_sign');
        $_sign = dataAuthSign($user);
        if($_sign != $user_sign){
            Session::delete('user_auth');
            Session::delete('user_auth_sign');
            $this->redirect('admin/login/index');
        }
        $this->user_info = $user ;

    }
}