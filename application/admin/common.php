<?php
/**
 * common.php
 * 后台公共函数文件
 * Created by PhpStorm.
 * Create On  2018/5/2115:27
 * Create by cyj
 */

/**
 * 写基础配置文件
 * @param $data
 */
function writeConfig($data)
{
    $path = APP_ROOT . '/config/group.conf';
    @file_put_contents($path, serialize($data));
    return true;
}



