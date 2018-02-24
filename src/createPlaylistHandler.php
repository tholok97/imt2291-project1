<?php

session_start();

require_once dirname(__FILE__) . '/classes/PlaylistManager.php';
require_once dirname(__FILE__) . '/classes/Playlist.php';
require_once dirname(__FILE__) . '/classes/DB.php';


// add playlist
$playlistManager = new PlaylistManager(DB::getDBConnection());
$ret = $playlistManager->addPlaylist($_POST['title'], $_POST['description'], $_FILES['thumbnail']);


// if success -> go to index
// if not -> reload page
if ($ret['status'] == 'ok') {

    header('Location: ../');
    exit();
} else {
    header('Location: ../createPlaylist');
    exit();
}

