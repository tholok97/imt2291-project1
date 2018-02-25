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

// don't update thumbnail
$ret_update = $playlistManager->updatePlaylist($_POST['pid'], $_POST['title'], $_POST['description']);



if ($ret_update['status'] == 'fail') {
    $sessionManager->put('message', "Kunne ikke uppdatere spilleliste");
    $sessionManager->put('messageStatus', "danger");
    header('Location: ../editPlaylist');
    exit();
}

$sessionManager->put('message', "Oppdaterte spilleliste");
$sessionManager->put('messageStatus', "success");


header('Location: ../editPlaylist');
exit();
