<?php
require_once 'basefile/Person.php';
require_once 'basefile/Basic.php';

header("Content-type:application/json;charset=utf-8");

$username = isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '';
$deviceID = $_COOKIE['prom_device_id'];

$person = new Person($username,"username");
$person->apiLog();
if ($person->checkSmsCodeAvailability() == false){
    $person->feedback("fail","您不符合短信验证码登陆条件，请用密码登录");
    exit();
}
$ipAdd = $person->getip();
$ua = $_SERVER['HTTP_USER_AGENT'];
$timestamp = time();

if ($person->checkUuid($deviceID) == false){
    $deviceID = $person->guid();
    setcookie("prom_device_id",$deviceID,"1919558118","/",".jerryang.moe");
}

$sql = "SELECT * FROM sms_verify_code WHERE username = '$username' OR deviceID = '$deviceID' OR ip = '$ipAdd' OR ua = '$ua'";
$con = $person->getDbConn();
$result = mysqli_query($con,$sql);
$maxEndTime = 0;
while ($row = mysqli_fetch_array($result)){
    if (intval($row['endtime']) > $maxEndTime){
        $maxEndTime = intval($row['endtime']);
    }
}
if ($maxEndTime > $timestamp){
    $person->feedback("fail","操作过于频繁，请稍后再试");
    exit();
}


$smsToken = $person->guid();
$smsCode = mt_rand(10000,99999);
$endtime = intval($timestamp) + 300;

$person->dbInsert("sms_verify_code",["username"=>"$username", "code"=>"$smsCode", "token"=>"$smsToken", "deviceID"=>"$deviceID", "timestamp"=>"$timestamp", "ip"=>"$ipAdd", "ua"=>"$ua","endtime"=>"$endtime"]);
setcookie("prom_sms_token",$smsToken,time()+300,"/",".jerryang.moe");
$person->tencentSend('628214',["$smsCode"],"$username","$smsToken");
$person->feedback("success","验证码发送成功，如30秒内未收到验证码也可使用密码登录");
exit();
