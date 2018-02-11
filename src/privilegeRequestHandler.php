<?php

session_start();

require_once dirname(__FILE__) . '/classes/UserManager.php';
require_once dirname(__FILE__) . '/classes/User.php';

// heigten privlege TODO

$ret['status'] = 'ok';

// if success -> go to index
// if not -> reload page
if ($ret['status'] == 'ok') {

    // TODO: currently ignores it if registering privilege fails....

    header('Location: ../admin');
    exit();
} else {
    header('Location: ../admin');
    exit();
}

