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

$ret_remove = $playlistManager->removeMaintainerFromPlaylist($_POST['uid'], $_POST['pid']);


if ($ret_remove['status'] != 'ok') {
    $sessionManager->put('message', "Kunne ikke slette vedlikeholder");
    $sessionManager->put('messageStatus', "danger");
    header('Location: ../editPlaylist');
    exit();
}


header('Location: ../editPlaylist');
exit();
