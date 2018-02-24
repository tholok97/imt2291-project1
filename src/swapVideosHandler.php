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

$ret_swap = $playlistManager->swapPositionsInPlaylist($_POST['position1'], $_POST['position2'], $_POST['pid']);


if ($ret_swap['status'] == 'fail') {
    $sessionManager->put('message', "Couldn't swap videos");
    header('Location: ../editPlaylist');
    exit();
}


header('Location: ../editPlaylist');
exit();
