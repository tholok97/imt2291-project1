<?php

session_start();

require_once dirname(__FILE__) . '/classes/UserManager.php';
require_once dirname(__FILE__) . '/classes/User.php';
require_once dirname(__FILE__) . '/classes/DB.php';

// grant privilege
$userManager = new UserManager(DB::getDBConnection());
$ret = $userManager->grantPrivilege($_POST['uid'], $_POST['privilege_level']);


// if success -> go to index
// if not -> reload page
if ($ret_updateuser['status'] == 'ok') {

    header('Location: ../admin');
    exit();
} else {
    header('Location: ../admin');
    exit();
}

