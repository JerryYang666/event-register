<?php
require_once 'basefile/Person.php';
require_once 'basefile/PartnerGroup.php';
require_once 'basefile/Basic.php';

header("Content-type:application/json;charset=utf-8");

//开始，获取参数
$loginID = isset($_POST['prom_login_id']) ? htmlspecialchars($_POST['prom_login_id']) : '';
$actionID = isset($_POST['action_id']) ? htmlspecialchars($_POST['action_id']) : '';

//实例化
$person = new Person($loginID,"uuid");
$person->apiLog();

//检查是否可以调用此接口
if ($person->getPartnerStatus() != "1"){
    $person->feedback("fail","请先完成前序步骤再登记此信息");
    exit();
}

//参数合法性检验
if ($actionID == "1"){
    $allergenInfo = isset($_POST['allergen_info']) ? htmlspecialchars($_POST['allergen_info']) : '';
    $timeSectionChoice = isset($_POST['time_section_choice']) ? htmlspecialchars($_POST['time_section_choice']) : '';
    if (mb_strlen($allergenInfo,"utf-8") > 50){
        $person->feedback("fail","内容过长，字数限制50");
        exit();
    }elseif (!preg_match("/^[a-c]?$/", $timeSectionChoice)){
        $person->feedback("fail","参数输入不合法");
        exit();
    }
    //写入过敏原信息
    $allergenInfoOld = $person->dbRead(["allergen_info"])['allergen_info'];
    if ($allergenInfoOld == "" || $allergenInfoOld == null){
        $person->dbWrite(["allergen_info"=>"$allergenInfo"]);
    }
    //分配时间
    $pg = new PartnerGroup($person->getPartnerGroupId());
    $arriveTime = $pg->allotArriveTime($timeSectionChoice);
    if ($arriveTime == "noAvailable"){
        $person->feedback("fail","您选择的到达时间人数已满，请重新选择");
        exit();
    }elseif ($arriveTime == "alreadyAllotted"){
        $person->feedback("success","您或您的舞伴已经选择过了到达时间，不需要重复选择，过敏原信息登记成功");
        exit();
    }elseif ($arriveTime == "invalidParam"){
        $person->feedback("success","过敏原信息登记成功");
        exit();
    }else{
        $reason = "登记成功，系统为您和您舞伴智能分配的到达时间为" . $arriveTime ."，请尽量准时到达以获得最佳参会体验";
        $person->feedback("success",$reason);
        exit();
    }
}elseif ($actionID == "2"){
    $tableNo = isset($_POST['table_no']) ? htmlspecialchars($_POST['table_no']) : '';
    if (!preg_match("/^\d{2}$/", $tableNo)){
        $person->feedback("fail","桌号输入不合法！请一定输入两位数字，如“10”");
        exit();
    }
    //写入桌号
    $tableNoOld = $person->dbRead(["table_no"])['table_no'];
    if ($tableNoOld == "" || $tableNoOld == null){
        //join
        $joinResult = $person->joinTable($tableNo);
        if ($joinResult == "success"){
            $person->feedback("success","加入桌子成功");
            exit();
        }elseif ($joinResult == "fullTable"){
            $person->feedback("fail","该桌子人数已满");
            exit();
        }elseif ($joinResult == "wrongTableNumber"){
            $person->feedback("fail","没有这个桌号");
            exit();
        }
    }else{
        $person->feedback("fail","您已经加入过桌子，请不要重复操作。如需更换桌子，请先刷新网页后退出当前桌");
        exit();
    }
}else if($actionID == "3"){
    $tableNoOld = $person->dbRead(["table_no"])['table_no'];
    if ($tableNoOld == "" || $tableNoOld == null) {
        $person->feedback("fail","无效操作：没选择桌子或重复退出");
        exit();
    }else{
        $person->quitTable();
        $person->feedback("success","退出桌子成功，请刷新网页后重新加入桌子");
        exit();
    }
}else{
    $person->feedback("fail","invalidParam");
    exit();
}









