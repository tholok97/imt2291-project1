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
    $sessionManager->put('message', "User with username: " . $_POST['username']  . " not found");
    header('Location: ../editPlaylist');
    exit();
}


// check if privilege is high enough
$ret_getuser = $userManager->getUser($ret_getuseruid['uid']);

if ($ret_getuser['status'] == 'fail') {
    $sessionManager->put('message', "Couldn't determine privilege level of user");
    header('Location: ../editPlaylist');
    exit();
}

if ($ret_getuser['user']->privilege_level < 1) {
    $sessionManager->put('message', "Chosen user isn't privileged enough to be a maintainer");
    header('Location: ../editPlaylist');
    exit();
}



// add maintainer
$ret_add = $playlistManager->addMaintainerToPlaylist($ret_getuseruid['uid'], $_POST['pid']);


if ($ret_add['status'] == 'fail') {
    $sessionManager->put('message', "Couldn't add maintainer..");
    header('Location: ../editPlaylist');
    exit();
}



header('Location: ../editPlaylist');
exit();
