<?php
/**
 * Group.php
 * 群组
 * Created by PhpStorm.
 * Create On  2018/5/239:49
 * Create by cyj
 */

namespace app\admin\controller;

use app\admin\common\controller\Base;

class Group extends Base
{
    public function editGroup(){
        if(request()->isPost()){

            $data = input('post.');
            writeConfig($data);
            return json(['code' => 1, 'data' => '', 'msg' => '配置成功']);
        }
        $config = readConfig();
        empty($config) && $config = ['make' => 1, 'maxgroup' => 10, 'maxjoin' => 20, 'pass' => -1];
        $this->assign([
            'config' => $config
        ]);
        return $this->fetch();
    }
    //群组列表
    public function index()
    {
        $groupName = input('param.group_name');
        $where = [];
        if(!empty($groupName)){
            $where['group_name'] = htmlspecialchars($groupName);
        }
        $model = new \app\common\model\Group();
        $list = $model->where($where)->order('addtime desc')->paginate(10);
        $this->assign([
            'list' => $list,
            'gname' => empty($groupName) ? '' : $groupName,
            'total' => $list->total(),
            'status' => config('group_status')
        ]);

        return $this->fetch();
    }
    //审核通过
    public function pass(){
        if(request()->isAjax()){

            $id = input('post.id');
            $status = input('post.status');
            $flag = db('group')->where('id', $id)->setField('status', $status);

            if(false === $flag){
                return json(['code' => -1, 'data' => '', 'msg' => '审核失败']);
            }

            return json(['code' => 1, 'data' => '', 'msg' => '审核成功']);
        }
    }
}