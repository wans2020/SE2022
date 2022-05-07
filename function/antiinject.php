<?php

function ai($str){
$str = str_replace("'" , '',$str);
$str = str_replace('"','',$str);
return str_replace('%','',$str);
}