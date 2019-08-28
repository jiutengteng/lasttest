<?php
/**
 * Created by PhpStorm.
 * User: 酒腾腾
 * Date: 2019/8/28
 * Time: 8:41
 */
namespace app\controllers;

use yii\web\Controller;

class AddController extends Controller
{
    /**
     * @return string
     * 渲染注册页面
     */
    public function actionReg()
    {
        return $this->render('reg');
    }
    /**
     * 接受用户注册数据
     */
    public function actionDo() {
        $data = \Yii::$app->request->post();
        $arr['title'] = $data['title'];
        $arr['username'] = $data['username'];
        $arr['phone'] = $data['phone'];
        $res = \Yii::$app->db->createCommand()->insert('last',$arr)->execute();
        if($res){
            $this->redirect(['add/index','info'=>$arr]);
        }else {
            echo '注册失败';
        }
    }
    /**
     * 注册成功，检测注册用户权限，渲染不同页面
     */
    public function actionIndex() {
        $data = \Yii::$app->request->get();
        $info = $data['info'];
        if($info['username'] == 'jtt') {
            $list = \Yii::$app->db->createCommand()->setSql('select * from last')->queryAll();
//            print_r($list);die;
          return $this->render('list',['list'=>$list]);
        }else {
//            print_r($info);die;
          return $this->render('main',['info'=>$info]);
        }
    }
    /**
     * 登录页面
     */
    public function actionLogin() {
        return $this->render('login');
    }
    public function actionDoing() {
        $data = \Yii::$app->request->post();
        $username = $data['username'];
        $phone = $data['phone'];
        $res = \Yii::$app->db->createCommand()->setSql("select * from last where username='$username' and phone='$phone'")->queryOne();
        if($res) {
            $session = \Yii::$app->session;
            $session->open();
            $session->set('username',$username);
            if($username == 'jtt') {
                $this->redirect(['add/list']);
            }else {
                return $this->render('main',['info'=>$res]);
            }
        }else {
            echo '账号或密码输入错误';
            die;
        }
    }
    /**
     * 列表展示页面
     * 展示所有用户信息，也就是管理员登录
     */
    public function actionList() {
        $redis = new \Redis();
        $redis->connect('127.0.0.1',6379);
        $info = \Yii::$app->request->post();
        if(\Yii::$app->request->isPost) {
            $name = $info['username'];
            $sql = "select * from last where username like '%$name%'";
        }else {
            $name = '';
            $sql = 'select * from last';
        }
        $redis->select(3);
        if($redis->hExists('userinfo',$name."info")) {
            $response = $redis->hGet('userinfo',$name."info");
            $data = json_decode($response,true);
        }else {
            $data = \Yii::$app->db->createCommand()->setSql($sql)->queryAll();
            $redis->hSet('userinfo',$name."info",json_encode($data));
        }
//        print_r($data);die;
        return $this->render('list',['list'=>$data]);
    }
    /**
     * 上传excel导入数据库
     */
    public function actionUpload() {
        include_once "./PHPExcel.php";
        $excel = new \PHPExcel();
        $path = 'D:\web\PHPTutorial\WWW\Only_three\last\basic\web\last.xls';
        $load = \PHPExcel_IOFactory::load($path);
        $data = $load->getActiveSheet()->toArray();
        $name = array_shift($data);
//        print_r($name);
        foreach($data as $k => $v) {
            $arr[$k] = array_combine($name,$v);
        }
        $values = '';
        foreach($arr as $k => $v) {
            $values .= ",(";
            $key[$k] = array_keys($v);
           if($key[$k][0] == 'title') {
               $values .= "null,'".$v['title']."','".$v['username']."','".$v['phone']."'";
           }else {
               $values .= ",null,'".$v['title']."','".$v['username']."','".$v['phone']."'";
           }
            $values .= ")";
        }
        $values = substr($values,1);
//        echo $values;die;
        $res = \Yii::$app->db->createCommand()->setSql("insert into last VALUES $values")->execute();
        if($res) {
            echo '导入成功';
        }else {
            echo '导入失败';
        }
    }
    public function actionSetexcel() {
        include_once "./PHPExcel.php";
        $excel = new \PHPExcel();
        $data = \Yii::$app->db->createCommand()->setSql('select * from last')->queryAll();
        foreach($data[0] as $k => $v) {
            $name[$k] =  $k;
        }
//        print_r($name);
        array_unshift($data,$name);
        $excel->getActiveSheet()->setTitle('员工详情');
        foreach($data as $k => $v) {
            $excel->getActiveSheet()->setCellValue('A'.($k+1),$v['id'])
                ->setCellValue('B'.($k+1),$v['title'])
                ->setCellValue('C'.($k+1),$v['username'])
                ->setCellValue('D'.($k+1),$v['phone']);
        }
        $write = \PHPExcel_IOFactory::createWriter($excel,'excel5');
        $write->save('./用户信息.xls');
        echo '已导出';
        $array['ip'] = $_SERVER['REMOTE_ADDR'];
        $array['doing'] = '导出excel表格';
        $session = \Yii::$app->session;
        $session->open();
        $array['name'] = $session->get('username');
//        echo $session->get('username');die;
//        print_r($array);
        $result = \Yii::$app->db->createCommand()->insert('info',$array)->execute();
//        echo $result;
    }
}