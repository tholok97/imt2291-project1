<?php

session_start();

require_once dirname(__FILE__) . '/classes/VideoManager.php';
require_once dirname(__FILE__) . '/classes/SessionManager.php';
require_once dirname(__FILE__) . '/classes/PlaylistManager.php';

$VideoManager = new VideoManager(DB::getDBConnection());
$SessionManager = new SessionManager();
$playlistManager = new PlaylistManager(DB::getDBConnection());

$searchAfter;
$advanced = false;          // Is it advanced search (find out if any box is marked);

$searchAfterInPlaylist = array();

if (isset($_POST['titleBox'])) {
    $searchAfter['title'] = true;
    array_push($searchAfterInPlaylist, 'title');
    $advanced = true;
}
if (isset($_POST['descriptionBox'])) {
    $searchAfter['description'] = true;
    array_push($searchAfterInPlaylist, 'description');
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

$video_result = $VideoManager->search(htmlspecialchars($_POST['searchText']), $searchAfter);
$playlist_result = $playlistManager->searchPlaylistsMultipleFields($_POST['searchText'], $searchAfterInPlaylist);

if ($video_result['status'] == 'ok' && $playlist_result['status'] == 'ok') {

    $SessionManager->put("searchResult",$video_result['result'], true);
    $SessionManager->put("playlistResult",$playlist_result['playlists'], true);
    
    // Go to result-page
    header('Location: ../search/result');
    exit();
}
else {
    // Reload page
    header('Location: ../search');
    exit();
}
