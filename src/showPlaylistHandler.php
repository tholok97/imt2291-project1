<?php

session_start();

require_once dirname(__FILE__) . '/classes/PlaylistManager.php';
require_once dirname(__FILE__) . '/classes/SessionManager.php';

// prepare handlers
$playlistManager = new PlaylistManager(DB::getDBConnection());
$sessionManager = new SessionManager();


// fetch playlist
$res = $playlistManager->getPlaylist($_POST['pid']);

if ($res['status'] != 'ok') {
    $sessionManager->put('message', "Couldn't go to playlist " . $_POST['pid']);
    header('Location: ../');
    exit();
}

// put in session
$sessionManager->put('playlistToShow', $res['playlist'], true);

// redirect to /playlist
header('Location: ../playlist');
exit();
