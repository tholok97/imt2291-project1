<?php

session_start();

require_once dirname(__FILE__) . '/classes/UserManager.php';
require_once dirname(__FILE__) . '/classes/SessionManager.php';

$sessionManager = new SessionManager();


$userManager = new UserManager(DB::getDBConnection());
$ret = $userManager->login($_POST['username'], $_POST['password']);

if ($ret['status'] == 'fail') {
    $sessionManager->put("message", "Kunne ikke logge inn");
    $sessionManager->put('messageStatus', "danger");
    header('Location: ../');
    exit();
}

// get privlege level
$uid = $ret['uid'];
$ret = $userManager->getUser($uid);

if ($ret['status'] == 'fail') {
    $sessionManager->put("message", "Kunne ikke logge inn");
    $sessionManager->put('messageStatus', "danger");
    header('Location: ../');
    exit();
}

$_SESSION['uid'] = $uid;
$_SESSION['privilege_level'] = $ret['user']->privilege_level;


$sessionManager->put("message", "Du er nå logget inn :D");
$sessionManager->put('messageStatus', "success");
header('Location: ../');
exit();
