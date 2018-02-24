<?php

session_start();

require_once dirname(__FILE__) . '/classes/UserManager.php';
require_once dirname(__FILE__) . '/classes/User.php';
require_once dirname(__FILE__) . '/classes/DB.php';
require_once dirname(__FILE__) . '/classes/SessionManager.php';

$sessionManager = new SessionManager();

// grant privilege
$userManager = new UserManager(DB::getDBConnection());
$ret = $userManager->grantPrivilege($_POST['uid'], $_POST['privilege_level']);

// if didn't work -> early return
if ($ret['status'] == 'fail') {
    $sessionManager->put("message", "Couldn't grant request. Error: " . $ret['message']);
    echo "Didn't grant properly : " . $ret['message'];
    exit();
}


// delete request
$ret = $userManager->deletePrivilegeRequest($_POST['uid'], $_POST['privilege_level']);
// if didn't work -> early return
if ($ret['status'] == 'fail') {
    $sessionManager->put("message", "Couldn't grant request. Error: " . $ret['message']);
    echo "Didn't delete request properly : " . $ret['message'];
    exit();
}



// if everything fine -> admin page
$sessionManager->put("message", "Successfully granted privilege");
header('Location: ../admin');
exit();

