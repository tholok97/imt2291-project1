<?php

session_start();

require_once dirname(__FILE__) . '/classes/UserManager.php';


$userManager = new UserManager(DB::getDBConnection());
$ret = $userManager->login($_POST['username'], $_POST['password']);

if ($ret['status'] == 'fail') {
    header('Location: ../');
    exit();
}

// get privlege level
$uid = $ret['uid'];
$ret = $userManager->getUser($uid);

if ($ret['status'] == 'fail') {
    header('Location: ../');
    exit();
}

$_SESSION['uid'] = $uid;
$_SESSION['privilege_level'] = $ret['user']->privilege_level;

header('Location: ../');
exit();
