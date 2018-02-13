<?php

session_start();

require_once dirname(__FILE__) . '/classes/UserManager.php';
require_once dirname(__FILE__) . '/classes/User.php';

// build user from POST
$user = new User(
    $_POST['username'],
    $_POST['firstname'],
    $_POST['lastname'],
    0
);



// do add user
$userManager = new UserManager(DB::getDBConnection());
$ret = $userManager->addUser($user, $_POST['password']);




// if success -> go to index
// if not -> reload page
if ($ret['status'] == 'ok') {

    // if the user wants privilege -> register it in the db 
    if (isset($_POST['wants_lecturer'])) {
        $privilege_ret = $userManager->requestPrivilege($ret['uid'], 1);
    }

    // TODO: currently ignores it if registering privilege fails....

    header('Location: ../');
    exit();
} else {
    header('Location: ../register');
    exit();
}

