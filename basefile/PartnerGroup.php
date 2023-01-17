<?php
require_once 'Person.php';
require_once 'Basic.php';

/**
 * Class PartnerGroup
 */
class PartnerGroup extends Basic
{
    /**
     * @param $num
     * 传入需要获取的成员编号
     * @return mixed
     */
    public function getMember($num)
    {
        return $this->member["$num"];
    }

    /**
     * @var
     * 组内成员，array
     */
    private $member;

    /**
     * @var false|string
     * 组ID
     */
    private $groupId;

    /**
     * @var
     * 组建议到达时间编号
     */
    private $arriveTimeNo;

    /**
     * @return mixed
     * 获取该组建议到达时间
     */
    public function getArriveTime()
    {
        if ($this->arriveTimeNo != "" && $this->arriveTimeNo != null){
            return $this->arriveNo2Time($this->arriveTimeNo);
        }else{
            return false;
        }
    }

    /**
     * @return false|string
     */
    public function getGroupId()
    {
        return $this->groupId;
    }

    /**
     * @var
     * 组内人数
     */
    private $memberNum;

    /**
     * @return PartnerGroup
     * 更新组人数
     */
    private function setMemberNum()
    {
        $this->memberNum = count($this->member);
        return $this;
    }

    /**
     * @param mixed $groupId
     * @return PartnerGroup
     * 设置组ID
     */
    private function setGroupId($groupId)
    {
        $this->groupId = $groupId;
        return $this;
    }

    /**
     * PartnerGroup constructor.
     * @param $partnerGroupId
     * 如果要创建新组，请传入操作者uuid，如果读取旧组，请传入组ID
     */
    function __construct($partnerGroupId)
    {
        if ($this->checkUuid($partnerGroupId)){
            //生成新组
            $this->groupId = $this->pgid();
            $this->createNewGroup($partnerGroupId);
        }elseif ($this->checkPgid($partnerGroupId)){
            $this->setGroupId($partnerGroupId);
            $select = $this->dbSelect();
            if ($select == false){
                $this->feedback("fail","舞伴组ID不存在");
                exit();
            }
        }else{
            $this->feedback("fail","舞伴组ID格式错误");
            exit();
        }
    }


    /**
     * @param $username
     */
    public function addMember($username){
        $newMember = new Person($username,"username");
        if ($newMember->checkAccountStat() == true && $newMember->checkPairAvailability() == true){
            $this->setMemberNum();
            $this->member["$this->memberNum"] = $username;
            $this->dbUpdate();
        }else{
            $this->feedback("fail","账户已经绑定了舞伴");
            exit();
        }
    }

    /**
     * @param $loginID
     */
    private function createNewGroup($loginID){
        $time = time();
        $this->dbInsert("partner_group",["group_id"=>"{$this->groupId}", "group_member"=>'{"memberNum":"0"}', "operate_id"=>"$loginID", "create_time"=>"$time"]);
        $this->member = [];
    }

    /**
     * @return false|string
     */
    private function pgid(){
        mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
        $charid = strtoupper(md5(uniqid(rand(), true)));
        return substr($charid,20,12);
}

    /**
     * @param $arriveTimeNo
     * 传入到达时间编号，返回时间
     * @return bool|mixed
     */
    private function arriveNo2Time($arriveTimeNo){
        if ($arriveTimeNo == "" || $arriveTimeNo == null){
            return false;
        }else{
            $this->startDbConn();
            $sql = "SELECT * FROM arrive_time WHERE arrive_time_no = '$arriveTimeNo'";
            $result = mysqli_query($this->con,$sql);
            $this->endDbConn();
            while($row = mysqli_fetch_array($result)){
                return $row['arrive_time'];
            }
            return false;
        }
    }

    /**
     * @param $timeSection
     * 传入选择的时间区间a/b/c
     * @return bool|mixed|string|void
     * 返回分配到的时间，或者分配不成功的原因
     */
    public function allotArriveTime($timeSection){
        if ($timeSection == "" || $timeSection == null){
            return "invalidParam";
        }
        if ($this->arriveTimeNo == "" || $this->arriveTimeNo == null){
            $this->startDbConn();
            $sql = "SELECT * FROM arrive_time WHERE arrive_time_section = '$timeSection'";
            $result = mysqli_query($this->con,$sql);
            while ($row = mysqli_fetch_array($result)){
                if ($row['remain_num'] > 0){
                    $newRemain = $row['remain_num'] - 1;
                    $newRegistered = $row['registered_num'] + 1;
                    $timeNo  = $row['arrive_time_no'];
                    $sql = "UPDATE arrive_time SET remain_num = '$newRemain' WHERE arrive_time.arrive_time_no = '$timeNo'";
                    mysqli_query($this->con,$sql);
                    $sql0 = "UPDATE arrive_time SET registered_num = '$newRegistered' WHERE arrive_time.arrive_time_no = '$timeNo'";
                    mysqli_query($this->con,$sql0);
                    $sql1 = "UPDATE partner_group SET arrive_time_no = '$timeNo' WHERE partner_group.group_id = '$this->groupId'";
                    mysqli_query($this->con,$sql1);
                    $this->endDbConn();
                    return $this->arriveNo2Time($timeNo);
                }
            }
            $this->endDbConn();
            return "noAvailable";
        }else{
            $this->endDbConn();
            return "alreadyAllotted";
        }
    }

    /**
     * @return string
     * 返回可用的到达时间区间代码
     */
    public function checkArriveTimeAvail(){
        $this->startDbConn();
        $avail = "";
        $checkCont = ["a","b","c"];
        foreach ($checkCont as $item) {
            $sql = "SELECT * FROM arrive_time WHERE arrive_time_section = '$item'";
            $result = mysqli_query($this->con,$sql);
            while ($row = mysqli_fetch_array($result)){
                if ($row['remain_num'] > 0){
                    $avail = $avail . $item;
                    break;
                }
            }
        }
        $this->endDbConn();
        return $avail;
    }

    /**
     * @return bool
     */
    private function dbUpdate()
    {
        $this->startDbConn();
        $this->setMemberNum();
        $sqlArray = ["memberNum"=>"{$this->memberNum}"];
        foreach ($this->member as $key=>$value){
            $sqlArray["$key"] = $value;
        }
        $partnerGroupJson = json_encode($sqlArray);
        $sql = "UPDATE partner_group SET group_member = '$partnerGroupJson' WHERE partner_group.group_id = '$this->groupId'";
        $result = mysqli_query($this->con,$sql);
        if (!$result) {
            $this->feedback("fail","db error");
            exit();
        }
        $this->endDbConn();
        return true;
    }

    /**
     * @return bool
     */
    private function dbSelect()
    {
        $this->startDbConn();
        $sql = "SELECT * FROM partner_group WHERE group_id = '$this->groupId'";
        $result = mysqli_query($this->con,$sql);
        if (!$result) {
            $this->feedback("fail","db error");
            exit();
        }
        $this->endDbConn();
        while($row = mysqli_fetch_array($result)){
            $this->arriveTimeNo = $row['arrive_time_no'];
            $keyValue = json_decode($row['group_member'],TRUE);
            $this->member = [];
            foreach ($keyValue as $key=>$value){
                if ($key != "memberNum") {
                    $this->member["$key"] = $value;
                }
                if ($key == "0"){
                    $this->member["0"] = $value;
                }
            }
            $this->setMemberNum();
            return true;
        }
        $this->member = [];
        return false;
    }
}