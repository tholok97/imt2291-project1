<?php

session_start();

require_once dirname(__FILE__) . '/classes/UserManager.php';
require_once dirname(__FILE__) . '/classes/User.php';
require_once dirname(__FILE__) . '/classes/DB.php';

// grant privilege
$userManager = new UserManager(DB::getDBConnection());
$ret = $userManager->grantPrivilege($_POST['uid'], $_POST['privilege_level']);

// if didn't work -> early return
if ($ret['status'] == 'fail') {
    echo "Didn't grant properly : " . $ret['message'];
    exit();
}


// delete request
$ret = $userManager->deletePrivilegeRequest($_POST['uid'], $_POST['privilege_level']);
// if didn't work -> early return
if ($ret['status'] == 'fail') {
    echo "Didn't delete request properly : " . $ret['message'];
    exit();
}



// if everything fine -> admin page
header('Location: ../admin');
exit();

