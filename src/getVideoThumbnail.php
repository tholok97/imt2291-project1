<?php

// returns thumbnail of video from db
// EXPECTS:
//  $_GET['vid'] -> vid of video

require_once dirname(__FILE__) . '/constants.php';
require_once dirname(__FILE__) . '/classes/DB.php';

$thumbnail = null;
$ok = false;

try {

    $dbh = DB::getDBConnection();

    $stmt = $dbh->prepare('SELECT thumbnail FROM video WHERE vid=:vid');
    $stmt->bindParam(':vid', $_GET['vid']);
    $ok = $stmt->execute();


    if ($ok) {
        $thumbnail = $stmt->fetchAll()[0];
    }

} catch (PDOException $ex) {
    echo 'Coudn\'t load image: ' . $ex->getMessage();
}

// if everything went right -> echo image
if ($ok) {

    header('Content-type: image/png');

    echo $thumbnail['thumbnail'];
} else {
    echo 'something went wrong..';
}

