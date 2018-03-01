<?php

session_start();

require_once dirname(__FILE__) . '/classes/PlaylistManager.php';
require_once dirname(__FILE__) . '/classes/Playlist.php';
require_once dirname(__FILE__) . '/classes/DB.php';
require_once dirname(__FILE__) . '/classes/SessionManager.php';

// prepare sessionManager
$sessionManager = new SessionManager();

// prepare playlist
$playlistManager = new PlaylistManager(DB::getDBConnection());

// prepare usermanager
$userManager = new UserManager(DB::getDBConnection());


// get pid of playlist
$ret_getplaylists = $playlistManager->searchPlaylists($_POST['playlistTitle'], 'title');

if ($ret_getplaylists['status'] == 'fail' || count($ret_getplaylists['playlists']) != 1) {
    $sessionManager->put('message', "Kunne ikke legge video til spilleliste: Spilleliste '" . $_POST['playlistTitle'] . "' finnes ikke? (Eller flere har samme navn?)");
    $sessionManager->put('messageStatus', "danger");
    header('Location: ../videos/' . $_POST['vid']);
    exit();
}


$pid = $ret_getplaylists['playlists'][0]->pid;


// check that user is maintainer of playlist

$ret_checkmaintainer = $playlistManager->getPlaylistsUserMaintains($_POST['uid']);

if ($ret_checkmaintainer['status'] == 'fail') {
    $sessionManager->put('message', "Kunne ikke legge video til spilleliste: Intern feil (1)");
    $sessionManager->put('messageStatus', "danger");
    header('Location: ../videos/' . $_POST['vid']);
    exit();
}




// transform array of playlists to array of pids
$maintains_pids = array();
foreach ($ret_checkmaintainer['playlists'] as $playlist) {
    array_push($maintains_pids, $playlist->pid);
}


// if user is not maintainer of playlist -> fail
if (!in_array($pid, $maintains_pids)) {
    $sessionManager->put('message', "Kunne ikke legge video til spilleliste: Du er ikke administrator over '" . $_POST['playlistTitle'] . "' ?");
    $sessionManager->put('messageStatus', "danger");
    header('Location: ../videos/' . $_POST['vid']);
    exit();
}





$ret_add = $playlistManager->addVideoToPlaylist($_POST['vid'], $pid);


if ($ret_add['status'] == 'fail') {
    $sessionManager->put('message', "Kunne ikke legge video til spilleliste. Intern feil (2)");
    $sessionManager->put('messageStatus', "danger");
    header('Location: ../videos/' . $_POST['vid']);
    exit();
}


$sessionManager->put('message', "Video lagt til spilleliste!");
$sessionManager->put('messageStatus', "success");

header('Location: ../videos/' . $_POST['vid']);
exit();
