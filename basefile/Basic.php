<?php


/**
 * Class Basic
 */
class Basic
{
    /**
     * @var
     * 每次访问生产的全局唯一id
     */
    private $action_uuid;

    /**
     * @var string
     * 数据库地址
     */
    private $dbHost = 'localhost';

    /**
     * @var string
     * 数据库用户名
     */
    private $dbUsername = '';

    /**
     * @var string
     * 数据库密码
     */
    private $dbPwd = '';

    /**
     * @var string
     * 数据库名
     */
    private $dbName = 'PROM';

    /**
     * @var
     *  数据库链接变量
     */
    protected $con;
    /**
     * @param $status
     * 状态值：success/fail
     * @param $reason
     * 原因
     */
    public function feedback($status, $reason){
        $json_arr = array("status"=>"$status","reason"=>"$reason");
        $json_obj = json_encode($json_arr);
        $json_forSave = json_encode($json_arr,JSON_UNESCAPED_UNICODE);
        $this->feedbackRecord($json_forSave);
        echo $json_obj;
        return;
}

    /**
     * @param $keyValue
     * 传入数组，第一个键值对一定是status
     */
    public function generalFeedback($keyValue){
        $json_obj = json_encode($keyValue);
        $json_forSave = json_encode($keyValue,JSON_UNESCAPED_UNICODE);
        $this->feedbackRecord($json_forSave);
        echo $json_obj;
        return;
}

    /**
     * @param $json
     * 传入保存返回参数
     * @return bool
     */
    private function feedbackRecord($json){
        $this->startDbConn();
        $sql = "UPDATE api_log SET feedback = '$json' WHERE api_log.action_uuid = '$this->action_uuid'";
        mysqli_query($this->con,$sql);
        $this->endDbConn();
        return true;
    }

    /**
     * @param $uuid
     * @return bool
     * 检查uuid合法性
     */
    public function checkUuid($uuid ){
        $regx = "/^[A-F0-9]{8}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{12}$/";
        if(!preg_match($regx, $uuid)){
            return FALSE;
        }else{
            return TRUE;
        }
    }

    /**
     * @param $username
     * @return bool
     * 检查用户名合法性
     */
    public function checkUsername($username ){
        $username = strtoupper($username);
        $regx = "/^(G)\d{8}$/";
        if(!preg_match($regx, $username)){
            return FALSE;
        }else{
            return TRUE;
        }
    }

    /**
     * @param $phone
     * @return bool
     * 检查手机号合法性
     */
    public function checkPhone($phone ){
        $regx = "/^1[3456789]{1}\d{9}$/";
        if(!preg_match($regx, $phone)){
            return FALSE;
        }else{
            return TRUE;
        }
    }

    /**
     * @param $code
     * @return bool
     * 检查短信验证码合法性
     */
    public function checkSmsCode($code ){
        $regx = "/^\d{5}$/";
        if(!preg_match($regx, $code)){
            return FALSE;
        }else{
            return TRUE;
        }
    }

    /**
     * @param $email
     * @return bool
     * 检查电子邮箱合法性
     */
    public function checkEmail($email){
        if (filter_var($email, FILTER_VALIDATE_EMAIL)){
            return true;
        }else{
            return false;
        }
    }

    /**
     * @param $name
     * @return bool
     * 检查姓名合法性
     */
    public function checkName($name){
        $regx = "/^([\xe4-\xe9][\x80-\xbf]{2}){2,4}$/";
        if(!preg_match($regx, $name))
        {
            return FALSE;
        }else{
            return TRUE;
        }
    }

    /**
     * @param $id
     * @return bool
     * 通用密码验证
     */
    public function checkPwd($id ){
        $id = strtoupper($id);
        $regx = "/^[A-Za-z0-9]{6,16}$/";
        if(!preg_match($regx, $id))
        {
            return FALSE;
        }else{
            return TRUE;
        }
    }

    /**
     * @param $id
     * @return bool
     * 新密码验证
     */
    public function checkNewPwd($id ){
        $id = strtoupper($id);
        $regx = "/^[A-Za-z0-9]{10,16}$/";
        if(!preg_match($regx, $id))
        {
            return FALSE;
        }else{
            return TRUE;
        }
    }


