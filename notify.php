<?php
require_once 'basefile/Basic.php';
require_once 'basefile/Person.php';
header('Content-type:text/html; Charset=utf-8');



//uuid获取账户username
function IDgetUsername ( $loginID ) {
    $basic = new Basic();
	$con1 = $basic->getDbConn();
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

//支付宝公钥，账户中心->密钥管理->开放平台密钥，找到添加了支付功能的应用，根据你的加密类型，查看支付宝公钥
$alipayPublicKey='';

$aliPay = new AlipayService($alipayPublicKey);
//验证签名
$result = $aliPay->rsaCheck($_POST,$_POST['sign_type']);
if($result===true){
	//处理你的逻辑，例如获取订单号$_POST['out_trade_no']，订单金额$_POST['total_amount']等
     if ($_POST['trade_status'] == 'TRADE_SUCCESS' or $_POST['trade_status'] == 'TRADE_FINISHED'){
            $basic = new Basic();
            $con = $basic->getDbConn();
            $outTradeNo = $_POST['out_trade_no'];
            $sql1 = "SELECT * FROM alipay_order_id WHERE out_trade_no = '$outTradeNo'";
            $result1 = mysqli_query($con,$sql1);
            while($row1 = mysqli_fetch_array($result1)){
			if (abs($row1['amount'] - $_POST['total_amount']) < 0.1){
			    if ($row1['trade_type'] == "0"){
                    $outTradeNo = $_POST['out_trade_no'];
                    $sql2 = "UPDATE alipay_order_id SET trade_status = '1' WHERE alipay_order_id.out_trade_no = '$outTradeNo'";
                    mysqli_query($con,$sql2);
                    $username = IDgetUsername($row1['user_login_id']);
                    $sql3 = "UPDATE temp SET deposit_status = '1' WHERE temp.Username = '$username'";
                    mysqli_query($con,$sql3);
                    $sql4 = "UPDATE temp SET deposit_order_id = '$outTradeNo' WHERE temp.Username = '$username'";
                    mysqli_query($con,$sql4);
                    $post_arr = array("notify_time"=>$_POST['notify_time'],"notify_type"=>$_POST['notify_type'],"notify_id"=>$_POST['notify_id'],"trade_no"=>$_POST['trade_no'],"out_trade_no"=>$_POST['out_trade_no'],"buyer_id"=>$_POST['buyer_id'],"buyer_logon_id"=>$_POST['buyer_logon_id'],"seller_email"=>$_POST['seller_email'],"trade_status"=>$_POST['trade_status'],"seller_id"=>$_POST['seller_id'],"total_amount"=>$_POST['total_amount'],"receipt_amount"=>$_POST['receipt_amount'],"buyer_pay_amount"=>$_POST['buyer_pay_amount'],"gmt_create"=>$_POST['gmt_create'],"gmt_payment"=>$_POST['gmt_payment'],"fund_bill_list"=>$_POST['fund_bill_list'],"gmt_close"=>$_POST['gmt_close']);
                    $post_json = json_encode($post_arr);
                    $sql5 = "UPDATE alipay_order_id SET notify_post = '$post_json' WHERE alipay_order_id.out_trade_no = '$outTradeNo'";
                    mysqli_query($con,$sql5);
                    echo 'success';exit();
                }elseif ($row1['trade_type'] == "1"){
			        $payPerson = new Person($row1['user_login_id'],"uuid");
			        $payPerson->dbWrite(["final_pay_status"=>"1","final_pay_id"=>"$outTradeNo"]);
                    $sql2 = "UPDATE alipay_order_id SET trade_status = '1' WHERE alipay_order_id.out_trade_no = '$outTradeNo'";
                    mysqli_query($con,$sql2);
                    $post_arr = array("notify_time"=>$_POST['notify_time'],"notify_type"=>$_POST['notify_type'],"notify_id"=>$_POST['notify_id'],"trade_no"=>$_POST['trade_no'],"out_trade_no"=>$_POST['out_trade_no'],"buyer_id"=>$_POST['buyer_id'],"buyer_logon_id"=>$_POST['buyer_logon_id'],"seller_email"=>$_POST['seller_email'],"trade_status"=>$_POST['trade_status'],"seller_id"=>$_POST['seller_id'],"total_amount"=>$_POST['total_amount'],"receipt_amount"=>$_POST['receipt_amount'],"buyer_pay_amount"=>$_POST['buyer_pay_amount'],"gmt_create"=>$_POST['gmt_create'],"gmt_payment"=>$_POST['gmt_payment'],"fund_bill_list"=>$_POST['fund_bill_list'],"gmt_close"=>$_POST['gmt_close']);
                    $post_json = json_encode($post_arr);
                    $sql5 = "UPDATE alipay_order_id SET notify_post = '$post_json' WHERE alipay_order_id.out_trade_no = '$outTradeNo'";
                    mysqli_query($con,$sql5);
                    //开始写入final-info
                    $finalID = $payPerson->guid();
                    $payPerson->dbWrite(["final_id"=>"$finalID"]);
                    $payPerson->dbInsert("final_info",["username"=>"{$payPerson->getUsername()}","name"=>"{$payPerson->getName()}","phone"=>"{$payPerson->getPhone()}","arrive_time"=>"{$payPerson->getPersonArriveTime()}","table_no"=>"{$payPerson->getTableNo()}","final_id"=>"$finalID","arrive_status"=>'0']);
                    //发短信
                    $payPerson->tencentSend('647014',["{$payPerson->getPersonArriveTime()}"],$payPerson->getUsername(),"0E636160-A349-BACE-4426-F2EA66E0D3E1");
                    echo 'success';exit();
                }else{
                    echo 'success';exit();
                }
			}else{
				echo 'success';exit();
			}
		}
     }else{
     	echo 'success';exit();
     } //程序执行完后必须打印输出“success”（不包含引号）。如果商户反馈给支付宝的字符不是success这7个字符，支付宝服务器会不断重发通知，直到超过24小时22分钟。一般情况下，25小时以内完成8次通知（通知的间隔频率一般是：4m,10m,10m,1h,2h,6h,15h）；
    echo 'success';exit();
}
echo 'error';exit();
class AlipayService
{
    //支付宝公钥
    protected $alipayPublicKey;
    protected $charset;

    public function __construct($alipayPublicKey)
    {
        $this->charset = 'utf8';
        $this->alipayPublicKey=$alipayPublicKey;
    }

    /**
     *  验证签名
     **/
    public function rsaCheck($params) {
        $sign = $params['sign'];
        $signType = $params['sign_type'];
        unset($params['sign_type']);
        unset($params['sign']);
        return $this->verify($this->getSignContent($params), $sign, $signType);
    }

    function verify($data, $sign, $signType = 'RSA') {
        $pubKey= $this->alipayPublicKey;
        $res = "-----BEGIN PUBLIC KEY-----\n" .
            wordwrap($pubKey, 64, "\n", true) .
            "\n-----END PUBLIC KEY-----";
        ($res) or die('支付宝RSA公钥错误。请检查公钥文件格式是否正确');

        //调用openssl内置方法验签，返回bool值
        if ("RSA2" == $signType) {
            $result = (bool)openssl_verify($data, base64_decode($sign), $res, version_compare(PHP_VERSION,'5.4.0', '<') ? SHA256 : OPENSSL_ALGO_SHA256);
        } else {
            $result = (bool)openssl_verify($data, base64_decode($sign), $res);
        }
//        if(!$this->checkEmpty($this->alipayPublicKey)) {
//            //释放资源
//            openssl_free_key($res);
//        }
        return $result;
    }

    /**
     * 校验$value是否非空
     *  if not set ,return true;
     *    if is null , return true;
     **/
    protected function checkEmpty($value) {
        if (!isset($value))
            return true;
        if ($value === null)
            return true;
        if (trim($value) === "")
            return true;
        return false;
    }

    public function getSignContent($params) {
        ksort($params);
        $stringToBeSigned = "";
        $i = 0;
        foreach ($params as $k => $v) {
            if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {
                // 转换成目标字符集
                $v = $this->characet($v, $this->charset);
                if ($i == 0) {
                    $stringToBeSigned .= "$k" . "=" . "$v";
                } else {
                    $stringToBeSigned .= "&" . "$k" . "=" . "$v";
                }
                $i++;
            }
        }
        unset ($k, $v);
        return $stringToBeSigned;
    }

    /**
     * 转换字符集编码
     * @param $data
     * @param $targetCharset
     * @return string
     */
    function characet($data, $targetCharset) {
        if (!empty($data)) {
            $fileType = $this->charset;
            if (strcasecmp($fileType, $targetCharset) != 0) {
                $data = mb_convert_encoding($data, $targetCharset, $fileType);
                //$data = iconv($fileType, $targetCharset.'//IGNORE', $data);
            }
        }
        return $data;
    }
}