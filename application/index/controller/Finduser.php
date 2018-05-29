<?php
/**
 * Finduser.php
 * 文件描述
 * Created by PhpStorm.
 * Create On  2018/5/2417:47
 * Create by cyj
 */

namespace app\index\controller;


use app\index\common\controller\Base;

class Finduser extends Base
{
    public function index(){
        if(request()->isPost()){
            //获取参数
            $uid = $this->user['id'];
            $param = input('post.');
            $where['id'] = ['NEQ',$uid];
            if(!empty($param['user_name'])){
                $where['user_name'] = ['like','%'.htmlspecialchars($param['user_name']).'%'];
            }
            if(!empty($param['sex'])){
                $where['sex'] = intval($param['sex']);
            }
            if(!empty($param['age'])){
                $config = config('age');
                $key = array_search($param['age'],$config);
                switch ($key){
                    case 1 :
                        $where['age'] = ['<=',18];
                        break;
                    case 2 :
                        $where['age'] = array('between',[18,28]);
                        break;
                    case 3 :
                        $where['age'] = array('between',[28,38]);
                        break;
                    case 4 :
                        $where['age'] = array('between',[38,48]);
                        break;
                    case 5 :
                        $where['age'] = [">=",48];
                        break;
                }
            }
            if(!empty($param['pid'])){
                $where['pid'] = $param['pid'] ;
            }
            if(!empty($param['cid'])){
                $where['cid'] = $param['cid'] ;
            }
            if(!empty($param['aid'])){
                $where['aid'] = $param['aid'] ;
            }
            $userList = db('user')->field('id,user_name,area,avatar,sex,age')->where($where)
                ->order('id desc')->select();
            return json(['code'=>1,'data'=>$userList]);
        }
    }
}