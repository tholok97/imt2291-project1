<?php

session_start();

require_once dirname(__FILE__) . '/classes/VideoManager.php';


$VideoManager = new VideoManager(DB::getDBConnection());
$res = $VideoManager->upload(
    $_POST['title'],
    $_POST['descr'],
    $_SESSION['uid'],
    $_POST['topic'],
    $_POST['course'],
    $_FILES['fileToUpload']
);

// if success -> go to index
// if not -> reload page
if ($res['status'] == 'ok') {
    header('Location: ../');
    exit();
} else {
    header('Location: ../upload');
    exit();
}

