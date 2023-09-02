<?php

function connect_db(){
    $param = 'mysql:dbname='.DB_NAME.';host='.DB_HOST;
    $pdo = new PDO($param, DB_USER, DB_PASSWORD);
    $pdo->query('SET NAMES utf8;');
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
}

function time_format_dw($date){
    $format_date = NULL;
    $week = array('日','月', '火', '水', '木', '金', '土');

    if($date){
        $format_date = date('j('.$week[date('w',strtotime($date))].')', strtotime($date));
    }

    return $format_date;
}

function format_time($value) {
    if (!$value || $value == '00:00:00') {
        return NULL;
    } else {
        return date('H:i', strtotime($value));
    }
}

//htmlエスケープ処理
function h($original_str){
    return htmlspecialchars($original_str, ENT_QUOTES, 'UTF-8');
}

//トークンを発行する処理
function set_token(){
    $token = sha1(uniqid(mt_rand(), true));
    $_SESSION['CSRF_TOKEN'] = $token;
}

//トークンをチェックする処理
function check_token(){
    if(empty($_SESSION['CSRF_TOKEN']) || ($_SESSION['CSRF_TOKEN'] != $_POST['CSRF_TOKEN'])){
        unset($pdo);
        header('Location: /error.php');
        exit;
    }
}

//時間の形式チェックを行う
function check_time_format($time){
    if(preg_match('/([01]?[0-9]|2[0-3]):([0-5][0-9])$/', $time)){
       return true; 
    }else{
        return false;
    }
}

//指定されたPHPへリダイレクトする
function redirect($path){
    unset($pdo);
    header('Location: '.$path);
    exit;
}
?>