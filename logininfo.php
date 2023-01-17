<?php
require_once 'basefile/Basic.php';
require_once 'basefile/Person.php';
require_once 'basefile/PartnerGroup.php';

header("Content-type:application/json;charset=utf-8");

$register_module_html = "";
$invite_module_html = "";
$preinfo_html = "";
$payment_html = "";

//开始，获取参数
$loginID = isset($_POST['prom_login_id']) ? htmlspecialchars($_POST['prom_login_id']) : '';
$actionType = isset($_POST['actionType']) ? htmlspecialchars($_POST['actionType']) : '';

//实例化person
$person = new Person($loginID,"uuid");
$person->apiLog();

//如果是getInfo
if ($actionType == "getInfo"){
    if ($person->checkAccountStat() == false){
        //账户未激活
        $name = $person->dbRead(["name"]);
        $person->generalFeedback(array("status"=>"inactivate","realname"=>$name['name']));
        exit();
    }else{
        //账户已激活，返回页面所需要的信息
        $info = $person->getAllInfo();
        //检查当前可选时间
        if ($person->getPartnerStatus() == "1") {
            $partnerGroup = new PartnerGroup($person->getPartnerGroupId());
            if ($partnerGroup->getArriveTime() == false){
                $arriveTime = $partnerGroup->checkArriveTimeAvail();
            }else{
                $arriveTime = $partnerGroup->getArriveTime();
            }
        }
        //todo-动态挂载网页内容
        //为方便数据库记录，将html组合放到前端，后端只传finalID
        //$url0 = 'https://helper.jerryang.moe/eticket-qr.php?value=https://prom2020.jerryang.moe/checkin.php?final_id='.$info['final_id'];
        //$url = "<img src='{$url0}' style='width:250px;' id='alipay-qr'><br>";
        $person->generalFeedback(array("status"=>"success","realname"=>$info['name'],"gender"=>$info['gender'],"isPugao"=>$person->isPugao(),"depositPayStat"=>$info['deposit_status'],"depositPayID"=>$info['deposit_order_id'],"partnerStatus"=>$info['partner_status'],"partnerUsername"=>$info['partner_username'],"partnerName"=>$info['partner_name'],"partnerGroupId"=>$info['partner_group_id'],"finalPayStat"=>$info['final_pay_status'],"finalPayID"=>$info['final_pay_id'],"finalID"=>$info['final_id'],"timeChoseAvail"=>"$arriveTime","timeSectionA"=>"16:00-16:30","timeSectionB"=>"16:30-17:00","timeSectionC"=>"17:00-17:30","tableNo"=>"{$info['table_no']}","pageTopPost"=>"<b> 欢迎！</b> 最后两项登记/尾款支付全面开放，你离舞会只有一步之遥！（如因找不到舞伴无法支付尾款，请输自己的名字登记solo；如因没有桌子无法支付尾款，请加入44号公共桌，我们会帮你分配桌子）"));
        exit();
    }
}else if ($actionType == "activateAccount") {
    //如果是activateAccount
    //检查订金支付情况
    if ($person->checkDepositStat() == true && $person->checkAccountStat() == false) {
        //定金已支付，激活账户
        $person->dbWrite(["activate" => "1"]);
        $name = $person->dbRead(["name"]);
        $datetime = date('Y-m-d H:i:s');
        $person->dbInsert("activate_log", ["Username" => "{$person->getUsername()}", "name" => "{$name['name']}", "activate_time" => "$datetime", "operate_id" => "$loginID"]);
        $person->feedback("activated", "激活成功");
    }elseif ($person->checkAccountStat() == true){
        //重复激活
        $person->feedback("activated", "请不要重复操作");
    }else{
        //订金未支付
        $person->feedback("fail","您还未支付订金或系统后台支付状态还未更新。如支付成功30分钟后还显示此消息，请联系舞会负责人");
    }
}else{
	$person->feedback("fail","未知action类型");
	exit();
}
?>