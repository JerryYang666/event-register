<?php
require_once 'basefile/Person.php';
require_once 'basefile/PartnerGroup.php';
require_once 'basefile/Exoperson.php';
header("Content-type:application/json;charset=utf-8");

//开始
$loginID = isset($_POST['prom_login_id']) ? htmlspecialchars($_POST['prom_login_id']) : '';
$partnerName = isset($_POST['partner_name']) ? htmlspecialchars($_POST['partner_name']) : '';
$partnerIdCard = isset($_POST['partner_id_card']) ? htmlspecialchars($_POST['partner_id_card']) : '';

$operatePerson = new Person($loginID,"uuid");
$operatePerson->apiLog();

if ($operatePerson->checkIdCard($partnerIdCard) && $operatePerson->checkName($partnerName)){
}else{
    $operatePerson->feedback("fail","姓名或身份证号格式错误");
	exit();
}

if ($operatePerson->checkAccountStat() == false){
    $operatePerson->feedback("fail","请先激活账户再邀请");
    exit();
}elseif ($operatePerson->checkPartnerAvailability() == false){
    $operatePerson->feedback("fail","您已经邀请了舞伴，不可以再次邀请其他舞伴");
    exit();
}else {
    $exo = new Exoperson($loginID,$partnerName,$partnerIdCard,$operatePerson->getUsername());
    //$operatePerson->clearAllOtherInviter();
    //生成新舞伴组
    $newPerson = new Person($exo->getNewUsername(),"username");
    $newPerson->dbWrite(["activate"=>"1","deposit_status"=>"1"]);
    $newPartnerGroup = new PartnerGroup($loginID);
    $newPartnerGroup->addMember($operatePerson->getUsername());
    $newPartnerGroup->addMember($newPerson->getUsername());
    //配对
    $operatePerson->pairWith($newPerson->getUsername(),$newPerson->getName(),$newPartnerGroup->getGroupId(),$loginID);
    $newPerson->pairWith($operatePerson->getUsername(),$operatePerson->getName(),$newPartnerGroup->getGroupId(),$loginID);
    $newPerson->dbWrite(["activate"=>"0","deposit_status"=>"0"]);
    $operatePerson->feedback("success","邀请成功");
    exit();
}

?>