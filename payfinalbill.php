<?php
require_once 'basefile/Person.php';
require_once 'basefile/AlipayService.php';
header("Content-type:application/json;charset=utf-8");

//获取参数
$loginID = isset($_POST['prom_login_id']) ? htmlspecialchars($_POST['prom_login_id']) : '';

$person = new Person($loginID,"uuid");
$person->apiLog();

//截止代码
$person->feedback("fail","舞会报名截止，支付系统已关闭，发起支付失败");
exit();
//应删除

if ($person->checkFinalPayStat() == true){
    $person->feedback("done","您尾款已支付完成，请刷新网页，在支付一栏获取电子票");
    exit();
}

if ($person->checkFinalEverything() == true){
}else{
    $person->feedback("fail","您有前序信息登记步骤未完成，请刷新网页后查看（您需要完成舞伴登记、到达时间选择、桌号登记三项之后才能支付尾款并获得电子票）");
    exit();
}


/*** 请填写以下配置信息 ***/
$appid = '2021001106625036';  //https://open.alipay.com 账户中心->密钥管理->开放平台密钥，填写添加了电脑网站支付的应用的APPID
$notifyUrl = 'https://promdev.jerryang.moe/notify.php';     //付款成功后的异步回调地址
$outTradeNo = uniqid(mt_rand(10000,99999));    //你自己的商品订单号，不能重复
$payAmount = 300;          //付款金额，单位:元
$orderName = 'NFLS-Prom2020-'. $person->getName() .'-尾款';    //订单标题
$signType = 'RSA2';			//签名算法类型，支持RSA2和RSA，推荐使用RSA2
$rsaPrivateKey='';		//商户私钥，填写对应签名算法类型的私钥，如何生成密钥参考：https://docs.open.alipay.com/291/105971和https://docs.open.alipay.com/200/105310
/*** 配置结束 ***/
$aliPay = new AlipayService();
$aliPay->setAppid($appid);
$aliPay->setNotifyUrl($notifyUrl);
$aliPay->setRsaPrivateKey($rsaPrivateKey);
$aliPay->setTotalFee($payAmount);
$aliPay->setOutTradeNo($outTradeNo);
$aliPay->setOrderName($orderName);

$result = $aliPay->doPay();
$result = $result['alipay_trade_precreate_response'];
if($result['code'] && $result['code']=='10000'){
    //生成商户端订单
    $tstamp = time();
    $person->dbInsert("alipay_order_id",["create_time"=>"$tstamp", "subject"=>"$orderName", "out_trade_no"=>"$outTradeNo", "amount"=>"$payAmount", "user_login_id"=>"$loginID", "trade_status"=>'0', "trade_type"=>'1', "cancel_flag"=>'0']);
    //生成二维码
    $url0 = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data='.$result['qr_code'];
    $url = "<img src='{$url0}' style='width:250px;' id='alipay-qr'><br>";
    //此处由于sql转义难度过大，遂放弃使用通用返回函数
    $json_arr = array("status"=>"success","qrcode"=>$url,"codecontent"=>$result['qr_code']);
    $json_obj = json_encode($json_arr);
    echo $json_obj;
    exit();
}else{
    $person->feedback("fail","支付发起失败");
    exit();
}