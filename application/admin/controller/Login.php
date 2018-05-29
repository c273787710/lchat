<?php
/**
 * Login.php
 * 文件描述
 * Created by PhpStorm.
 * Create On  2018/5/2116:32
 * Create by cyj
 */

namespace app\admin\controller;


use app\admin\common\controller\Base;
use \app\common\model\Admin;
use think\captcha\Captcha;
use think\helper\Hash;
use think\Session;

class Login extends Base
{
    public function _initialize(){}
    public function index(){
        $config = config('login_config');
        $this->assign('is_captcha',$config['captcha']);
        return $this->fetch();
    }
    public function dologin(){
        if(request()->isAjax()){
            $param = input('post.');
            $result = $this->validate($param,'DoLogin');
            if(true !== $result){
                $this->error($result);
            }
            $captcha = new Captcha();
            if(!$captcha->check($param['captcha'])){
                $this->error('验证码错误');
            }
            $model = new Admin();
            $res = $model->checkLogin($param['uname'],$param['pwd']);
            if(!$res){
                $this->error($model->error);
            }
            $this->success('登录成功',url('admin/index/index'));
        }
    }
    public function captcha(){
        $config = [
            'length' =>  6 ,
            'useNoise' => true ,
            'codeSet'  => '1234567890' ,
            'useCurve' => true ,
            'imageH'   => 60 ,
            'imageW'   => 250 ,
            'fontttf'  => '2.ttf' ,
        ];
        $captcha = new Captcha($config);
        return $captcha->entry();
    }
    public function loginout(){
        Session::delete('user_auth');
        Session::delete('user_auth_sign');
        $this->redirect('admin/login/index');
    }
}