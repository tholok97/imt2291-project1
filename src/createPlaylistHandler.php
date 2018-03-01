<?php

session_start();

require_once dirname(__FILE__) . '/classes/PlaylistManager.php';
require_once dirname(__FILE__) . '/classes/Playlist.php';
require_once dirname(__FILE__) . '/classes/DB.php';
require_once dirname(__FILE__) . '/classes/SessionManager.php';

$sessionManager = new SessionManager();


// add playlist
$playlistManager = new PlaylistManager(DB::getDBConnection());
$ret_create = $playlistManager->addPlaylist($_POST['title'], $_POST['description'], $_FILES['thumbnail']);

if ($ret_create['status'] == 'fail') {

    $sessionManager->put("message", "Kunne ikke lage spilleliste");
    $sessionManager->put('messageStatus', "danger");

    header('Location: ../createPlaylist');
    exit();
}

// add user to maintainers
$ret_maintainer = $playlistManager->addMaintainerToPlaylist($_SESSION['uid'], $ret_create['pid']);


if ($ret_maintainer['status'] == 'fail') {

    // remove the playlist
    $playlistManager->removePlaylist($ret_create['pid']);

    $sessionManager->put("message", "Kunne ikke lage spilleliste");
    $sessionManager->put('messageStatus', "danger");

    header('Location: ../createPlaylist');
    exit();
}


$sessionManager->put("message", "Laget ny spilleliste!");
$sessionManager->put('messageStatus', "success");

header('Location: ../');
exit();
