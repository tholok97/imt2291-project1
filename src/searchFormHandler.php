<?php

session_start();

require_once dirname(__FILE__) . '/classes/VideoManager.php';
require_once dirname(__FILE__) . '/classes/SessionManager.php';

$VideoManager = new VideoManager(DB::getDBConnection());
$SessionManager = new SessionManager();

$searchAfter;

if (isset($_POST['titleBox'])) {
    $searchAfter['title'] = true;
}
if (isset($_POST['descriptionBox'])) {
    $searchAfter['description'] = true;
}
if (isset($_POST['topicBox'])) {
    $searchAfter['topic'] = true;
}
if (isset($_POST['courseBox'])) {
    $searchAfter['course_code'] = true;
}
if (isset($_POST['firstnameBox'])) {
    $searchAfter['firstname'] = true;
}
if (isset($_POST['lastnameBox'])) {
    $searchAfter['lastname'] = true;
}

$result = $VideoManager->search(htmlspecialchars($_POST['searchText']), $searchAfter);

if ($result['status'] == 'ok') {

    $SessionManager->put("searchResult",$result['result'], true);
    
    // Go to result-page
    header('Location: ../search/result');
    exit();
}
else {
    // Reload page
    header('Location: ../search');
    exit();
}