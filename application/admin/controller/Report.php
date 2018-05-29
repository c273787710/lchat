<?php
/**
 * Report.php
 * 文件描述
 * Created by PhpStorm.
 * Create On  2018/5/2311:35
 * Create by cyj
 */

namespace app\admin\controller;


use app\admin\common\controller\Base;

class Report extends Base
{
    //举报信息
    public function index()
    {
        $where = [];
        $userName = input('param.user_name');
        if(!empty($userName)){
            $where['report_user'] = htmlspecialchars($userName);
        }
        $model = new \app\common\model\Report();
        $list = $model->field('id,report_uid,report_user,reported_uid,reported_user,report_type,report_detail')
            ->where($where)->order('addtime desc')->paginate(10);
        $this->assign([
            'list' => $list,
            'uname' => empty($userName) ? '' : $userName,
            'total' => $list->total()
        ]);

        return $this->fetch();
    }
    public function seeDetail()
    {
        $id = input('id');
        $model = new \app\common\model\Report();
        $detail = $model->field('content')->where('id', $id)->find();
        if(!empty($detail)){
            $detail = unserialize($detail['content']);
        }

        $this->assign([
            'detail' => $detail
        ]);

        return $this->fetch();
    }
}