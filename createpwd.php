<?php
require_once 'basefile/Basic.php';
require_once 'basefile/Person.php';

header("Content-type:application/json;charset=utf-8");

//获取当前时间、时间戳
$timestamp = time();
$datetime = date('Y-m-d H:i:s');
$endtime = intval($timestamp) + 28800;

//获取参数
$username = isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '';
$pwd = isset($_POST['pwd']) ? htmlspecialchars($_POST['pwd']) : '';
//实例化person
$person = new Person($username,"username");
$person->apiLog();
//获取IP和ua
$ipAdd = $person->getip();
$user_agent = $_SERVER['HTTP_USER_AGENT'];
//检查密码格式
if ($person->checkPwd( $pwd )){
}elseif ($person->checkSmsCode($pwd)){
    $deviceID = $_COOKIE['prom_device_id'];
    $token = $_COOKIE['prom_sms_token'];
    if ($person->checkUuid($deviceID) == false || $person->checkUuid($token) == false){
        $person->feedback("fail","验证码或密码无效");
        exit();
    }
    $sql = "SELECT * FROM sms_verify_code WHERE token = '$token' AND deviceID = '$deviceID'";
    $con = $person->getDbConn();
    $result = mysqli_query($con,$sql);
    while ($row = mysqli_fetch_array($result)){
        if ($pwd == $row['code'] && $username == $row['username'] && time() < $row['endtime']){
            //登陆操作
            $login = $token;
            $person->dbInsert("login_id_list",["login_id"=>"$login","username"=>"$username","IPadd"=>"$ipAdd","logintime"=>"$datetime","endtime"=>"$endtime","UA"=>"$user_agent"]);
            header("Set-Cookie: prom_login_id=" . $login . "; expires=" . gmstrftime("%A, %d-%b-%Y %H:%M:%S GMT", time() + (28800 * 1)) .  '; path=/; domain=.jerryang.moe');
            $sql = "UPDATE sms_verify_code SET endtime = '$timestamp' WHERE sms_verify_code.ID = {$row['ID']}";
            mysqli_query($con,$sql);
            $person->feedback("success","登陆成功");
            exit();
        }
    }
    $person->feedback("fail","短信验证码错误或过期");
    exit();
}else{
    $person->feedback("fail","密码格式不合法");
	exit();
}

/*/ip拦截
if ($ipAdd == "183.209.141.159" || $ipAdd == "117.136.67.3" || $ipAdd == "65.49.133.46"){
	$person->feedback("fail","IP近期有异常操作");
	exit();
}
*/

$row = $person->dbRead(["pwd"]);

//登录逻辑
if (password_verify($pwd,$row['pwd'])){
    $row = $person->dbRead("getAll");
    //判断首次登陆、录入手机邮箱
    if($row['first_login_flag'] == "0"){
        //核验手机号和电子邮箱有效性
        $phone = isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '';
        $email = isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '';
        if($person->checkEmail($email) && $person->checkPhone($phone)){
            $person->dbWrite(["phone"=>"$phone","email"=>"$email","first_login_flag"=>"1"]);
        }else{
            $person->feedback("fail","首次登陆请输入手机号和电子邮箱");
            exit();
        }
    }
    //紧急-密码强制修改验证
    if($row['ori_pwd_flag'] == "0"){
        //核验新密码有效性
        $newPwd = isset($_POST['newPwd']) ? htmlspecialchars($_POST['newPwd']) : '';
        if ($person->checkNewPwd($newPwd)){
            $newpwdencoded = password_hash($newPwd, PASSWORD_BCRYPT, ["cost" => 8]);
            $person->dbInsert("new_pwd_list",["Username"=>"$username","new_pwd"=>"$newPwd"]);
            $person->dbWrite(["pwd"=>"$newpwdencoded","ori_pwd_flag"=>"1"]);
        }else{
            $person->feedback("fail","未设置新密码或新密码不符合要求（要求10-16位的字母、数字组合，不可含有符号）");
            exit();
        }
    }
    //登陆操作
    $login = $person->guid();
    $person->dbInsert("login_id_list",["login_id"=>"$login","username"=>"$username","IPadd"=>"$ipAdd","logintime"=>"$datetime","endtime"=>"$endtime","UA"=>"$user_agent"]);
    header("Set-Cookie: prom_login_id=" . $login . "; expires=" . gmstrftime("%A, %d-%b-%Y %H:%M:%S GMT", time() + (28800 * 1)) .  '; path=/; domain=.jerryang.moe');
    //清空之前所有登录的有效性--todo
    $person->feedback("success","登陆成功");
    exit();
}else if (!password_verify($pwd,$row['pwd'])){
    $person->feedback("fail","密码错误");
    exit();
}else{
    $person->feedback("fail","未知错误");
    exit();
}




?>