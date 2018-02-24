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

$ret_update = $playlistManager->updatePlaylist($_POST['pid'], $_POST['title'], $_POST['description'], '');



if ($ret_update['status'] == 'fail') {
    $sessionManager->put('message', "Couldn't update playlist");
    header('Location: ../editPlaylist');
    exit();
}


header('Location: ../editPlaylist');
exit();
