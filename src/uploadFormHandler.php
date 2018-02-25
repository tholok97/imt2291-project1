<?php

session_start();

require_once dirname(__FILE__) . '/classes/VideoManager.php';
require_once dirname(__FILE__) . '/classes/SessionManager.php';

$sessionManager = new SessionManager();


$VideoManager = new VideoManager(DB::getDBConnection());
$res = $VideoManager->upload(
    $_POST['title'],
    $_POST['descr'],
    $_SESSION['uid'],
    $_POST['topic'],
    $_POST['course'],
    $_FILES['fileToUpload'],
    $_FILES['thumbnail']
);

// if success -> go to index
// if not -> reload page
if ($res['status'] == 'ok') {
    $sessionManager->put("message", "Video lastet opp");
    $sessionManager->put('messageStatus', "success");
    header('Location: ../');
    exit();
} else {
    $sessionManager->put("message", "Kunne ikke laste opp video");
    $sessionManager->put('messageStatus', "danger");
    header('Location: ../upload');
    exit();
}

