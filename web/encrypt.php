<?php
    require_once(dirname(__FILE__).'/../config/config.php');
    require_once(dirname(__FILE__).'/functions.php');
    require_once(dirname(__FILE__).'/../lib/crypt.php');

    echo(encrypt("管理者", $secret_key, $secret_iv));
    echo(decrypt("a2VFYmZtL2RVbjZVbERlb0tLVzRpQT09", $secret_key, $secret_iv));
?>