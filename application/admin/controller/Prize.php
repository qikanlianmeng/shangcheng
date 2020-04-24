<?php

namespace app\admin\controller;

use think\Db;
use think\Request;

 
class Prize extends Base
{
    public function __construct(Request $request)
    {
        parent::__construct();
        header("Content-type:text/html;charset=utf-8");
        $this->request = $request;
        $this->backUrl = isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '/';
        $this->assign("backUrl", $this->backUrl);
    }
    /************************抽奖活动管理***************************/
    public function lists(){
        $subsql = Db::name('prize_log')->group('pid')->field('count(id) as total,pid')->buildSql();
        $list = Db::name('prize')->alias('p')->join([$subsql=>'pl'],'p.id=pl.pid','left')->field('p.*,total')->paginate(10);
        $this->assign('list', $list);
        // 渲染模板输出
        return $this->fetch();
    }
    public function add(){
        if ($this->request->isPost()) {
            $data = input('param.');
            $data['start_time'] = strtotime($data['start_time']);
            $data['end_time'] = strtotime($data['end_time']);
            $data['create_time'] = time();
            if(Db::name('prize')->insert($data)){
                $back = ['code' => 1, 'msg' => '添加成功'];
                writelog($this->uid,$this->username,'添加抽奖活动【'.$data['title'].'】');
            }else{
                $back = ['code' => 0, 'msg' => '添加失败'];
            }
            return json($back);
        }
        return view('');
    }
    public function edit(){
        $data = input('param.');
        if ($this->request->isPost()) {
            $data = input('param.');
            $data['start_time'] = strtotime($data['start_time']);
            $data['end_time'] = strtotime($data['end_time']);
            $old_info = Db::name('prize')->where('id',$data['id'])->find();
            if(Db::name('prize')->where('id',$data['id'])->update($data)){
                $back = ['code' => 1, 'msg' => '修改成功'];
                $new_info = Db::name('prize')->where('id',$data['id'])->find();
                $before=$after='';
                foreach($new_info as $k=>$v) {
                    if ($v != $old_info[$k]) {
                        switch ($k) {
                            case 'title':
                                $before .= '名称：'.$old_info[$k].'，';
                                $after .= '名称：'.$v.'，';
                                break;
                            case 'price':
                                $before .= '价格：'.$old_info[$k].'，';
                                $after .= '价格：'.$v.'，';
                                break;
                            case 'start_time':
                                $before .= '开始日期：'.date('Y-m-d',$old_info[$k]).'，';
                                $after .= '开始日期：'.date('Y-m-d',$v).'，';
                                break;
                            case 'end_time':
                                $before .= '结束日期：'.date('Y-m-d',$old_info[$k]).'，';
                                $after .= '结束日期：'.date('Y-m-d',$v).'，';
                                break;
                            case 'buy_num':
                                $before .= '满赠次数：'.$old_info[$k].'，';
                                $after .= '满赠次数：'.$v.'，';
                                break;
                            case 'free_num':
                                $before .= '赠送次数：'.$old_info[$k].'，';
                                $after .= '赠送次数：'.$v.'，';
                                break;
                        }
                    }
                }
                writelog($this->uid,$this->username,'修改抽奖活动【'.$data['title'].'】',1,$before,$after);
            }else{
                $back = ['code' => 0, 'msg' => '修改失败'];
            }
            return json($back);
        }
        $info = Db::name('prize')->where('id',$data['id'])->find();
        //print_r($info);exit;
        return view('',['info'=>$info]);
    }
    public function del($id){
        $title = Db::name('prize')->where('id',$id)->value('title');
        if(Db::name('prize')->where('id',$id)->delete()){
            $back = ['code' => 1, 'msg' => '删除成功'];
            writelog($this->uid,$this->username,'删除抽奖活动，【'.$title.'】');
        }else{
            $back = ['code' => 0, 'msg' => '删除失败'];
        }
        return json($back);
    }
    /************************抽奖活动商品管理***************************/
    public function goods($pid){
        $list = Db::name('prize_goods')->where('pid',$pid)->order('order asc')->paginate(10);
        $prize = Db::name('prize')->where('id',$pid)->find();
        return view('', ['list' => $list,'prize'=>$prize]);
    }
    public function add_goods(){
        $data = input('param.');
        if ($this->request->isPost()) {
            $data['create_time'] = time();
            $data['left_num'] = $data['num'];
            if(Db::name('prize_goods')->insert($data)){
                $back = ['code' => 1, 'msg' => '添加成功'];
                $prize_title =  $title = Db::name('prize')->where('id',$data['pid'])->value('title');
                writelog($this->uid,$this->username,'给抽奖活动【'.$prize_title.'】添加奖品【'.$data['name'].'】');
            }else{
                $back = ['code' => 0, 'msg' => '添加失败'];
            }
            return json($back);
        }

        return view('',['pid'=>$data['pid']]);
    }
    public function edit_goods(){
        $data = input('param.');
        $info = Db::name('prize_goods')->where('id',$data['id'])->find();
        if ($this->request->isPost()) {
            if(Db::name('prize_goods')->where('id',$data['id'])->update($data)){
                $back = ['code' => 1, 'msg' => '修改成功'];
                $prize_title =  $title = Db::name('prize')->where('id',$info['pid'])->value('title');
                $new_info = Db::name('prize_goods')->where('id',$data['id'])->find();
                $before=$after='';
                $old_info = $info;
                foreach($new_info as $k=>$v) {
                    if ($v != $old_info[$k]) {
                        switch ($k) {
                            case 'name':
                                $before .= '名称：'.$old_info[$k].'，';
                                $after .= '名称：'.$v.'，';
                                break;
                            case 'num':
                                $before .= '数量：'.$old_info[$k].'，';
                                $after .= '数量：'.$v.'，';
                                break;
                            case 'left_num':
                                $before .= '剩余数量：'.$old_info[$k].'，';
                                $after .= '剩余数量：'.$v.'，';
                                break;
                            case 'rate':
                                $before .= '中奖概率：'.$old_info[$k].'，';
                                $after .= '中奖概率：'.$v.'，';
                                break;
                            case 'order':
                                $before .= '排序：'.$old_info[$k].'，';
                                $after .= '排序：'.$v.'，';
                                break;
                        }
                    }
                }
                writelog($this->uid,$this->username,'修改抽奖活动【'.$prize_title.'】奖品【'.$data['name'].'】',1,$before,$after);
            }else{
                $back = ['code' => 0, 'msg' => '修改失败'];
            }
            return json($back);
        }

        return view('',['info'=>$info]);
    }
    public function del_goods($id){
        $info = Db::name('prize_goods')->where('id',$id)->find();
        $prize_title =  $title = Db::name('prize')->where('id',$info['pid'])->value('title');
        if(Db::name('prize_goods')->where('id',$id)->delete()){
            $back = ['code' => 1, 'msg' => '删除成功'];
            writelog($this->uid,$this->username,'删除抽奖活动【'.$prize_title.'】的奖品【'.$info['name'].'】');
        }else{
            $back = ['code' => 0, 'msg' => '删除失败'];
        }
        return json($back);
    }

    /************************抽奖活动中奖名单***************************/
    public function zj($pid){
        $where = [
            'pl.pid'   => $pid,
            'pl.status'=> ['in',[1,2]]
        ];
        $list = Db::name('prize_log')->alias('pl')
            ->field('pl.*,m.nickname,m.mobile')
            ->join('think_member m','m.id=pl.uid','left')
            ->order('pl.status asc')
            ->where($where)->order('pl.create_time desc')->paginate(10);
        $prize = Db::name('prize')->where('id',$pid)->find();
        return view('', ['list' => $list,'prize'=>$prize]);
    }
    public function set_zj(){
        $param = input('param.');
        $id_arr = $param['id_arr'];
        foreach($id_arr as $v){
            Db::name('prize_log')->where(['id'=>$v,'status'=>1])->update(['status'=>2,'send_time'=>time()]);
        }
        writelog($this->uid,$this->username,'批量设置中奖奖品为已发放');
        return json(['code' => 1, 'msg' => '操作成功']);
    }

}