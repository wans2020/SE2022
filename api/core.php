<?php

$debug = 0;
//初始化数据库
$db = new DB;

//任务队列
class TASK{
    //初始化task
    private $db;
    private $id;
    private $qq;
    public function __construct($qq)
    {
        global $db;
        $this->db = $db;
        $this->qq = $qq;
    }

    //创建task

    function createTask($command,$var,$show_name){
        if ($this->db->sql("insert into tasks(qq,task_command,task_var,task_show_name) values('{$this->qq}','{$command}','{$var}','{$show_name}')")){
            $this->id = $this->db->get_insert_id();
            return array("Status"=> 0 , "msg" => "创建任务成功","ID"=>$this->id);
        }else{
            return array("Status"=> -1 , "msg" => "创建任务失败");
        }
    }


    //删除task
    function destroyTask(){

    }





}

class GAME{
    private $db;
    public function __construct()
    {
        global $db;
        $this->db = $db;
    }
    public function game_list(){
        return array("Status"=> 0, "msg" => "获取成功" , "Data"=> $this->db->get_all("select * from games"));
    }
    public function game_server_list($game_id){
        if (is_numeric($game_id)) return array("Status"=> 0, "msg" => "获取成功" , "Data"=> $this->db->get_all("select * from game_servers where game_id=" .  $game_id));
        return null;
    }
    public function game_version_list($server_id){
        if (is_numeric($server_id)) return array("Status"=> 0, "msg" => "获取成功" , "Data"=> $this->db->get_all("select * from game_versions where game_server_id=" . $server_id));
        return null;

    }
    public function get_game_info($game_id,$server_id,$version_id){
        if (is_numeric($game_id) && is_numeric($server_id)&& is_numeric($version_id) ) {
            if ($game_info = $this->db->get("select * from games natural join game_servers natural join game_versions where game_id={$game_id} and game_server_id={$server_id} and game_version_id={$version_id} " )){
                return array("Status"=> 0 , "msg" => "获取成功" , "Data"=>$game_info);
            }else {
                return null;
            }
        }
        return null;

    }

}

