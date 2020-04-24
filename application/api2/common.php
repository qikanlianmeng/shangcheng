<?php
use think\Db;

/**
 * 记录前台用户信息日志
 * @param  [type] $uid         [用户id]
 * @param  [type] $description [描述]
 * @param  [type] $before      修改前信息
 * @param [type] $after       修改后信息
 */
function writelogfront($uid,$description,$before='',$after='')
{

    $data['uid'] = $uid;
    $data['description'] = $description;
    $data['before'] = $before;
    $data['after'] = $after;
    $data['ip'] = request()->ip();
    $data['add_time'] = time();
    $log = Db::name('Log_front')->insert($data);

}

