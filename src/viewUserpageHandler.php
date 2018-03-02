<?php

session_start();

require_once dirname(__FILE__) . '/classes/UserManager.php';
require_once dirname(__FILE__) . '/classes/User.php';
require_once dirname(__FILE__) . '/classes/DB.php';
require_once dirname(__FILE__) . '/classes/SessionManager.php';

$sessionManager = new SessionManager();

$userManager = new UserManager(DB::getDBConnection());
$ret = $userManager->getUID($_POST['username']);


// TODO: Test whether editUser, requestPrivilege, or deleteUser gets called

switch($_POST) {
case ("Rediger min info"):
case ("Bli lærer"):
    if($ret['status'] == "ok") {
        $userManager->requestPrivilege($ret['uid'], 1);

        //TODO: The stuff..

    } // TODO: error msg
    break;
case ("Slett konto"):
}              
