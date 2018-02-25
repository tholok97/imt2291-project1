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


// get uid of maintainer
$ret_getuseruid = $userManager->getUID($_POST['username']);

if ($ret_getuseruid['status'] == 'fail') {
    $sessionManager->put('message', "Bruker med brukernavn: " . $_POST['username']  . " ble ikke funnet.");
    $sessionManager->put('messageStatus', "primary");
    header('Location: ../editPlaylist');
    exit();
}


// check if privilege is high enough
$ret_getuser = $userManager->getUser($ret_getuseruid['uid']);

if ($ret_getuser['status'] == 'fail') {
    $sessionManager->put('message', "Kunne ikke fastslå brukerens rettigheter.");
    $sessionManager->put('messageStatus', "danger");
    header('Location: ../editPlaylist');
    exit();
}

if ($ret_getuser['user']->privilege_level < 1) {
    $sessionManager->put('message', "Valgt bruker har ikke rettigheter til å bli valgt til å vedlikeholde spilleliste.");
    $sessionManager->put('messageStatus', "danger");
    header('Location: ../editPlaylist');
    exit();
}



// add maintainer
$ret_add = $playlistManager->addMaintainerToPlaylist($ret_getuseruid['uid'], $_POST['pid']);


if ($ret_add['status'] == 'fail') {
    $sessionManager->put('message', "Kunne ikke legge til vedlikeholder...");
    $sessionManager->put('messageStatus', "danger");
    header('Location: ../editPlaylist');
    exit();
}



header('Location: ../editPlaylist');
exit();
