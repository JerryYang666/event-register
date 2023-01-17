<?php
require_once 'Basic.php';
require_once 'Base.php';
require_once 'TencentSms.php';
require_once 'PartnerGroup.php';

/**
 * Class Person
 */
class Person extends Basic
{
    /**
     * @var
     * 用户名（学号）
     */
    private $username;

    /**
     * @param mixed $username
     */
    private function setUsername($username): void
    {
        if ($this->checkUsername($username)) {
            $this->username = $username;
        }elseif ($username == false){
            $this->username = $username;
        }else{
            $this->username = "G0000";
        }
    }

    /**
     * @return mixed
     */
    public function getUsername()
    {
        if ($this->checkUsername($this->username)){
            return $this->username;
        }
        return false;
    }


    /**
     * @var
     * 姓名
     */
    private $name;

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->dbRead(["name"])['name'];
    }

    /**
     * @param mixed $name
     */
    private function setName($name): void
    {
        $this->name = $name;
    }


    /**
     * @var
     * 订金支付状态
     */
    private $depositStatus;
    /**
     * @var
     * 性别（0->女，1->男）
     */
    private $gender;

    /**
     * @return mixed
     */
    public function getGender()
    {
        return $this->dbRead(["gender"])['gender'];
    }
    /**
     * @var
     * 账户激活状态
     */
    private $activate;
    /**
     * @var
     * 手机号
     */
    private $phone;

    /**
     * @return mixed
     * 返回手机号
     */
    public function getPhone(){
        return $this->dbRead(["phone"])['phone'];
    }
    /**
     * @var
     * 舞伴状态（0-null，1-确认，2-待确认，4-待配对，5-单人）
     */
    private $partnerStatus;

    /**
     * @return mixed
     * 返回舞伴状态
     */
    public function getPartnerStatus()
    {
        return $this->dbRead(["partner_status"])['partner_status'];
    }

    /**
     * @var
     * 舞伴用户名
     */
    private $partnerUsername;

    /**
     * @return mixed
     * 返回舞伴用户名
     */
    public function getPartnerUsername()
    {
        return $this->dbRead(["partner_username"])['partner_username'];
    }

    /**
     * @return mixed
     * 返回桌号
     */
    public function getTableNo(){
        return $this->dbRead(["table_no"])['table_no'];
    }


    /**
     * @var
     * 舞伴组id
     */
    private $partnerGroupId;

    /**
     * @return mixed
     * 返回舞伴组id
     */
    public function getPartnerGroupId()
    {
        return $this->dbRead(["partner_group_id"])['partner_group_id'];
    }

    /**
     * @return bool|mixed
     * 返回这个人的到达时间
     */
    public function getPersonArriveTime(){
        $pg = new PartnerGroup($this->getPartnerGroupId());
        return $pg->getArriveTime();
    }


    /**
     * Person constructor.
     * @param $personIdentifier
     * @param $type (uuid/username/name)
     * 构造人，可传入uuid/username/姓名
     */
    public function __construct($personIdentifier, $type){
        if ($type == "uuid" && $this->checkUuid($personIdentifier) == true) {
            $this->setUsername($this->idGetUsername($personIdentifier));
            if ($this->username == false) {
                $this->feedback("fail", "登陆超时，请重新登录");
                exit();
            }
        }elseif ($type == "username" && $this->checkUsername($personIdentifier) == true) {
            $this->setUsername($personIdentifier);
            if ($this->dbRead(["Username"]) == false){
                $this->feedback("fail","账户不存在！");
                exit();
            }
        }elseif ($type == "name" && $this->checkName($personIdentifier) == true){
            $this->setUsername($this->nameGetUsername($personIdentifier));
            if ($this->username == false) {
                $this->feedback("fail", "姓名查无此人");
                exit();
            }
        }else{
            $this->setUsername("");
            $this->feedback("fail","pid输入不合法");
            exit();
        }
}

    //账户验证类function

    /**
     * @return string
     * 判断账户性质
     */
    public function isPugao(){
        if (strpos($this->username,"20140") == "1"){
            return "本届普高账户";
        }else if(strpos($this->username,"20201") == "1"){
            return "非本届普高账户";
        }else{
            return "未知账户错误";
        }
}

    /**
     * @return bool
     * 检查账户是否激活
     */
    public function checkAccountStat(){
        $accountStat = $this->dbRead(["activate","deposit_status"]);
        if ($accountStat["activate"] == "1" && $accountStat["deposit_status"] == "1"){
            return true;
        }
        return false;
    }

    /**
     * @return bool
     * 检查账户是否可以用验证码登录
     */
    public function checkSmsCodeAvailability(){
        $accountStat = $this->dbRead(["first_login_flag","ori_pwd_flag"]);
        if ($accountStat["first_login_flag"] == "1" && $accountStat["ori_pwd_flag"] == "1"){
            return true;
        }
        return false;
    }

    /**
     * @return bool
     * 检查账户是否交定金
     */
    public function checkDepositStat(){
        $depositStat = $this->dbRead(["deposit_status"]);
        if ($depositStat['deposit_status'] == "1"){
            return true;
        }
        return false;
    }

    /**
     * @return bool
     * 检查账户是否完成尾款支付
     */
    public function checkFinalPayStat(){
        $depositStat = $this->dbRead(["final_pay_status"]);
        if ($depositStat['final_pay_status'] == "1"){
            return true;
        }
        return false;
    }

    /**
     * @return bool
     * 付尾款前最终检查everything
     */
    public function checkFinalEverything(){
        $accountStat = $this->checkAccountStat();
        $partnerStat = $this->getPartnerStatus();
        $pg = new PartnerGroup($this->getPartnerGroupId());
        $pgTime = $pg->getArriveTime();
        $tableNoValid = preg_match("/^\d{2}$/",$this->dbRead(["table_no"])['table_no']);
        if ($accountStat == true && $partnerStat == "1" && $pgTime != false && $tableNoValid == true){
            return true;
        }else{
            return false;
        }
    }

    /**
     * @return bool
     * 检查是否可以进行舞伴配对
     */
    public function checkPartnerAvailability(){
        $partnerStat = $this->dbRead(["partner_status"]);
        if ($partnerStat['partner_status'] == "1" || $partnerStat['partner_status'] == "2"){
            return false;
        }
        return true;
    }

    /**
     * @return bool
     * 检查是否可以被配对
     */
    public function checkPairAvailability(){
        $partnerStat = $this->dbRead(["partner_status"]);
        if ($partnerStat['partner_status'] == "1"){
            return false;
        }
        return true;
    }


    //获取类function
    /**
     * @param $uuid
     * @return bool|mixed
     * uuid获取用户名（同时检查登陆有效性）
     */
    protected function idGetUsername($uuid){
        $this->startDbConn();
        $sql = "SELECT * FROM login_id_list WHERE login_id = '$uuid'";
        $result = mysqli_query($this->con,$sql);
        if (!$result) {
            printf("DataBase Error");
            exit();
        }
        $this->endDbConn();
        $timestamp = time();
        while($row = mysqli_fetch_array($result)){
            //loginID过期检查
            if ($row['endtime'] > $timestamp){
                return $row['username'];
            }else if($row['endtime'] < $timestamp){
                return FALSE;
            }else{
                return FALSE;
            }
        }
        return false;
}

    /**
     * @param $name
     * @return bool|mixed
     * 姓名获取username
     */
    protected function nameGetUsername($name){
        $this->startDbConn();
        $sql = "SELECT * FROM temp WHERE name = '$name'";
        $result = mysqli_query($this->con,$sql);
        if (!$result) {
            printf("DataBase Error");
            exit();
        }
        $this->endDbConn();
        while($row = mysqli_fetch_array($result)){
            return $row['Username'];
        }
        return false;
}

    /**
     *返回全部信息数组
     */
    public function getAllInfo(){
        return $this->dbRead("getAll");
}

    /**
     * @return string
     * 获取打码姓名
     */
    public function getNameMasked() {
        $nameArr = $this->dbRead(["name"]);
        $name = $nameArr['name'];
        $hisNameLength = mb_strlen($name,"utf-8");
        if ($hisNameLength == 2){
            $smsName = mb_substr($name,0,1,'utf-8') . "*";
            return $smsName;
        }else if($hisNameLength == 3){
            $smsName = mb_substr($name,0,1,'utf-8') . "**";
            return $smsName;
        }else if($hisNameLength == 4){
            $smsName = mb_substr($name,0,1,'utf-8') . "***";
            return $smsName;
        }else{
            $smsName = "您好";
            return $smsName;
        }
    }



    //数据库写入/读取function

    /**
     * @param $key
     * 一定传入数组，哪怕只有一个,如全部需要请传“getAll”
     * @return bool|mixed
     * 数据库读取
     */
    public function dbRead($key){
        $this->startDbConn();
        $sql = "SELECT * FROM temp WHERE Username = '$this->username'";
        $result = mysqli_query($this->con,$sql);
        if (!$result) {
            $this->feedback("fail","db error");
            exit();
        }
        $this->endDbConn();
        while($row = mysqli_fetch_array($result)){
            if ($key == "getAll"){
                return $row;
            }
            $dbResult = array();
            foreach ($key as $each){
                $dbResult["$each"] = $row["$each"];
            }
            return $dbResult;
        }
        return false;
    }

    /**
     * @param $keyValue
     * 请一定传入键值对数组,key是数据库字段，value是写入的值
     * @return bool|mixed
     * 数据库写入
     */
    public function dbWrite($keyValue){
        $this->startDbConn();
        foreach ($keyValue as $key=>$value){
            $sql = "UPDATE temp SET $key = '$value' WHERE temp.Username = '$this->username'";
            $result = mysqli_query($this->con,$sql);
            if (!$result) {
                $this->feedback("fail","db error");
                exit();
            }
        }
        $this->endDbConn();
        return true;
    }

    /**
     * @return bool
     * 清空其他追求者
     */
    public function clearAllOtherInviter(){
        if ($this->checkUsername($this->username)){
            $sql10 = "UPDATE temp SET partner_status = '0', partner_username = '', partner_name = '', partner_group_id = '' WHERE temp.partner_username = '$this->username'";
            $this->startDbConn();
            mysqli_query($this->con,$sql10);
            $this->endDbConn();
            return true;
        }
        return false;
    }

    /**
     * @param $groupId
     * 要加入的组ID
     * @param $loginID
     * 登录ID
     */
    public function joinGroup($groupId, $loginID){
        $ps = $this->getPartnerStatus();
        $n = $this->getName();
        $u = $this->getUsername();
        $timestamp = time();
        $this->dbInsert("in_pair_log",["StatusAfter"=>"1", "StatusBefore"=>"{$ps}", "operater_name"=>"{$n}", "operater_username"=>"{$u}", "target_name"=>"na", "target_username"=>"$groupId", "timestamp"=>"$timestamp", "login_id"=>"$loginID", "action_type"=>"joinPartnerGroup"]);
        //改status
        $this->dbWrite(["partner_status"=>"1","partner_group_id"=>"$groupId"]);
    }

    /**
     * @param $targetUsername
     * 要配对的人的用户名
     * @param $targetName
     * 要配对的人的姓名
     * @param $groupId
     * 生产的组号
     * @param $loginID
     * 登录ID
     */
    public function pairWith($targetUsername, $targetName, $groupId, $loginID){
        $ps = $this->getPartnerStatus();
        $n = $this->getName();
        $u = $this->getUsername();
        $timestamp = time();
        $this->dbWrite(["partner_status"=>"1","partner_username"=>"$targetUsername","partner_name"=>"$targetName","partner_group_id"=>"$groupId"]);
        $this->dbInsert("in_pair_log",["StatusAfter"=>"1", "StatusBefore"=>"{$ps}", "operater_name"=>"{$n}", "operater_username"=>"{$u}", "target_name"=>"$targetName", "target_username"=>"$targetUsername", "timestamp"=>"$timestamp", "login_id"=>"$loginID", "action_type"=>"registerConfirm"]);
    }

    /**
     * @param $targetUsername
     * 要配对的人的用户名
     * @param $targetName
     * 要配对的人的姓名
     * @param $loginID
     * 登录ID
     */
    public function singlePairWith($targetUsername, $targetName, $loginID){
        $ps = $this->getPartnerStatus();
        $n = $this->getName();
        $u = $this->getUsername();
        $timestamp = time();
        $this->dbWrite(["partner_status"=>"2","partner_username"=>"$targetUsername","partner_name"=>"$targetName"]);
        $this->dbInsert("in_pair_log",["StatusAfter"=>"2", "StatusBefore"=>"{$ps}", "operater_name"=>"{$n}", "operater_username"=>"{$u}", "target_name"=>"$targetName", "target_username"=>"$targetUsername", "timestamp"=>"$timestamp", "login_id"=>"$loginID", "action_type"=>"registerWithNoConfirm"]);
    }

    /**
     * @param $publicPairRequest
     * 配对需求
     * @param $loginID
     * 登录id
     */
    public function joinPublicPair($publicPairRequest, $loginID){
        $ps = $this->getPartnerStatus();
        $n = $this->getName();
        $u = $this->getUsername();
        $g = $this->getGender();
        $timestamp = time();
        $this->dbWrite(["partner_status"=>"4"]);
        $this->dbInsert("in_pair_log",["StatusAfter"=>"4", "StatusBefore"=>"{$ps}", "operater_name"=>"{$n}", "operater_username"=>"{$u}", "timestamp"=>"$timestamp", "login_id"=>"$loginID", "action_type"=>"requestPublicPair"]);
        $this->dbInsert("public_pair_info",["Username"=>"$u", "name"=>"$n", "gender"=>"$g", "request_info"=>"$publicPairRequest", "timestamp"=>"$timestamp", "login_id"=>"$loginID", "quit_flag"=>"0"]);
    }

    /**
     * @param $tableNo
     * 传入要加入的桌号，10-43
     * @return string
     * 返回加入是否成功
     */
    public function joinTable($tableNo){
        if ($tableNo == "" || $tableNo == null){
            return "invalidParam";
        }
        $this->startDbConn();
        $sql = "SELECT * FROM tables WHERE table_no = '$tableNo'";
        $result = mysqli_query($this->con,$sql);
        while ($row = mysqli_fetch_array($result)){
            if ($row['remain_num'] > 0){
                if ($row['registered_num'] == "0"){
                    $name = $this->getName();
                    $this->startDbConn();
                    $sql = "UPDATE tables SET first_guy = '$name' WHERE tables.table_no = '$tableNo'";
                    mysqli_query($this->con,$sql);
                }
                $newRemain = $row['remain_num'] - 1;
                $newRegistered = $row['registered_num'] + 1;
                $sql = "UPDATE tables SET remain_num = '$newRemain' WHERE tables.table_no = '$tableNo'";
                mysqli_query($this->con,$sql);
                $sql0 = "UPDATE tables SET registered_num = '$newRegistered' WHERE tables.table_no = '$tableNo'";
                mysqli_query($this->con,$sql0);
                $this->endDbConn();
                $this->dbWrite(["table_no"=>"$tableNo"]);
                return "success";
            }
            $this->endDbConn();
            return "fullTable";
        }
        $this->endDbConn();
        return "wrongTableNumber";
    }

    /**
     * @return string
     * 退出桌子
     */
    public function quitTable(){
        $oldTableNo = $this->dbRead(["table_no"])['table_no'];
        $this->dbWrite(["table_no"=>""]);
        $this->startDbConn();
        $sql = "SELECT * FROM tables WHERE table_no = '$oldTableNo'";
        $result = mysqli_query($this->con,$sql);
        while ($row = mysqli_fetch_array($result)) {
            if ($row['registered_num'] > 0) {
                if ($row['registered_num'] == "1") {
                    $sql = "UPDATE tables SET first_guy = NULL WHERE tables.table_no = '$oldTableNo'";
                    mysqli_query($this->con, $sql);
                }
                $newRemain = $row['remain_num'] + 1;
                $newRegistered = $row['registered_num'] - 1;
                $sql = "UPDATE tables SET remain_num = '$newRemain' WHERE tables.table_no = '$oldTableNo'";
                mysqli_query($this->con, $sql);
                $sql0 = "UPDATE tables SET registered_num = '$newRegistered' WHERE tables.table_no = '$oldTableNo'";
                mysqli_query($this->con, $sql0);
                $this->endDbConn();
                return "success";
            }
        }
        $this->endDbConn();
        return "fail";
    }

    /**
     * 腾讯短信接口对接函数
     * @param $template
     * 腾讯短信模板编号
     * @param $extraParams
     * 除了首个打码姓名外的其他参数，如无多余函数请传空数组
     * @param $operatorUsername
     * 操作者用户名
     * @param $loginID
     * 操作者loginID
     */
    function tencentSend($template, $extraParams, $operatorUsername, $loginID){
        $timestamp = time();
        $key = '';
        $secret = '';
        // 腾讯云短信发送短信需要指定应用id
        $appid = '1400371610';
        $sign = 'NFLS2020Prom';
        // 可发送多个手机号，变量为数组即可，如：[11111111111, 22222222222]
        $mobileArr = $this->dbRead(["phone"]);
        $mobile = $mobileArr['phone'];
        if ($this->checkPhone($mobile) == false){
            $this->dbInsert("sms_log",["phone"=>"$mobile", "template_id"=>"000000", "target_username"=>"{$this->getUsername()}", "operator_username"=>"$operatorUsername", "login_id"=>"$loginID", "timestamp"=>"$timestamp"]);
            return;
        }
        // 腾讯云模板变量为索引数组，当你传入关联数组时会按顺序变为索引数组，如：['name' => '张三', 'code' => '123'] => ['张三', '123']
        $params = ["{$this->getNameMasked()}"];
        //如果是验证码，首项不是名字
        if ($template == '628214'){
            $params = [];
        }
        if (count($extraParams) != 0){
            foreach ($extraParams as $extraParam){
                array_push($params,$extraParam);
            }
        }
        $SessionContext = "userid";
        $sms = new TencentSms($key, $secret);
        // 需要注意，设置配置不分先后顺序，send后也不会清空配置
        $result = $sms->setAppid($appid)->setMobile($mobile)->setTemplate($template)->setSign($sign)->setParams($params)->setSessionContext($SessionContext)->send();
        /**
         * 返回值为bool，你可获得腾讯云响应做出你业务内的处理
         *
         * status bool 此变量是此包用来判断是否发送成功
         * code string 腾讯云短信响应代码
         * message string 腾讯云短信响应信息
         */
        $response = $sms->getResponse();
        $callback = array("Code"=>"{$response['Response']['SendStatusSet']['0']['Code']}","Message"=>"{$response['Response']['SendStatusSet']['0']['Message']}");
        $callbackJson = json_encode($callback);
        $smsName = implode(',',$params);
        $this->dbInsert("sms_log",["params"=>"$smsName","phone"=>"$mobile", "template_id"=>"$template", "target_username"=>"{$this->getUsername()}", "operator_username"=>"$operatorUsername","callback_message"=>"$callbackJson", "login_id"=>"$loginID", "timestamp"=>"$timestamp"]);
        return;
    }

}