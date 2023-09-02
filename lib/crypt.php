<?php

$secret_key = ''; // セキュアなキーを選んでください。
$secret_iv = '';   // セキュアな初期ベクトルを選んでください。

// 暗号化関数
function encrypt($string, $key, $iv) {
    $encrypted = openssl_encrypt($string, 'AES-256-CBC', $key, 0, $iv);
    return base64_encode($encrypted);
}

// 復号化関数
function decrypt($encrypted_string, $key, $iv) {
    $decrypted = openssl_decrypt(base64_decode($encrypted_string), 'AES-256-CBC', $key, 0, $iv);
    return $decrypted;
}

?>