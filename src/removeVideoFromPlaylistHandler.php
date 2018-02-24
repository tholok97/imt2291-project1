<?php

session_start();

require_once dirname(__FILE__) . '/classes/PlaylistManager.php';
require_once dirname(__FILE__) . '/classes/Playlist.php';
require_once dirname(__FILE__) . '/classes/DB.php';
require_once dirname(__FILE__) . '/classes/SessionManager.php';

// prepare sessionManager
$sessionManager = new SessionManager();

// add playlist
$playlistManager = new PlaylistManager(DB::getDBConnection());

$ret_remove= $playlistManager->removeVideoFromPlaylist($_POST['vid'], $_POST['pid']);


if ($ret_update['status'] == 'fail') {
    $sessionManager->put('message', "Couldn't remove video");
    header('Location: ../editPlaylist');
    exit();
}


header('Location: ../editPlaylist');
exit();
