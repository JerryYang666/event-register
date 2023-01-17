<?php
require_once 'basefile/Basic.php';

/**
 * Class Exoperson
 */
class Exoperson extends Basic
{
    /**
     * @var string|void
     *新生成的用户名
     */
    private $newUsername;

    /**
     * @return string|void
     */
    public function getNewUsername()
    {
        return $this->newUsername;
    }

    /**
     * Exoperson constructor.
     * @param $loginID
     * 登录id
     * @param $name
     * 被邀请人姓名
     * @param $idCard
     * 被邀请人身份证
     * @param $invitor
     * 邀请人学号
     */
    function __construct($loginID, $name , $idCard, $invitor)
    {
        $newUsername = $this->getExoCount();
        $this->newUsername = $newUsername;
        $time = time();
        $this->dbInsert("exo_list",["Username"=>"$newUsername", "name"=>"$name", "id_card"=>"$idCard", "inviter"=>"$invitor", "operate_id"=>"$loginID", "create_time"=>"$time"]);
        $pwd = $this->genPwd($idCard);
        $gender = $this->getGenderById($idCard);
        $this->dbInsert("temp",["Username"=>"$newUsername", "pwd"=>"$pwd", "name"=>"$name", "gender"=>"$gender", "first_login_flag"=>'0', "activate"=>'0', "deposit_status"=>'0', "partner_status"=>'0']);
    }

    /**
     * @return string|void
     * 获取当前被邀请人计数
     */
    private function getExoCount(){
        $this->startDbConn();
        $sql3 = "SELECT *  FROM parameters WHERE name = 'exo_count'";
        $result3 = mysqli_query($this->con,$sql3);
        while($row3 = mysqli_fetch_array($result3)){
            $newUsername = 'G2020' . $row3['parameter'];
            $newCount = intval($row3['parameter']) + 1;
            $sql4 = "UPDATE parameters SET parameter = '$newCount' WHERE parameters.ID = 1";
            mysqli_query($this->con,$sql4);
            $this->endDbConn();
            return $newUsername;
        }
        $this->endDbConn();
        return;
    }

    /**
     * @param $partnerIdCard
     * 身份证号
     * @return false|string|null
     * 生成密码密文
     */
    private function genPwd($partnerIdCard){
        $idcard1 = substr($partnerIdCard,12,6);
        return password_hash($idcard1, PASSWORD_BCRYPT, ["cost" => 8]);
    }

    /**
     * @param $id
     * 身份证号
     * @return int
     * 身份证号码获取性别
     */
    private function getGenderById($id){
        if (intval(substr($id,16,1)) % 2 == 0){
            return 0;
        }else{
            return 1;
        }
    }
}