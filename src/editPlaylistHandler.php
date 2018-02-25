<?php

session_start();

require_once dirname(__FILE__) . '/classes/PlaylistManager.php';
require_once dirname(__FILE__) . '/classes/SessionManager.php';

// prepare handlers
$sessionManager = new SessionManager();


$sessionManager->put('playlistToEdit', $_POST['pid']);

// redirect to /playlist
header('Location: ../editPlaylist');
exit();
