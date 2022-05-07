<?php
include ("core.php");
$user = new USER;
$qq = rand(10000,1000000);
$passwd = rand(10000000,99999999);
//注册
$user->reg($qq,$passwd);
//登录,获得cookie
$cookie = $user->login($qq,$passwd,1)['Cookie'];
deprint($cookie);

//使用cookie登录
$user->check($cookie);
//登录完成后显示info
deprint($user->info());
deprint($user->change_point("10000","测试"));
//再次显示info
deprint($user->info());
$server = new SERVER($qq,0);
deprint($server->Create("1","1","1","1"));
deprint($server->Info());
deprint($server->ChangeGame("1","1","2"));
deprint($server->Info());


$game = new GAME();
deprint($game->game_list());
deprint($game->game_server_list(1));
deprint($game->game_version_list(1));
