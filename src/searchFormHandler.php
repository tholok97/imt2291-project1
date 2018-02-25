<?php

session_start();

require_once dirname(__FILE__) . '/classes/VideoManager.php';
require_once dirname(__FILE__) . '/classes/SessionManager.php';

$VideoManager = new VideoManager(DB::getDBConnection());
$SessionManager = new SessionManager();

$searchAfter;
$advanced = false;          // Is it advanced search (find out if any box is marked);

if (isset($_POST['titleBox'])) {
    $searchAfter['title'] = true;
    $advanced = true;
}
if (isset($_POST['descriptionBox'])) {
    $searchAfter['description'] = true;
    $advanced = true;
}
if (isset($_POST['topicBox'])) {
    $searchAfter['topic'] = true;
    $advanced = true;
}
if (isset($_POST['courseBox'])) {
    $searchAfter['course_code'] = true;
    $advanced = true;
}
if (isset($_POST['firstnameBox'])) {
    $searchAfter['firstname'] = true;
    $advanced = true;
}
if (isset($_POST['lastnameBox'])) {
    $searchAfter['lastname'] = true;
    $advanced = true;
}

if (!$advanced) {
    $searchAfter['title'] = true;
    $searchAfter['description'] = true;
    $searchAfter['topic'] = true;
    $searchAfter['course_code'] = true;
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
