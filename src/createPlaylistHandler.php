<?php

session_start();

require_once dirname(__FILE__) . '/classes/PlaylistManager.php';
require_once dirname(__FILE__) . '/classes/Playlist.php';
require_once dirname(__FILE__) . '/classes/DB.php';


// add playlist
$playlistManager = new PlaylistManager(DB::getDBConnection());
$ret_create = $playlistManager->addPlaylist($_POST['title'], $_POST['description'], $_FILES['thumbnail']);

if ($ret_create['status'] == 'fail') {
    header('Location: ../createPlaylist');
    exit();
}

// add user to maintainers
$ret_maintainer = $playlistManager->addMaintainerToPlaylist($_SESSION['uid'], $ret_create['pid']);


if ($ret_maintainer['status'] == 'fail') {

    // remove the playlist
    $playlistManager->removePlaylist($ret_create['pid']);

    header('Location: ../createPlaylist');
    exit();
}


header('Location: ../');
exit();
