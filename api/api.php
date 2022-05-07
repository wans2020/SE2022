<?php
include ("core.php");
$action = $_REQUEST['action'];
$user =new USER;
if ($action == "reg"){
    api_return($user->reg($_REQUEST['qq'],$_REQUEST['passwd']));
}else if ($action == "login"){
    api_return($user->login($_REQUEST['qq'],$_REQUEST['passwd'],$_REQUEST['long']));
}
$cookie = $_COOKIE["token"];
$response = $user->check($cookie);
if ($response['Status'] != 0 ){
    api_return($response);
}
//以下都是通过验证的

//个人信息
if ($action == "user_info"){
    api_return($user->info());
}

//游戏选择与实例规格选择
if ($action == "get_games"){
    $game = new GAME;
    api_return($game->game_list());
}else if ($action == "get_game_servers"){
    $game = new GAME;
    api_return($game->game_server_list($_REQUEST['game_id']));
}else if ($action == "get_game_versions"){
    $game = new GAME;

    api_return($game->game_version_list($_REQUEST['game_server_id']));
}else if ($action == "get_instances"){
    $instance = new INSTANCE;
    api_return($instance->instance_list());

}

//服务器相关
if ($action == "create_server"){
    $server = new SERVER($user->get_qq(),0);
    api_return(($server->Create($_REQUEST['game_id'],$_REQUEST['game_server_id'],$_REQUEST['game_version_id'],$_REQUEST['instance_id'])));
}
$server_id = $_REQUEST['server_id'];
$server = new SERVER($user->get_qq(),$server_id);

if (!$server->check_owner()){
   api_return(array("Status"=> -100, "msg" => "您无权管理此服务器"));
}

if ($action == "start_server"){
    api_return ($server->Start());
}else if ($action == "stop_server"){
    api_return ($server->Stop());
}else if ($action == "suspend_server"){
   api_return($server->Suspend());
}else if ($action == "unsuspend_server"){
    api_return($server->UnSuspend());
}else if ($action == "set_op"){
    api_return($server->SetOP($_REQUEST['player_id']));
}else if ($action == "server_info"){
    api_return ($server->Info());
}else if ($action == "destroy_server"){
    api_return($server->Destroy());
}



