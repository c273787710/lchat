<?php
/**
 * Chatuser.php
 * 文件描述
 * Created by PhpStorm.
 * Create On  2018/5/2414:25
 * Create by cyj
 */

namespace app\index\controller;
use app\index\common\controller\Base;
use think\helper\Hash;
use think\Session;

class Chatuser extends Base
{
    public function index()
    {
        $id = $this->user['id'];
        $user = db('user')->where('id', $id)->find();
        $this->assign([
            'user' => $user
        ]);
        return $this->fetch();
    }
    //修改签名
    public function changeSign()
    {
        if(request()->isAjax()){
            $id = $this->user['id'];
            $sign = input('post.sign');
            $flag = db('user')->where('id',$id)->setField('sign', htmlspecialchars($sign));
            if(false === $flag){
                return json(['code' => -1, 'data' => '', 'msg' => '系统错误']);
            }

            //重置签名
            $user = Session::get('chat_user');
            $user['sign'] = $sign ;
            Session::set('chat_user',$user);

            return json(['code' => 1, 'data' => '', 'msg' => '修改成功']);
        }
        $this->error('非法访问');
    }
    public function dochange(){
        if (request()->isAjax()) {
            $id = $this->user['id'];
            $param = input('post.');
            //补充验证

            //修改密码
            if (!empty($param['oldpwd'])) {

                $pwd = db('user')->field('pwd')->where('id', $id)->find();
                if (Hash::check($param['oldpwd'],$pwd)) {
                    return json(['code' => -1, 'data' => '', 'msg' => '旧密码不正确！']);
                }

                if($param['pwd'] != $param['repwd']){
                    return json(['code' => -2, 'data' => '', 'msg' => '两次密码输入不一致！']);
                }

                $upData['pwd'] = Hash::make($param['pwd']);
            }
            //查询获得区域描述
            $where = 'id =' . $param['pid'];
            if(!empty($param['cid'])){
                $where .= ' or id=' . $param['cid'];
            }else{
                $param['city'] = 0;
            }

            if(!empty($param['aid'])){
                $where .= ' or id=' . $param['aid'];
            }else{
                $param['area'] = 0;
            }
            $area = db('area')->field('area_name')->where($where)->order('level asc')->select();

            $areaStr = '';
            if(!empty($area)){
                foreach($area as $key=>$vo){
                    $areaStr .= $vo['area_name'] . '-';
                }
                $areaStr = rtrim($areaStr, '-');
            }else{
                $areaStr = '中国-中国-中国';
            }
            unset($area);
            $upData['user_name'] = trim($param['user_name']);
            !empty($param['avatar']) && $upData['avatar'] = $param['avatar'];
            $upData['sex'] = $param['sex'];
            $upData['age'] = $param['age'];
            $upData['pid'] = $param['pid'];
            $upData['cid'] = $param['cid'];
            $upData['aid'] = $param['aid'];
            $upData['area'] = $areaStr;
            unset($param);
            $flag = db('user')->where('id', $id)->update($upData);
            if(false === $flag){
                return json(['code' => -3, 'data' => '', 'msg' => '系统错误']);
            }
            if(!empty($upData['avatar'])){
                //重新更新缓存
                $user = Session::get('chat_user');
                $user['avatar'] = $upData['avatar'];
                Session::set('chat_user',$user);
            }

            return json(['code' => 1, 'data' => '', 'msg' => '修改成功']);
        }
    }
    //上传个人头像
    public function upAvatar()
    {
        // 获取表单上传文件
        $file = request()->file('avatar');

        // 移动到框架应用根目录/public/uploads/ 目录下
        if(!is_null($file)){

            $fileInfo = $file->getInfo();

            $imgExt = config('common_img');
            $ext = explode('.', $fileInfo['name']);
            $ext = array_pop($ext);

            if(!in_array($ext, $imgExt)){
                return json(['code' => -2, 'data' => '', 'msg' => '请上传' . implode(',' , $imgExt) . '的图片']);
            }
            unset($ext);

            $size = config('common_size');
            if($fileInfo['size'] > $size){
                return json(['code' => -3, 'data' => '', 'msg' => '上传的图片超过' . $size/1024 . 'kb']);
            }
            unset($fileInfo);

            $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
            if($info){
                // 成功上传后 获取上传信息
                $avatar = '/uploads' . '/' . date('Ymd') . '/' . $info->getFilename();
                return json(['code' => 1, 'url' => $avatar, 'msg' => 'success']);
            }else{
                // 上传失败获取错误信息
                return json(['code' => -4, 'url' => '', 'msg' => $file->getError()]);
            }
        }

        return json(['code' => -1, 'data' => '', 'msg' => '修改头像失败']);
    }
}