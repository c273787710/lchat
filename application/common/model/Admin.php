<?php
namespace app\common\model;
use think\helper\Hash;
use think\Model;

/**
 * Admin.php
 * 后台管理员操作模型
 * Created by PhpStorm.
 * Create On  2018/5/2117:14
 * Create by cyj
 */

class Admin extends Model
{
    protected $table = "__ADMIN__";
    protected $errMsg = null ;
    public $error = null;

    public function checkLogin($username = '',$password = ''){
        $map = array();
        if(preg_match('/^1[3456789]\d{9}$/',$username)){
            //手机号码登录
            $map['mobile'] = $username ;
        }elseif (preg_match('/^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/',$username)){
            //邮箱登录
            $map['email'] = $username ;
        }else{
            //用户名登录
            $map['login_name'] = $username ;
        }
        $user = $this::get($map);
        if(!$user){
            $this->error = "用户不存在";
            return false;
        }
        //验证密码
        $res = Hash::check($password,$user['password']);
        if(!$res){
            $this->error = "密码错误";
            return false;
        }
        $user->last_login_time = time() ;
        $user->last_login_ip   = request()->ip();
        $user->save();
        session('user_auth',$user);
        session('user_auth_sign',dataAuthSign($user));
        return true;
    }
}