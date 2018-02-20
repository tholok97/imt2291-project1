<?php

session_start();

require_once dirname(__FILE__) . '/classes/VideoManager.php';


$VideoManager = new VideoManager(DB::getDBConnection());
$res = $VideoManager->update(
    $_POST['vid'],
    $_SESSION['uid'],
    $_POST['title'],
    $_POST['descr'],
    $_POST['topic'],
    $_POST['course']
);

// if success -> go to index
// if not -> reload page
if ($res['status'] == 'ok') {
    header('Location: ../videos/' . $_POST['vid']);
    exit();
} else {
    header('Location: ../videos/' . $_POST['vid']);
    exit();
}