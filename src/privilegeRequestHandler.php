<?php

session_start();

require_once dirname(__FILE__) . '/classes/UserManager.php';
require_once dirname(__FILE__) . '/classes/User.php';
require_once dirname(__FILE__) . '/classes/DB.php';
require_once dirname(__FILE__) . '/classes/SessionManager.php';

$sessionManager = new SessionManager();

// grant privilege
$userManager = new UserManager(DB::getDBConnection());

// Check that request field is a legal request.
if($_POST['request'] == 'Tillat' || $_POST['request'] == 'Avvis')  {
    if($_POST['request'] == 'Tillat') {
        $ret = $userManager->grantPrivilege($_POST['uid'], $_POST['privilege_level']);

        // if didn't work -> early return
        if ($ret['status'] == 'fail') {
            $sessionManager->put("message", "Kunne ikke godta forespørsel. Error: " . $ret['message']);
            $sessionManager->put('messageStatus', "danger");
            echo "Didn't grant properly : " . $ret['message'];
            exit();
        }
    }
    // delete request
    $ret = $userManager->deletePrivilegeRequest($_POST['uid'], $_POST['privilege_level']);
    // if didn't work -> early return
    if ($ret['status'] == 'fail') {
        $sessionManager->put("message", "Kunne ikke godta forespørsel: " . $ret['message']);
        $sessionManager->put('messageStatus', "danger");
        echo "Didn't delete request properly : " . $ret['message'];
        exit();
    }
}


// Output success if user rejected
// Output success if user added
// Force logout if ended up at this page without following the required 
//  input for the request field -> Hacking.
if($_POST['request'] == 'Avvis') {
    $sessionManager->put("message", "Avviste brukerens forespørsel");
    $sessionManager->put('messageStatus', "success");
} elseif ($_POST['request'] == 'Tillat') {
    $sessionManager->put("message", "Ga privilegium til bruker");
    $sessionManager->put('messageStatus', "success");
} else {
    $sessionManager->put("message", "Stopp å hacke nå");
    $sessionManager->put('messageStatus', "danger");
    header('Location: ../logout');
    exit();
}


// if everything fine -> admin page
header('Location: ../admin');
exit();

