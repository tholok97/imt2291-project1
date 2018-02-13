<?php
require_once dirname(__FILE__) . '/../src/classes/DB.php';
require_once dirname(__FILE__) . '/../src/classes/VideoManager.php';
require_once dirname(__FILE__) . '/../src/classes/Video.php';

function uploadVideoTestdata($title, $description, $uid, $topic, $course_code) {
    $ret['status'] = 'fail';
    $ret['vid'] = null;
    $ret['errorMessage'] = null;
        
    $db = DB::getDBConnection(
        Config::DB_TEST_DSN, 
        Config::DB_TEST_USER,
        Config::DB_TEST_PASSWORD
    );

    // Check if connection to database was successfully established.
    if ($db == null) {
        $ret['errorMessage'] = 'Couldn\'t connect to database.';
        return $ret;
    }

    $title = htmlspecialchars($title);
    $description = htmlspecialchars($description);
    $thumbnail = getThumbnail("...");               // Muligens vi må endre til $_FILES på noen av de under, i tilfelle vil $videoRef bli helt fjernet.
    $uid = htmlspecialchars($uid);
    $topic = htmlspecialchars($topic);
    $course_code = htmlspecialchars($course_code);
    $timestamp = setTimestamp();
    $views = 0;
    $mime = "video/mp4";
    $size = 4837755;
    $sql = "INSERT INTO video (title, description, thumbnail, uid, topic, course_code, timestamp, view_count, mime, size) VALUES (:title, :description, :thumbnail, :uid, :topic, :course_code, :timestamp, :view_count, :mime, :size)";
    $sth = $db->prepare ($sql);
    $sth->bindParam(':title', $title);
    $sth->bindParam(':description', $description);
    $sth->bindParam(':thumbnail', $thumbnail);
    $sth->bindParam(':uid', $uid);
    $sth->bindParam(':topic', $topic);
    $sth->bindParam(':course_code', $course_code);
    $sth->bindParam(':timestamp', $timestamp);                   // Setting timestamp.
    $sth->bindParam(':view_count', $views);                      // Zero-out view-count.
    $sth->bindParam(':mime', $mime);
    $sth->bindParam(':size', $size);
    $sth->execute();
    //print_r($sth->errorInfo());
    if ($sth->rowCount()==1) {
        $ret['status'] = 'ok';
        $ret['vid'] = $db->lastInsertId();
    }
    else {
        $ret['errorMessage'] = "Could not insert video-info in database";
    }
    return $ret;
}