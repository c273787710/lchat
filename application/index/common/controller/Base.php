<?php
/**
 * Base.php
 * 文件描述
 * Created by PhpStorm.
 * Create On  2018/5/2313:49
 * Create by cyj
 */
namespace app\index\common\controller;
use think\Controller;
use think\Session;

class Base extends Controller
{
    public $user = null ;
    public function _initialize()
    {
        if(!Session::has('chat_user') || !Session::has('chat_user_sign')){
            $this->redirect(url('index/login/index'));
        }
        $chat_user = Session::get('chat_user');
        $chat_user_sign = Session::get('chat_user_sign');
        if($chat_user_sign != dataAuthSign($chat_user)){
            Session::delete('chat_user');
            Session::delete('chat_user_sign');
            $this->redirect(url('index/login/index'));
        }
        $this->user = $chat_user ;
    }
}