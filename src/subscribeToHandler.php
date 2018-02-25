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

$ret_subscribe = $playlistManager->subscribeUserToPlaylist(
    $_POST['uid'],
    $_POST['pid']
);


if ($ret_subscribe['status'] == 'fail') {
    $sessionManager->put('message', "Kunne ikke abonnere på spilleliste : " . $ret_subscribe['message']);
    header('Location: ../playlist');
    exit();
}


$sessionManager->put('message', "Abonnert på spilleliste!");


header('Location: ../playlist');
exit();
