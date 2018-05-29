<?php
namespace app\admin\validate;
use think\Validate;

/**
 * DoLogin.php
 * 登录验证
 * Created by PhpStorm.
 * Create On  2018/5/2117:04
 * Create by cyj
 */

class DoLogin extends Validate
{
    protected $rule = [
        'uname|登录名'  =>  'require|min:5' ,
        'pwd|登录密码'  =>  'require|min:5' ,
        'captcha|验证码'=>  'number|length:6' ,
    ];
    protected $message = [
        'uname.require'  =>  '用户名不能为空' ,
        'uname.min'      =>   '用户名错误' ,
        'pwd.require'    =>   '密码不能为空' ,
        'pwd.min'        =>   '密码错误' ,
        'captcha.number' =>   '验证码错误' ,
        'captcha.length' =>   '验证码长度错误',
    ];
}