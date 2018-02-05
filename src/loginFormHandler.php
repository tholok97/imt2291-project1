<?php

session_start();

require_once dirname(__FILE__) . '/classes/UserManager.php';


$userManager = new UserManager(DB::getDBConnection());
$ret = $userManager->login($_POST['username'], $_POST['password']);

print_r($ret);

if ($ret['status'] == 'ok') {
    $_SESSION['uid'] = $ret['uid'];
}

header('Location: ../');
exit();
