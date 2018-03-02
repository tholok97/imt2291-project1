<?php

session_start();

require_once dirname(__FILE__) . '/classes/UserManager.php';
require_once dirname(__FILE__) . '/classes/User.php';
require_once dirname(__FILE__) . '/classes/DB.php';
require_once dirname(__FILE__) . '/classes/SessionManager.php';

$sessionManager = new SessionManager();

$userManager = new UserManager(DB::getDBConnection());
$ret = $userManager->getUID($_POST['username']);



switch($_POST['submitAction']) {
case ("Rediger min info"):
    header('Location: ../editPersonalInfo');
    exit();
case ("Bli lærer"):
    if($ret['status'] == "ok") {
        $return = $userManager->requestPrivilege($ret['uid'], 1);

        header('Location: ../userpage');
        exit();
    } // TODO: error handling
    break;
case ("Bli admin"):
    if($ret['status'] == "ok") {
        $return = $userManager->requestPrivilege($ret['uid'], 2);

        header('Location: ../userpage');
        exit();
    }
case ("Slett konto"):
    header('Location: ../deleteAccount');
    exit();
case ("Se forespørsler"):
    header('Location: ../admin');
    exit();
default:
    header('Location: ../userpage');
    exit();
}              
