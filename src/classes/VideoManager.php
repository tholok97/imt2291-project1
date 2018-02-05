<?php

require_once 'DB.php';
require_once 'Video.php';
require_once dirname(__FILE__) . '/../constants.php';
require_once dirname(__FILE__) . '/../../config.php';
require_once dirname(__FILE__) . '/../functions/functions.php';


class VideoManager {
    // database handler. a pdo object
    private $db = null;



    public function __construct($dbh) {
        $this->db = $dbh;
    }

    /**
     * Upload video to service.
     * 
     * @param string The title of the video.
     * @param string Description of the video.
     * @param int The id of the user who uploaded the video.
     * @param string A topic the video is about.
     * @param string The course code of the course the video is made for.
     * @param array[] An $_FILES[] array. Example $_FILES['uploadedFile'] as input.
     * 
     * @return array[] Returns an associative array with the fields 'status', 'vid' (video id) and 'errorMessage' (if error).
     */
    public function upload($title, $description, $uid, $topic, $course_code, $videoRef) {
        $ret['status'] = 'fail';
        $ret['vid'] = null;
        $ret['errorMessage'] = null;
        
        // Check if connection to database was successfully established.
        if ($this->db == null) {
            $ret['errorMessage'] = 'Kunne ikke koble til databasen.';
            return $ret;
        }

        // If not someone who is trying to hack us.
        //if (is_uploaded_file($_FILES['fileToUpload']['tmp_name'])) {
            // If file size not too big.
            if($videoRef['size'] < 5000000 /*Some number*/) {
                $title = htmlspecialchars($title);
                $description = htmlspecialchars($description);
                $thumbnail = getThumbnail($videoRef);               // Muligens vi må endre til $_FILES på noen av de under, i tilfelle vil $videoRef bli helt fjernet.
                $uid = htmlspecialchars($uid);
                $topic = htmlspecialchars($topic);
                $course_code = htmlspecialchars($course_code);
                $timestamp = setTimestamp();
                $views = 0;
                $sql = "INSERT INTO video (title, description, thumbnail, uid, topic, course_code, timestamp, view_count, mime, size) VALUES (:title, :description, :thumbnail, :uid, :topic, :course_code, :timestamp, :view_count, :mime, :size)";
                $sth = $this->db->prepare ($sql);
                $sth->bindParam(':title', $title);
                $sth->bindParam(':description', $description);
                $sth->bindParam(':thumbnail', $thumbnail);
                $sth->bindParam(':uid', $uid);
                $sth->bindParam(':topic', $topic);
                $sth->bindParam(':course_code', $course_code);
                $sth->bindParam(':timestamp', $timestamp);                   // Setting timestamp.
                $sth->bindParam(':view_count', $views);                      // Zero-out view-count.
                $sth->bindParam(':mime', $_FILES['fileToUpload']['type']);
                $sth->bindParam(':size', $_FILES['fileToUpload']['size']);
                $sth->execute();
                //print_r($sth->errorInfo());
                if ($sth->rowCount()==1) {
                    $id = $this->db->lastInsertId();
                    if (!file_exists(dirname(__FILE__) . '/../../uploadedFiles/'.$uid.'/videos')) {      // The user have not uploaded anything before.
                      mkdir(dirname(__FILE__) . '/../../uploadedFiles/'.$uid.'/videos');
                    }
                    if (move_uploaded_file($_FILES['fileToUpload']['tmp_name'], dirname(__FILE__) . '/../../uploadedFiles/'.$uid.'/videos/'.$id)) {
                      $ret['status'] = 'ok';
                      $ret['vid'] = $id;
                    } else {
                      $sql = "delete from videos where id=$id";
                      $this->db->exec($sql);
                      $ret['errorMessage'] = "Klarte ikke å lagre filen.";
                    }
                  } else {
                    $ret['errorMessage'] = "Klarte ikke å laste opp filen.";
                  }
            }
            else {
                $ret['errorMessage'] = "Filen er for stor til å kunne lastes opp.";
            }
        /*}
        else {
            $ret['errorMessage'] = "Vi lurer på om du hacker oss, ser ut som en ulovlig fil.";
        }*/

        return $ret;
    }

    /**
     * Returns video and info about the video.
     * 
     * @param int The video's id.
     * @param bool Increase number of views on video or not (default true).
     * 
     * @return array[] Returns an associative array with the fields 'status' and 'errorMessage' (if error) and a 'video'-field with a video-object if no error.
     */
    public function get($vid, $increaseViews = true) {
        $ret['status'] = 'fail';
        $ret['errorMessage'] = null;

        $vid = htmlspecialchars($vid);  // Check that someone does not hack you.
        
        // Check if a numeric id is more than 0.
        if (!is_numeric($vid) || $vid <= 0) {
            $ret['errorMessage'] = 'Fikk ingen korrekt video-id';
			return $ret;
        }
        
        // Check if connection to database was successfully established.
        if ($this->db == null) {
            $ret['errorMessage'] = 'Kunne ikke koble til databasen.';
            return $ret;
        }

        $sth = $this->db->query('SELECT v.*, AVG(r.rating) AS rating FROM video v LEFT JOIN rated r ON r.vid = v.vid WHERE v.vid=' . $vid . ' GROUP BY vid');

        $views;
        // While-loop will (hopefully) just go one time.
        while($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $views = htmlspecialchars($row['view_count']) + 1;
            $ret['status'] = 'ok';
            $ret['video'] = new Video(htmlspecialchars($row['vid']), htmlspecialchars($row['title']), htmlspecialchars($row['description']), htmlspecialchars(/* Something here */'uploadedFiles/'.$row['uid'].'/videos/'.$row['vid']), /*htmlspecialchars(*/$row['thumbnail']/*)*/, htmlspecialchars($row['uid']), htmlspecialchars($row['topic']), htmlspecialchars($row['course_code']), htmlspecialchars($row['timestamp']), $views, htmlspecialchars($row['rating']), htmlspecialchars($row['mime']), htmlspecialchars($row['size']));
        }

        $sql = "UPDATE video SET view_count = :view_count WHERE vid = :vid";
        $sth = $this->db->prepare ($sql);
        $sth->bindParam(':view_count', $views);
        $sth->bindParam(':vid', $vid);
        $sth->execute();

        return $ret;
    }

    /**
     * Comment video.
     * 
     * @param string A title.
     * @param string A description.
     * @param int The number of stars the user rate it to be.
     * @param int The id to the user.
     * @param int The id to the video to comment on.
     * 
     * @return array[] Returns an associative array with the fields 'status', 'id' and 'errorMessage (if error)'.
     */
    public function comment($title, $description, $userID, $videoID) {
        $ret['status'] = 'fail';
        $ret['id'] = null;
        $ret['errorMessage'] = null;
        
        return $ret;
    }

    /**
     * Get comments for video.
     * 
     * @param int $vid - Video id to get comments from.
     * 
     * @return array[] Returns an associative array with the fields 'status' and 'errorMessage (if error) + another associative array for every comment with the fields 'id', 'user' and 'comment'.
     */
    public function getComments($vid) {
        $ret['status'] = 'fail';
        $ret['errorMessage'] = null; 
    }
}