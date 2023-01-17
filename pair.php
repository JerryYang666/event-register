<?php
require_once 'basefile/PartnerGroup.php';

header("Content-type:application/json;charset=utf-8");


//开始
$loginID = isset($_POST['prom_login_id']) ? htmlspecialchars($_POST['prom_login_id']) : '';
$actionID = isset($_POST['action_id']) ? htmlspecialchars($_POST['action_id']) : '';

$operatePerson = new Person($loginID,"uuid");
$operatePerson->apiLog();

if ($operatePerson->checkAccountStat() == false){
    $operatePerson->feedback("fail","请先激活账户再操作");
    exit();
}elseif ($operatePerson->checkPartnerAvailability() == false){
    $operatePerson->feedback("fail","您已经绑定了/邀请了舞伴，请不要重复操作");
    exit();
}

$timestamp = time();

//多人舞伴逻辑代码
if ($actionID == "2"){
    $inschoolPartnerName = isset($_POST['inschool_partner_name']) ? htmlspecialchars($_POST['inschool_partner_name']) : '';
    if ($operatePerson->checkPgid($inschoolPartnerName)){
        //入组
        $targetPartnerGroup = new PartnerGroup($inschoolPartnerName);
        $targetPartnerGroup->addMember($operatePerson->getUsername());
        //加log
        $targetPartnerGroup->dbInsert("name_query_log",["Username"=>"{$operatePerson->getUsername()}", "query_name"=>"$inschoolPartnerName", "timestamp"=>"$timestamp", "login_id"=>"$loginID"]);
        $operatePerson->joinGroup($inschoolPartnerName,$loginID);
        //发短信
        $pgMember0 = new Person($targetPartnerGroup->getMember(0),"username");
        $pgMember1 = new Person($targetPartnerGroup->getMember(1),"username");
        $pgMember0->tencentSend('614087',["{$operatePerson->getUsername()}"],$operatePerson->getUsername(),$loginID);
        $pgMember1->tencentSend('614087',["{$operatePerson->getUsername()}"],$operatePerson->getUsername(),$loginID);
        $operatePerson->feedback("success","加入舞伴组成功");
        exit();
    }elseif ($operatePerson->checkName($inschoolPartnerName)){
        $operatePerson->dbInsert("name_query_log",["Username"=>"{$operatePerson->getUsername()}", "query_name"=>"$inschoolPartnerName", "timestamp"=>"$timestamp", "login_id"=>"$loginID"]);
        $targetPerson = new Person($inschoolPartnerName,"name");
        //pre-check
        if ($targetPerson->checkDepositStat() == false){
            $operatePerson->feedback("fail","此人未报名舞会");
            exit();
        }elseif ($targetPerson->getUsername() == $operatePerson->getUsername()){
            $newPartnerGroup = new PartnerGroup($loginID);
            $newPartnerGroup->addMember($operatePerson->getUsername());
            $operatePerson->pairWith($targetPerson->getUsername(),$targetPerson->getName(),$newPartnerGroup->getGroupId(),$loginID);
            $operatePerson->feedback("success","solo登记成功");
            exit();
        }elseif ($targetPerson->checkPairAvailability() == false){
            $operatePerson->feedback("fail","对方已经绑定了舞伴");
            exit();
        }
        //根据对方status判断
        if ($targetPerson->getPartnerStatus() == "2"){
            //清空其他追求者信息(如果对上了)
            if ($targetPerson->getPartnerUsername() == $operatePerson->getUsername()){
                //$targetPerson->clearAllOtherInviter();
                //$operatePerson->clearAllOtherInviter();
                //生成新舞伴组
                $newPartnerGroup = new PartnerGroup($loginID);
                $newPartnerGroup->addMember($targetPerson->getUsername());
                $newPartnerGroup->addMember($operatePerson->getUsername());
                //配对
                $operatePerson->pairWith($targetPerson->getUsername(),$targetPerson->getName(),$newPartnerGroup->getGroupId(),$loginID);
                $targetPerson->pairWith($operatePerson->getUsername(),$operatePerson->getName(),$newPartnerGroup->getGroupId(),$loginID);
                $operatePerson->feedback("success","你和舞伴已双向确认成功，请刷新网页后在“我的报名”中的“舞伴”一栏查看信息");
                exit();
            }else{
                $operatePerson->feedback("fail","对方已经邀请了别人");
                exit();
            }
        }elseif ($targetPerson->getPartnerStatus() == "0" || $targetPerson->getPartnerStatus() == "4"){
            $operatePerson->singlePairWith($targetPerson->getUsername(),$targetPerson->getName(),$loginID);
            $targetPerson->tencentSend('612379',[],$operatePerson->getUsername(),$loginID);
            $operatePerson->feedback("success","你已登记成功，你的舞伴尚未登记，请提醒ta尽快登记，方便双向确认");
            exit();
        }
    }
}elseif ($actionID == "4"){
    //验证输入内容长度-写入数据库
    $inschoolPartnerRequest = isset($_POST['inschool_pair_request']) ? htmlspecialchars($_POST['inschool_pair_request']) : '';
    if (mb_strlen($inschoolPartnerRequest,"utf-8") > 200){
       $operatePerson->feedback("fail","内容过长，字数限制200");
       exit();
    }elseif ($operatePerson->checkPartnerAvailability() == false){
        $operatePerson->feedback("fail","你已经邀请或绑定舞伴，不可再参加配对活动");
        exit();
    }elseif ($operatePerson->getPartnerStatus() == "4"){
        $operatePerson->feedback("fail","你已经提交过配对信息，请不要重复提交");
        exit();
    }
    $operatePerson->joinPublicPair($inschoolPartnerRequest,$loginID);
    $operatePerson->feedback("success","加入配对成功");
    exit();
}else{
    $operatePerson->feedback("fail","未知参数错误");
    exit();
}

?>