class INSTANCE{
    private $db;
    public function __construct()
    {
        global $db;
        $this->db = $db;
    }
    public function instance_list(){
        return array("Status" => 0 , "msg" => "获取成功", "Data" => $this->db->get_all("select * from instance"));
    }
}
//服务器信息
class SERVER{
    private $id;
    private $db;
    private $qq;
    //初始化
    public function __construct($qq,$id)
    {
        if (is_numeric($qq) && is_numeric($id)){
            $this->id = $id;
            global $db;
            $this->db = $db;
            $this->qq  = $qq;
        }

    }
    //判断是否所属
    function check_owner(){
        if ($this->db->get("select * from servers where server_id={$this->id} and qq={$this->qq}")){
            return true;
        }
        return false;
    }
    //创建空白服务器
    function Create($game_id,$game_server_id,$game_version_id,$instance_id){
        $game = new GAME;
        if ($game->get_game_info($game_id,$game_server_id,$game_version_id)){
                if ($instance_info = $this->db->get("select * from instance where instance_id={$instance_id}")){
                    $now = time();
                        if ($this->db->sql("insert into servers(qq,game_id,game_server_id,game_version_id,instance_id,starttime) values('{$this->qq}','{$game_id}','{$game_server_id}','{$game_version_id}','{$instance_id}','{$now}')")){
                            //创建服务器
                            $this->id = $this->db->get_insert_id();
                            $task = new TASK($this->qq);
                            $task->createTask("CREATESERVER",$this->id,"创建服务器");
                            return array("Status" => 0 ,"msg" =>  "新建服务器成功","NewID" => $this->id);
                        }else{
                            return array("Status"=> -3 , "msg" => "创建服务器失败");
                        }

                }else{
                    return array("Status"=> -2 , "msg" =>"实例编号不存在");
                }
            }else{
                return array("Status"=>-1, "msg"=> "没有找到对应的版本");
            }


    }
    //安装插件
    function InstallPlugin($pluginID){
        if ($this->db->get("select * from plugins where server_id=" . $pluginID )){
            $this->pluginManager->install($pluginID);
            $this->addPluginInfo($pluginID);
            return array("Status"=> 0 , "msg" => "插件安装成功");
        }else{
            return array("Status" => -1, "msg" => "找不到插件");
        }
    }
    function Start(){

        return array("Status"=> 0 , "msg" => "启动成功");
    }
    function Stop(){
        return array("Status"=>  0, "msg" => "关闭成功");
    }
    //设置管理员(OP)权限
    function SetOP($playerID){
        $this->Send("op " . $playerID);
        return array("Status"=>0,'msg' => "操作成功");

    }
    //设置备份
    function Backup(){
        if ($this->db->get("select backup from servers where server_id={$this->id}" )['backup']) {
            return array("Status"=>-1,'msg' => "已有备份");
        }else{
            $this->db->sql("update servers set backup=1 where server_id=" .$this->id  );
            $this->pt->backup($this->id);

            return array("Status"=>0,'msg'=> "操作成功");
        }
    }
    //设置还原
    function Restore(){
        if ($this->db->get("select backup from servers where server_id={$this->id}")['backup']){
            $this->pt->restore($this->id);
            return array("Status"=>0,"msg" => "操作成功");
        }else{
            return array("Status"=>-1,"msg" => "没有备份存在");
        }
    }
    //删除备份
    function DelBackup(){
        if ($this->db->get("select backup from servers where server_id={$this->id} limit 1")['backup']){
            //删除备份
            $this->pt->deleteBackup($this->id);

            if ($this->db->sql("update servers set backup=0 where server_id=" . $this->id)){
                return array("Status"=>0,"msg" => "操作成功");

            }else{
                return array("Status"=> -1 , "msg" => "未知错误");
            }
        }else{
            return array("Status"=>-1,"msg" => "没有备份存在");
        }
    }
    //删除服务器
    function Destroy(){


        $this->db->sql("delete from servers where server_id=" . $this->id);
        return array("Status"=> 0 , "msg"  => "销毁服务器成功");

    }
    //修改服务器版本
    function ChangeGame($new_game_id , $new_game_server_id , $new_game_version_id){
        $game = new GAME;
        if ($game_info = $game->get_game_info($new_game_id,$new_game_server_id, $new_game_version_id)){
            //开始更换版本
            $this->db->sql("update servers set game_id={$new_game_id},game_server_id={$new_game_server_id},game_version_id={$new_game_version_id} where server_id=" . $this->id);
            return array("Status"=> 0 , "msg" => "修改游戏成功");
        }else{
            return array("Status" => -1 , "msg" => "修改游戏失败");
        }

    }
    //修改服务器配置
    function ChangeInstance($arr){

    }
    //暂停服务器
    function Suspend(){

        return array("Status" => 0  , "msg" => "暂停成功");
    }
    //解除暂停服务器
    function UnSuspend(){
        return array("Status" => 0  , "msg" => "解除暂停成功");
    }
    //获取服务器信息
    function Info(){
        $db_info = $this->db->get("select * from servers where server_id=". $this->id);
        return array("Status" => 0 ,"msg" => "获取信息成功" ,"DB_Info" => $db_info);

    }


}


//用户信息
class USER{
    private $db;
    private $qq;

