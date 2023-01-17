<?php
require_once 'basefile/Basic.php';

function checkFinalID($finalID,$con){
    $sql = "SELECT * FROM final_info WHERE final_id = '$finalID'";
    $result = mysqli_query($con,$sql);
    while ($row = mysqli_fetch_array($result)){
        if ($row['arrive_status'] == "0"){
            if ($_COOKIE['CheckinPermission'] == "960EC304D97D"){
                $sql1 = "UPDATE final_info SET arrive_status = '1' WHERE final_info.final_id = '$finalID'";
                mysqli_query($con,$sql1);
            }
            return ["{$row['name']}","{$row['username']}"];
        }else{
            return false;
        }
    }
    return false;
}

$finalID = $_GET['final_id'];

$basic = new Basic();
$basic->apiLog();
$checkStatus = "无检票权限";
$headerColor = "#666bbb";


if ($basic->checkUuid($finalID) == false){
    //失败
    $headerColor = "#943b43";
    $headerContent = "无效参数";
    $checkStatus = "";
}else{
    $con = $basic->getDbConn();
    $jianpiao = checkFinalID($finalID,$con);
    $datetime = date('Y-m-d H:i:s');
    if ($jianpiao == false){
        $headerColor = "#943b43";
        $headerContent = "无效票";
        if ($_COOKIE['CheckinPermission'] == "960EC304D97D"){
            $checkStatus = "有检票权限";
        }
    }else{
        if ($_COOKIE['CheckinPermission'] == "960EC304D97D"){
            $basic->dbInsert("arrive_log",["username"=>"{$jianpiao[1]}","arrive_time"=>"$datetime","final_id"=>"$finalID"]);
            $headerColor = "#3f9863";
            $checkStatus = "检票成功";
        }
        $username = $jianpiao[1];
        $name = $jianpiao[0];
        $headerContent = "有效票";
    }
}

print <<<EOT
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta charset="UTF-8">
    <title>ticket check</title>
    <style type="text/css">
        .card{
            margin-top:10px;
            width:90%;
            box-shadow: 0 4px 8px 0 rgba(0,0,0,0.2),0 6px 20px 0 rgba(0,0,0,0.19);
            text-align: center;
            margin: 50px auto;
            border-radius: 10px;
            font-size: 25px;
            background-color: #3a3e82;
        }
        .header{
            background-color: {$headerColor};
            margin:0 auto;
            padding: 20px;
            border-radius: 10px 10px 0px 0px;
            font-size: 30px;
            color: white;
        }
        .body{
            padding: 30px;
            color: white;
        }
    </style>
</head>
<body style="background-color: #171941;">
<div>
    <div class="card">
        <div class="header">
            <a>{$headerContent}</a>
        </div>
        <div class="body">
            <p>{$username}</p>
            <p>{$name}</p>
            <p>{$finalID}</p>
            <p>{$checkStatus}</p>
        </div>
    </div>
</div>
</body>
</html>
EOT;
