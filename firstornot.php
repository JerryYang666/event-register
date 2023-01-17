<?php
require_once 'basefile/Basic.php';
require_once 'basefile/Person.php';

header("Content-type:application/json;charset=utf-8");

//获取参数
$username = isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '';
//实例化
$person = new Person($username,"username");
$person->apiLog();
//连接数据库
$row1 = $person->dbRead(["first_login_flag","ori_pwd_flag"]);
//返回数据
$person->generalFeedback(["status"=>"success","firstornot"=>$row1['first_login_flag'],"oriPwdFlag"=>$row1['ori_pwd_flag']]);

?>