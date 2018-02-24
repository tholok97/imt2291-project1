<?php

session_start();

require_once dirname(__FILE__) . '/classes/UserManager.php';
require_once dirname(__FILE__) . '/classes/SessionManager.php';

$sessionManager = new SessionManager();


$userManager = new UserManager(DB::getDBConnection());
$ret = $userManager->login($_POST['username'], $_POST['password']);

if ($ret['status'] == 'fail') {
    $sessionManager->put("message", "Couldn't login");
    header('Location: ../');
    exit();
}

// get privlege level
$uid = $ret['uid'];
$ret = $userManager->getUser($uid);

if ($ret['status'] == 'fail') {
    $sessionManager->put("message", "Couldn't login");
    header('Location: ../');
    exit();
}

$_SESSION['uid'] = $uid;
$_SESSION['privilege_level'] = $ret['user']->privilege_level;


$sessionManager->put("message", "Successfully logged in :D");
header('Location: ../');
exit();
