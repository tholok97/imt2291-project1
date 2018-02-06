<?php

session_start();

require_once dirname(__FILE__) . '/classes/UserManager.php';
require_once dirname(__FILE__) . '/classes/User.php';

// build user from POST
$user = new User(
    $_POST['username'],
    $_POST['firstname'],
    $_POST['lastname'],
    $_POST['privilege_level']
);


// do add user
$userManager = new UserManager(DB::getDBConnection());
$ret = $userManager->addUser($user, $_POST['password']);

// if success -> go to index
// if not -> reload page
if ($ret['status'] == 'ok') {
    echo 'yay!';
    header('Location: ../');
    exit();
} else {
    header('Location: ../register');
    exit();
}