    /**
     * @param $uid
     * @return bool
     * 检查舞伴组id
     */
    public function checkPgid($uid ){
        $regx = "/^[A-F0-9]{12}$/";
        if(!preg_match($regx, $uid)){
            return FALSE;
        }else{
            return TRUE;
        }
    }

    /**
     * @param $id
     * @return bool
     * 验证身份证号
     */
    public function checkIdCard($id ){
        $id = strtoupper($id);
        $regx = "/^\d{17}([0-9]|X)$/";
        if(!preg_match($regx, $id))
        {
            return FALSE;
        }else{
            return TRUE;
        }
    }

    /**
     * @return mixed
     * 获取客户端访问ip
     */
    public function getip() {
        static $ip = '';
        $ip = $_SERVER['REMOTE_ADDR'];
        if(isset($_SERVER['HTTP_CDN_SRC_IP'])) {
            $ip = $_SERVER['HTTP_CDN_SRC_IP'];
        } elseif (isset($_SERVER['HTTP_CLIENT_IP']) && preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/', $_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif(isset($_SERVER['HTTP_X_FORWARDED_FOR']) AND preg_match_all('#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#s', $_SERVER['HTTP_X_FORWARDED_FOR'], $matches)) {
            foreach ($matches[0] AS $xip) {
                if (!preg_match('#^(10|172\.16|192\.168)\.#', $xip)) {
                    $ip = $xip;
                    break;
                }
            }
        }
        return $ip;
    }

    /**
     * @return string
     * 返回cookie
     */
    public function getCookieString(){
        return json_encode($_COOKIE,JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return string
     * 返回所有post参数
     */
    public function getAllPostFields(){
        return json_encode($_POST,JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param $array
     * @return string
     * 数组转字符串
     */
    public function array2string($array){
        $string = [];
        if($array && is_array($array)){
            foreach ($array as $key=> $value){
                $string[] = $key.'=>'.$value;
            }
        }
        return implode(',',$string);
    }

    /**
     *api访问记录
     */
    public function apiLog(){
        $uuid = $this->guid();
        $this->action_uuid = $uuid;
        $apiName = str_replace("/","",$_SERVER['REQUEST_URI']);
        $ip = $this->getip();
        $post = $this->getAllPostFields();
        $ua = $_SERVER['HTTP_USER_AGENT'];
        $cookie = $this->getCookieString();
        $time = time();
        $this->dbInsert("api_log",["action_uuid"=>"$uuid","api_name"=>"$apiName","ip_add"=>"$ip","timestamp"=>"$time","post"=>"$post","UA"=>"$ua","cookie"=>"$cookie"]);
    }

    /**
     * @return string
     * 返回一个新的uuid
     */
    public function guid(){
        if (function_exists('com_create_guid')){
            return com_create_guid();
        }else{
        mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
        $charid = strtoupper(md5(uniqid(rand(), true)));
        $hyphen = chr(45);// "-"
        $uuid = substr($charid, 0, 8).$hyphen
            .substr($charid, 8, 4).$hyphen
            .substr($charid,12, 4).$hyphen
            .substr($charid,16, 4).$hyphen
            .substr($charid,20,12);
        return $uuid;
}
}

    /**
     * @return void 建立数据库连接
     * 建立数据库连接
     */
    protected function startDbConn(){
        $this->con = mysqli_connect("$this->dbHost","$this->dbUsername","$this->dbPwd","$this->dbName");
        return;
    }

    /**
     * 关闭数据库连接
     */
    protected function endDbConn(){
        mysqli_close($this->con);
        return;
    }

    /**
     * @return false|mysqli
     * 返回一个数据库连接
     */
    public function getDbConn(){
        return mysqli_connect($this->dbHost,$this->dbUsername,$this->dbPwd,$this->dbName);
    }

    /**
     * @param $tableName
     * 数据表名
     * @param $keyValue
     * 以键值对数组传入写入内容
     * @return bool|mysqli_result
     */
    public function dbInsert($tableName, $keyValue){
        $keyArr = [];
        $valueArr = [];
        foreach ($keyValue as $key=>$value) {
            array_push($keyArr,$key);
            array_push($valueArr,$value);
        }
        $keySql = implode(",",$keyArr);
        $valueSql = implode("','",$valueArr);
        $sql = "INSERT INTO $tableName ($keySql) VALUES ('$valueSql')";
        $this->startDbConn();
        $result = mysqli_query($this->con,$sql);
        $this->endDbConn();
        return $result;
    }
}