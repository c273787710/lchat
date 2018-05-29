<?php
/**
 * RegisterValidate.php
 * 文件描述
 * Created by PhpStorm.
 * Create On  2018/5/2311:54
 * Create by cyj
 */
namespace app\index\validate;
use think\Validate;

class RegisterValidate extends Validate
{
    protected $rule = [
        'user_name|登录名'  =>  'require|min:5' ,
        'pwd|密码'          =>  'require|min:6|max:16' ,
        'repwd|重复密码'    =>  'require|min:6|max:16' ,
        'sex|性别'          =>  'require|in:0,1' ,
        'age|年龄'          =>  'require|integer' ,
    ];
}