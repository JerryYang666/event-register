<?php
/*
header("Content-type:application/json;charset=utf-8");

//uuid正则匹配，验证
function is_uuid( $uid ){
	$regx = "/^[A-F0-9]{8}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{12}$/";
     $arr_split = array();
     if(!preg_match($regx, $uid)){
        $json_arr = array("status"=>"fail","reason"=>"非法uuid值");
		$json_obj = json_encode($json_arr);
		echo $json_obj;
        return FALSE;
    }else{
    	   return TRUE;
    }
}

//账户性质判断
function is_pugao( $arr ){
	if (strpos($arr['Username'],"20140") == "1"){
		return "本届普高账户";
	}else if(strpos($arr['Username'],"20201") == "1"){
		return "非本届普高账户";
	}else{
		return "未知账户错误";
	}
}

//uuid获取账户username
function IDgetUsername ( $loginID ) {
	$con1 = mysqli_connect('localhost','root','Yangrhpython2020','PROM_TEST');
	$sql = "SELECT * FROM login_id_list WHERE login_id = '$loginID'";
	$result = mysqli_query($con1,$sql);
	if (!$result) {
    		printf("DataBase Error");
    		exit();
	}
	$timestamp = time();
	while($row = mysqli_fetch_array($result)){
		//loginID过期检查
		if ($row['endtime'] > $timestamp){
			$username = $row['username'];
			return $username;
		}else if($row['endtime'] < $timestamp){
			return FALSE;
		}else{
			return FALSE;
		}
	}
	mysqli_close($con1);
}


///开始
$loginID = isset($_POST['prom_login_id']) ? htmlspecialchars($_POST['prom_login_id']) : '';
$actionType = isset($_POST['actionType']) ? htmlspecialchars($_POST['actionType']) : '';

if (is_uuid( $loginID )){
}else{
	exit();
}

$con = mysqli_connect('localhost','root','Yangrhpython2020','PROM_TEST');
if (mysqli_connect_errno($con)) 
{ 
    echo "连接 MySQL 失败"; 
} 

$username = IDgetUsername($loginID);

if ($username == FALSE){
	$json_arr = array("status"=>"fail","reason"=>"登陆超时或未知错误");
	$json_obj = json_encode($json_arr);
	echo $json_obj;
	exit();
}

if ($actionType == "getInfo"){
	//返回数据区域
	$sql2 = "SELECT * FROM temp WHERE Username = '$username'";
	$result2 = mysqli_query($con,$sql2);
	while($row2 = mysqli_fetch_array($result2)){
		if ($row2['activate'] == 0){
			$json_arr = array("status"=>"inactivate","realname"=>$row2['name']);
			$json_obj = json_encode($json_arr);
			echo $json_obj;
			mysqli_close($con);
			exit();
		}else if ($row2['activate'] == 1){
			$json_arr = array("status"=>"success","realname"=>$row2['name'],"gender"=>$row2['gender'],"isPugao"=>is_pugao($row2));
			$json_obj = json_encode($json_arr);
			echo $json_obj;
			exit();
		}
	}
}else if ($actionType == "activateAccount"){
	$sql3 = "UPDATE temp SET activate = '1' WHERE temp.Username = '$username'";
	mysqli_query($con,$sql3);
	$sql4 = "SELECT * FROM temp WHERE Username = '$username'";
	$result4 = mysqli_query($con,$sql4);
	while($row3 = mysqli_fetch_array($result4)){
		$realname = $row3['name'];
		$datetime = date('Y-m-d H:i:s');
		$sql5 = "INSERT INTO activate_log (Username, name, activate_time, operate_id) VALUES ('$username', '$realname', '$datetime', '$loginID')";
		mysqli_query($con,$sql5);
	}
	$json_arr = array("status"=>"activated");
	$json_obj = json_encode($json_arr);
	echo $json_obj;
}else{
	$json_arr = array("status"=>"fail","reason"=>"未知类型action");
	$json_obj = json_encode($json_arr);
	echo $json_obj;
}



mysqli_close($con);
*/

?>