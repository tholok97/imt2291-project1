<?php

require_once 'DB.php';
require_once 'Video.php';
//require_once '../constants.php';
//require_once '../../config.php';
require_once basename(__FILE__) . '../functions/functions.php'; // Må rettes på!!!!!!


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
        if (is_uploaded_file($videoRef['tmp_name'])) {
            // If file size not too big.
            if($videoRef['size'] < 5000000 /*Some number*/) {
                $thumbnail = getThumbnail($videoRef);
                $sql = "INSERT INTO video (title, description, thumbnail, uid, topic, course_code, timestamp, view_count, mime, size) VALUES (:title, :description, :thumbnail, :uid, :topic, :course_code, :timestamp, :view_count, :mime, :size)";
                $sth = $this->db->prepare ($sql);
                $sth->bindParam(':title', htmlspecialchars($title));
                $sth->bindParam(':description', htmlspecialchars($description));
                $sth->bindParam(':thumbnail', $thumbnail);
                $sth->bindParam(':uid', htmlspecialchars($uid));
                $sth->bindParam(':topic', htmlspecialchars($topic));
                $sth->bindParam(':course_code', htmlspecialchars($course_code));
                $sth->bindParam(':timestamp', setTimestamp());          // Setting timestamp.
                $sth->bindParam(':view_count', 0);                      // Zero-out view-count.
                $sth->bindParam(':mime', $videoRef['type']);
                $sth->bindParam(':size', $videoRef['size']);
                $sth->execute ();
                if ($sth->rowCount()==1) {
                    $id = $db->lastInsertId();
                    if (!file_exists('uploadedFiles/'.$uid.'/videos')) {      // The user have not uploaded anything before.
                      @mkdir('uploadedFiles/'.$uid.'/videos');
                    }
                    if (@move_uploaded_file($_FILES['fileToUpload']['tmp_name'], "uploadedFiles/{$owner}/$id")) {
                      $ret['status'] = 'ok';
                      $ret['vid'] = $id;
                    } else {
                      $sql = "delete from videos where id=$id";
                      $db->exec($sql);
                      $ret['errorMessage'] = "Klarte ikke å lagre filen.";
                    }
                  } else {
                    $ret['errorMessage'] = "Klarte ikke å laste opp filen.";
                  }
            }
            else {
                $ret['errorMessage'] = "Filen er for stor til å kunne lastes opp.";
            }
        }
        else {
            $ret['errorMessage'] = "Vi lurer på om du hacker oss, ser ut som en ulovlig fil.";
        }

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
        
        // Check if a numeric id which is 0 or more.
        if (!is_numeric(htmlspecialchars($vid)) || htmlspecialchars($vid) < 0) {
            $ret['errorMessage'] = 'Fikk ingen korrekt video-id';
			return $ret;
        }
        
        // Check if connection to database was successfully established.
        if ($this->db == null) {
            $ret['errorMessage'] = 'Kunne ikke koble til databasen.';
            return $ret;
        }

        $stmt = $this->db->query('SELECT v.*, AVG(r.rating) AS rating FROM video v LEFT JOIN rated r ON r.vid = v.vid WHERE v.vid=' . htmlspecialchars($vid) . ' GROUP BY vid');

        // While-loop will (hopefully) just go one time.
        while($row = $stmt->fetch(PDO::FETCH_ASSOC))
        {
            $ret['video'] = new Video(htmlspecialchars($row['vid']), htmlspecialchars($row['title']), htmlspecialchars($row['description']),htmlspecialchars('uploadedFiles/'.$row['uid'].'/videos'.$row['vid']),/*htmlspecialchars(*/$row['thumbnail']/*)*/, htmlspecialchars($row['uid']), htmlspecialchars($row['topic']), htmlspecialchars($row['course_code']), htmlspecialchars($row['timestamp']), htmlspecialchars($row['view_count']), htmlspecialchars($row['rating']), htmlspecialchars($row['mime'], htmlspecialchars($row['size'])));
        }

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