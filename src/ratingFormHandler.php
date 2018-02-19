<?php

session_start();

require_once dirname(__FILE__) . '/classes/VideoManager.php';

$VideoManager = new VideoManager(DB::getDBConnection());
$res = $VideoManager->addRating(
    htmlspecialchars(intval(htmlspecialchars($_POST['rating']))),
    htmlspecialchars($_POST['uid']),
    htmlspecialchars($_POST['vid'])
);
    
// if success -> go to index
// if not -> reload page
if ($res['status'] == 'ok') {
    header('Location: ../videos/' . htmlspecialchars($_POST['vid']));
    exit();
} else {
    //header('Location: ../videos/' . htmlspecialchars($_POST['vid']));
    exit();
}
