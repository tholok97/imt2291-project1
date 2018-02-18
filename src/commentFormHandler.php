<?php

session_start();

require_once dirname(__FILE__) . '/classes/VideoManager.php';


$VideoManager = new VideoManager(DB::getDBConnection());
$res = $VideoManager->comment(
    htmlspecialchars($_POST['text']),
    htmlspecialchars($_POST['uid']),
    htmlspecialchars($_POST['vid'])
);

// if success -> go to index
// if not -> reload page
if ($res['status'] == 'ok') {
    header('Location: ../videos/' . htmlspecialchars($_POST['vid']));
    exit();
} else {
    header('Location: ../error' . $res['errorMessage']);
    exit();
}