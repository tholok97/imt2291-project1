<?php

session_start();

require_once dirname(__FILE__) . '/classes/UserManager.php';
require_once dirname(__FILE__) . '/classes/SessionManager.php';

$sessionManager = new SessionManager();


$userManager = new UserManager(DB::getDBConnection());
$ret = $userManager->login($_POST['username'], $_POST['password']);

print_r($ret);

if ($ret['status'] == 'ok') {
    $sessionManager->put("message", "Successfully logged in :D");
    $_SESSION['uid'] = $ret['uid'];
} else {
    $sessionManager->put("message", "Couldn't login");
}


header('Location: ../');
exit();
