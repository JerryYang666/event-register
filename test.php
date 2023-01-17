<?php
require_once 'basefile/Basic.php';
require_once 'basefile/Person.php';
header("Content-type:application/json;charset=utf-8");

$sql = "";
$loginID = $_COOKIE['prom_login_id'];
$search = $_GET['search'];
//搜索匹配
if (preg_match("/^\d{1}$/",$search)){
    $sql = "SELECT * FROM tables WHERE table_no like '$search%'";
}else if (preg_match("/^\d{2}$/",$search)){
    $sql = "SELECT * FROM tables WHERE table_no = '$search'";
}else{
    $sql = "SELECT * FROM tables";
}

$ba = new Basic();
$ba->apiLog();
//检查cookie
if ($ba->checkUuid($loginID) == false){
    $ba->feedback("fail","未授权访问");
    exit();
}
//检查是否已经有桌子
$person = new Person($loginID,"uuid");
$hasTable = false;
if (preg_match("/^\d{2}$/",$person->getTableNo())){
    $hasTable = true;
}

$con = $ba->getDbConn();


$result = mysqli_query($con,$sql);

$tablesarray = [];
while ($row = mysqli_fetch_array($result)){
    if ($row['remain_num'] != "0") {
        if ($row['registered_num'] == "0") {
            $text = $row['table_no'] . "号桌-当前无人 上限" . $row['remain_num'] . "人";
        } else {
            $text = "-" . $row['table_no'] . "号桌 | 现有" . $row['first_guy'] . "等" . $row['registered_num'] . "人-";
        }
        if ($hasTable){
            //如果已有桌子，不生成桌号，只能看不能选
            $thistable = ["text" => $text];
            array_push($tablesarray, $thistable);
        }else{
            //如果没桌子，要生成桌号
            $thistable = ["id" => $row['table_no'], "text" => $text];
            array_push($tablesarray, $thistable);
        }

    }
}

$new = ["results"=>$tablesarray];

$json = json_encode($new);
echo $json;
