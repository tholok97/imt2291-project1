<?php

session_start();

require_once dirname(__FILE__) . '/classes/PlaylistManager.php';
require_once dirname(__FILE__) . '/classes/Playlist.php';
require_once dirname(__FILE__) . '/classes/DB.php';
require_once dirname(__FILE__) . '/classes/SessionManager.php';

// prepare sessionManager
$sessionManager = new SessionManager();

// prepare playlist
$playlistManager = new PlaylistManager(DB::getDBConnection());

// prepare usermanager
$userManager = new UserManager(DB::getDBConnection());


// get uid of user
$ret_getuseruid = $userManager->getUID($_POST['username']);

if ($ret_getuseruid['status'] == 'fail') {
    $sessionManager->put('message', "Bruker med brukernavn: " . $_POST['username']  . " ble ikke funnet.");
    $sessionManager->put('messageStatus', "danger");
    header('Location: ../admin');
    exit();
}




// make user admin
$ret = $userManager->grantPrivilege($ret_getuseruid['uid'], 2);

// if didn't work -> early return
if ($ret['status'] == 'fail') {
    $sessionManager->put("message", "Kunne ikke gjøre bruker til admin. Error: " . $ret['message']);
    $sessionManager->put('messageStatus', "danger");
    header('Location: ../admin');
    echo "Didn't grant properly : " . $ret['message'];
    exit();
}

$sessionManager->put("message", "Gjorde bruker til admin!");
$sessionManager->put('messageStatus', "success");


header('Location: ../admin');
exit();
