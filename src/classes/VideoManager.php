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
                    mkdir(dirname(__FILE__) . '/../../uploadedFiles/'.$uid.'/videos', 0777, true);
                }
                if (move_uploaded_file($_FILES['fileToUpload']['tmp_name'], dirname(__FILE__) . '/../../uploadedFiles/'.$uid.'/videos/'.$id)) {
                    $ret['status'] = 'ok';
                    $ret['vid'] = $id;
                } else {

                    print_r($sth->errorInfo());

                    $sql = "delete from videos where id=$id";
                    $this->db->execute($sql);
                    $ret['errorMessage'] = "Klarte ikke å lagre filen.";
                    $ret['uid'] = $uid;
                    $ret['id'] = $id;
                    $ret['file'] = $_FILES['fileToUpload']['tmp_name'];
                    $ret['files'] = $_FILES;
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

        $sth = $this->db->prepare('SELECT * FROM video WHERE vid = :vid GROUP BY vid');
        $sth->bindParam(':vid', $vid);
        $sth->execute();

        $views;
        // While-loop will (hopefully) just go one time.
        while($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $views = htmlspecialchars($row['view_count']) + 1;
            $ret['status'] = 'ok';
            $ret['video'] = new Video(htmlspecialchars($row['vid']), htmlspecialchars($row['title']), htmlspecialchars($row['description']), htmlspecialchars('uploadedFiles/'.$row['uid'].'/videos/'.$row['vid']), /*htmlspecialchars($row['thumbnail']),*/ htmlspecialchars($row['uid']), htmlspecialchars($row['topic']), htmlspecialchars($row['course_code']), htmlspecialchars($row['timestamp']), $views, htmlspecialchars($row['mime']), htmlspecialchars($row['size']));
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
     * @param string The text which is the comment.
     * @param int The id to the user.
     * @param int The id to the video to comment on.
     * 
     * @return array[] Returns an associative array with the fields 'status', 'cid' and 'errorMessage (if error)'.
     */
    public function comment($text, $uid, $vid) {
        $ret['status'] = 'fail';
        $ret['cid'] = null;
        $ret['errorMessage'] = null;

        $text = htmlspecialchars($text);
        $uid = htmlspecialchars($uid);
        $vid = htmlspecialchars($vid);
        $timestamp = setTimestamp();

        // Check if video-id is numeric and more than 0.
        if (!is_numeric($vid) || $vid <= 0) {
            $ret['errorMessage'] = 'Fikk ingen korrekt video-id';
            return $ret;
        }

        // Check if user-id is numeric and more than 0.
        if (!is_numeric($uid) || $uid <= 0) {
            $ret['errorMessage'] = 'Fikk ingen korrekt bruker-id';
            return $ret;
        }

        // Check if connection to database was successfully established.
        if ($this->db == null) {
            $ret['errorMessage'] = 'Kunne ikke koble til databasen.';
            return $ret;
        }

        $sql = "INSERT INTO comment (vid, uid, text, timestamp) VALUES (:vid, :uid, :text, :timestamp)";
        $sth = $this->db->prepare ($sql);
        $sth->bindParam(':vid', $vid);
        $sth->bindParam(':uid', $uid);
        $sth->bindParam(':text', $text);
        $sth->bindParam(':timestamp', $timestamp);
        $sth->execute();

        if ($sth->rowCount()==1) {
            $ret['status'] = 'ok';
            $ret['cid'] = $this->db->lastInsertId();
        }
        else {
            $ret['errorMessage'] = 'Fikk ikke lagt til kommentar i databasen.';
        }

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

        $vid = htmlspecialchars($vid);              // Be sure we are not hacked.

        // Check if video-id is numeric and more than 0.
        if (!is_numeric($vid) || $vid <= 0) {
            $ret['errorMessage'] = 'Fikk ingen korrekt video-id';
            return $ret;
        }

        // Check if connection to database was successfully established.
        if ($this->db == null) {
            $ret['errorMessage'] = 'Kunne ikke koble til databasen.';
            return $ret;
        }

        $sth = $this->db->prepare('SELECT cid, vid, uid, text, timestamp FROM comment WHERE vid = :vid');
        $sth->bindParam(':vid', $vid);
        $sth->execute();
        $ret['status'] = 'ok';
        $i = 0;

        // Get all comments
        while($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $ret['comments'][$i]['cid'] = $row['cid'];
            $ret['comments'][$i]['vid'] = $row['vid'];
            $ret['comments'][$i]['uid'] = $row['uid'];
            $ret['comments'][$i]['text'] = $row['text'];
            $ret['comments'][$i]['timestamp'] = $row['timestamp'];
            $i++;
        }

        return $ret;
    }

    /**
     * Rate video.
     * 
     * @param int The rating.
     * @param int The id to the user.
     * @param int The id to the video to rate.
     * 
     * @return array[] Returns an associative array with the fields 'status' and 'errorMessage' (if error).
     */
    public function addRating($rating, $uid, $vid) {
        $ret['status'] = 'fail';
        $ret['errorMessage'] = null;

        $rating = htmlspecialchars($rating);
        $uid = htmlspecialchars($uid);
        $vid = htmlspecialchars($vid);

        // Check if connection to database was successfully established.
        if ($this->db == null) {
            $ret['errorMessage'] = 'Kunne ikke koble til databasen.';
            return $ret;
        }

        $res = $this->getUserRating($uid, $vid);

        if ($res['status'] != 'ok') {
            $sql = "INSERT INTO rated (vid, uid, rating) VALUES (:vid, :uid, :rating)";
            $sth = $this->db->prepare ($sql);
            $sth->bindParam(':vid', $vid);
            $sth->bindParam(':uid', $uid);
            $sth->bindParam(':rating', $rating);
            $sth->execute();
    
            if ($sth->rowCount()==1) {
                $ret['status'] = 'ok';
                $ret['cid'] = $this->db->lastInsertId();
            }
            else {
                $ret['errorMessage'] = 'Fikk ikke lagt til rating i databasen.';
            }
        }
        else {
            $ret['errorMessage'] = 'Har allerede lagt til en rating. Rating er ' . $res['rating'];
        }
        

        return $ret;
    }

    /**
     * Search after videos.
     * 
     * @param string The search text.
     * @param array[] An associative array with bool's to what to search through (example $options['title'] = true, $options['description'] = true, $options['timestamp'] = false).
     * @param int The id to the video to rate.
     * 
     * @return array[] Returns an associative array with the fields 'status' and 'errorMessage' (if error) + a 'result'-field which is an associative array with the results with [0], [1], etc. for each result (see get(..) for more info).
     */
    public function search($searchText, $options = null) {
        $ret['status'] = 'fail';
        $ret['errorMessage'] = null;

        // Check if connection to database was successfully established.
        if ($this->db == null) {
            $ret['errorMessage'] = 'Kunne ikke koble til databasen.';
            return $ret;
        }

        $searchText = htmlspecialchars($searchText);
        $sql;                                               // Set sql-variable ready.

        // No options set, search through all meaningful columns.
        if ($options = null) {
            $sql = "SELECT vid FROM video WHERE title LIKE %" . $searchText . "% OR description LIKE %" . $searchText . "% OR topic LIKE %" . $searchText . "% OR course_code LIKE %" . $searchText . "% OR timestamp LIKE %" . $searchText . "%";
        }
        else {                                         // Some options most likely set
            // Check that something is actually set, if not, give error.
            if((isset($options['title']) && $options['title'] == true)
                || (isset($options['description']) && $options['description'] == true)
                || (isset($options['topic']) && $options['topic'] == true)
                || (isset($options['course_code']) && $options['course_code'] == true)
                || (isset($options['timestamp']) && $options['timestamp'] == true)) {
                $sql = "SELECT vid FROM video WHERE";
                if (isset($options['title']) && $options['title'] == true) {
                    $sql = $sql . " title LIKE %:text%";
                }
                if (isset($options['description']) && $options['description'] == true) {
                    $sql = $sql . " OR description LIKE %:text%";
                }
                if (isset($options['topic']) && $options['topic'] == true) {
                    $sql = $sql . " OR topic LIKE %:text%";
                }
                if (isset($options['course_code']) && $options['course_code'] == true) {
                    $sql = $sql . " OR course_code LIKE %:text%";
                }
                if (isset($options['timestamp']) && $options['timestamp'] == true) {
                    $sql = $sql . " OR timestamp LIKE %:text%";
                }
            }
            else {
                $ret['errorMessage'] = 'Ingen valg er satt, kan derfor ikke gi noen resultater.';
                return $ret;
            }
        }

        $sth = $this->db->prepare($sql);
        $sth->bindParam(':text', $searchText);
        $sth->execute();

        $i = 0;

        $ret['status'] = 'ok';

        while($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $ret['result'][$i] = $this->get(htmlspecialchars($row['vid']));
            $i++;
        }

        return $ret;
    }

    /**
     * Get the average rating for a video.
     * 
     * @param int $vid is the id of the video to get average rating for.
     * 
     * @return array[] An associative array width the fields 'status' (as always 'ok' or 'fail'), and 'errorMessage' if 'status' is 'fail', and if 'status' is 'ok' a field 'rating'.
     */
    public function getRating($vid) {
        $ret['status'] = 'fail';
        $ret['rating'] = null;
        $ret['errorMessage'] = "Vi fikk ikke noe resultat";

        $vid = htmlspecialchars($vid);

        $sql = "SELECT AVG(rating) AS rating FROM rated WHERE vid = :vid GROUP BY vid";

        $sth = $this->db->prepare($sql);
        $sth->bindParam(":vid", $vid);
        $sth->execute();

        while($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $ret['status'] = 'ok';
            $ret['rating'] = htmlspecialchars($row['rating']);
        }
        
        return $ret;
    }

    /**
     * Returns the user rating for a particular video.
     * 
     * @param int $uid is the id for the user to check.
     * @param int $vid is the id to the video to check.
     * 
     * @return array[] An associative array with the fields 'status' (with 'ok' if it finds something and 'fail' if an error or if it doesn't find anything), 'errorMessage' if 'status' is 'fail' and 'rating' if 'status' is 'ok'.
     */
    public function getUserRating($uid, $vid) {
        $ret['status'] = 'fail';
        $ret['rating'] = null;
        $ret['errorMessage'] = "Vi fikk ikke noe resultat";

        $vid = htmlspecialchars($vid);
        $uid = htmlspecialchars($uid);

        $sql = "SELECT rating FROM rated WHERE vid = :vid AND uid = :uid";

        $sth = $this->db->prepare($sql);
        $sth->bindParam(":vid", $vid);
        $sth->bindParam(":uid", $uid);
        $sth->execute();

        while($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $ret['status'] = 'ok';
            $ret['rating'] = htmlspecialchars($row['rating']);
        }
        
        return $ret;
    }
}