    public function __construct()
    {
        global $db;
        $this->db = $db;
    }
    function get_qq(){
        return $this->qq;
    }
    //注册
    function reg($qq,$passwd){

        if (is_numeric($qq)){
            $arr['passwd'] = aisql($passwd );
            if (ctype_alnum($passwd) && strlen($passwd ) >= 6){

                $now = time();
                if ($this->db->sql("insert into users(qq,passwd,regtime) values('{$qq}','{$passwd}','{$now}')")){
                    return array("Status"=>0,'msg'=>'注册成功');
                }else{
                    return array("Status"=>-2,'msg'=>'已经注册过，无需再次注册');
                }



            }else{
                return array("Status"=> 1 , "msg"=>'密码不正确');
            }

        }else{
            return array("Status"=>-1,'msg'=>'QQ号不正确');
        }

    }
    //登录
    function login($qq,$passwd,$long){
        //参数表
        if (is_numeric($qq)){

            if (ctype_alnum($passwd) && strlen($passwd) >= 6){
                if ($this->db->get("select * from users where qq='{$qq}' and passwd='{$passwd}'")){
                    if ($long){
                        return array("Status"=>0,'msg'=>'登录成功','Cookie' => AESEncode($qq . "'" . $passwd . "'" . (time() + 3600)));
                    }else{
                        return array("Status"=>0,'msg'=>'登录成功','Cookie' => AESEncode($qq . "'" . $passwd . "'" . (time() + 86400000)));
                    }
                }
            }
            }
        return array("Status"=>-1,'msg'=>'用户名或密码错误');
    }
    //用户信息
    function info(){
        if (!isset ($this->qq)) return array("Status"=>-1,'msg'=>'没有登录');
        $result['Servers'] = $this->db->get_all("select * from servers where qq='{$this->qq}'");
        $result['User'] = $this->db->get("select * from users where qq='{$this->qq}'");
        $result['Status'] = 0 ;
        $result['msg'] = "查询成功";
        return $result;

    }
    //校验Cookie
    function check($cookie){
        $data  =  AESDecode($cookie);
        if ($data != null && $data != ""){
            //解密成功
            deprint($data);

            $split = explode("'",$data);
            $qq = $split[0];
            $passwd = $split[1];
            $end_time = $split[2];
            if ($end_time > time() && $this->login($qq,$passwd,0)['Status'] == 0){
                //成功解密
                $this->qq = $qq;
                return array("Status"=>0,'msg'=>'登录成功','End_time' => $end_time);
            }else{
                return array("Status"=>-100,'msg'=>'登录失败');
            }
        }
        return array("Status"=>-101,'msg'=>'登录失败');
    }
    //更改积分
    function change_point($point,$reason){
        if ($user_info = $this->db->get("select * from users where qq=" . $this->qq)){
            if ($user_info['point'] > -1* $point ){
                //正常
                if ($this->db->sql("update users set point=point+{$point} where qq={$this->qq}")){
                    $this->db->sql("insert into change_history(qq,point,reason) values('{$this->qq}','{$point}','{$reason}')");
                    return array("Status"=> 0 , "msg" => "变动积分成功");
                }else{
                    return array("Status" => -3 , "msg" => "积分变动失败");
                }
            }else{
                //异常
                return array("Status" => -2 , "msg" => "积分不足");
            }
        }else{
            return array("Status" => -1 , "msg" => "用户不存在");
        }


    }



}

//数据库相关操作
class DB{
    private $conn;
    public function __construct()
    {
        $this->conn = new mysqli("newtest.simpfun.cn","sfe","a6L4jPXnjrNwNxwY","sfe",3306);
        if ($this->conn->connect_error) {
            die("连接失败: " . $this->conn->connect_error);
        }

    }
    public function get_insert_id() : int
    {
        return mysqli_insert_id($this->conn);
    }
    public function sql($sql): bool
    {
        deprint("SQL:" . $sql);

        if ($this->conn->query($sql) === TRUE){
            return true;
        }else{
            deprint("Error: " . $sql . "<br>" . $this->conn->error );

            return false;
        }
    }
    public function get($sql): array|null
    {
        deprint("GET_SQL:" . $sql);

        $result = $this->conn->query($sql .  " limit 1");

        if ($result->num_rows > 0) {
            // 输出数据
            return $result->fetch_assoc();

        } else {
            return null;
        }
    }
    public function get_all($sql) : array|null
    {
        deprint("GET_ALL_SQL:" . $sql);

        $result = $this->conn->query($sql);

        if ($result->num_rows > 0) {
            // 输出数据
            $re = array();
            $i = 0;
            while($row = $result->fetch_assoc()) {
                $re[$i++] = $row;
            }
            return $re;
        } else {
            return null;
        }
    }

}


//AES加解密
function AESDecode($data){
    $keyStr = '5Ic1234563nSptdr';
    return (openssl_decrypt(base64_decode($data), 'AES-128-ECB', $keyStr)); // options 默认值 0 表示 base64输出

}
function AESEncode($data){
    $keyStr = '5Ic1234563nSptdr';
    return base64_encode(openssl_encrypt($data, 'AES-128-ECB', $keyStr)); // options 默认值 0 表示 base64输出
}

//防止sql注入
function aisql($str){
    $str = str_replace("'" , '',$str);
    $str = str_replace('"','',$str);
    return str_replace('%','',$str);
}

//调试输出
function deprint($str){
    global $debug;

    if ($debug){
        echo "<br>[DEBUG]  <strong>";
        if (is_array($str)){
          echo "<pre>";
          print_r($str);
        }else{
            echo $str;
        }

        echo "<br></strong>";
    }
}
//api返回数据
function api_return($str){
    if ($str['Status'] <= -100 ) {
        http_response_code(401);
    }else if ($str['Status'] <0){
        http_response_code(400);
    }
    echo json_encode($str);
    die();

}