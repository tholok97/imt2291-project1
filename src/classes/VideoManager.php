<?php

require_once 'DB.php';
require_once 'Video.php';
require_once 'UserManager.php';
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
    public function upload($title, $description, $uid, $topic, $course_code, $videoRef, $thumbnailRef) {
        $ret['status'] = 'fail';
        $ret['vid'] = null;
        $ret['errorMessage'] = null;

        // Check if connection to database was successfully established.
        if ($this->db == null) {
            $ret['errorMessage'] = 'Kunne ikke koble til databasen.';
            return $ret;
        }

        // If not someone who is trying to hack us.
        if (is_uploaded_file($videoRef['tmp_name']) && is_uploaded_file($thumbnailRef['tmp_name'])) {
            // If file size not too big.
            if($videoRef['size'] <= Constants::MAX_FILESIZE_VIDEO && $thumbnailRef['size'] <= Constants::MAX_FILESIZE_THUMBNAIL) {
                try {
                    $title = htmlspecialchars($title);
                    $description = htmlspecialchars($description);
                    $thumbnail = getThumbnail($thumbnailRef);               // Muligens vi må endre til $_FILES på noen av de under, i tilfelle vil $videoRef bli helt fjernet.
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
                    $sth->bindParam(':mime', $videoRef['type']);
                    $sth->bindParam(':size', $videoRef['size']);
                    $sth->execute();

                    if ($sth->rowCount()==1) {
                        $id = $this->db->lastInsertId();
                        if (!file_exists(dirname(__FILE__) . '/../../uploadedFiles/'.$uid.'/videos')) {      // The user have not uploaded anything before.
                            mkdir(dirname(__FILE__) . '/../../uploadedFiles/'.$uid.'/videos', 0777, true);
                        }
                        if (move_uploaded_file($videoRef['tmp_name'], dirname(__FILE__) . '/../../uploadedFiles/'.$uid.'/videos/'.$id)) {
                            $ret['status'] = 'ok';
                            $ret['vid'] = $id;
                        } else {
                            $sql = "delete from videos where id=$id";
                            $this->db->execute($sql);
                            $ret['errorMessage'] = "Klarte ikke å lagre filen.";
                        }
                    } else {
                        $ret['errorMessage'] = "Klarte ikke å laste opp filen.";
                    }
                } catch (PDOException $ex) {
                    $ret['errorMessage'] = "Problemer med å bruke databasen, prøv igjen senere eller kontakt administrator.";//$ex->getMessage();
                }
            }
            else {
                $ret['errorMessage'] = "Filen er for stor til å kunne lastes opp, vi tillater kun opp til " . (Constants::MAX_FILESIZE_VIDEO/1000/1000) . " MB for video, og " . (Constants::MAX_FILESIZE_THUMBNAIL/1000/1000) . " MB for thumbnail.";
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

        try {
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
        } catch (PDOException $ex) {
            $ret['errorMessage'] = "Problemer med å bruke databasen, prøv igjen senere eller kontakt administrator.";//$ex->getMessage();
        }

        if ($increaseViews == true) {
            try {
                $sql = "UPDATE video SET view_count = :view_count WHERE vid = :vid";
                $sth = $this->db->prepare ($sql);
                $sth->bindParam(':view_count', $views);
                $sth->bindParam(':vid', $vid);
                $sth->execute();
            } catch (PDOException $ex) {
                $ret['errorMessage'] = "Problemer med å bruke databasen, prøv igjen senere eller kontakt administrator.";//$ex->getMessage();
            }
        }
        return $ret;
    }

    /**
     * Returns all videos the user has uploaded.
     * 
     * @param int The user-id to the user who we shall get videos from.
     * 
     * @return array[] Returns an associative array with the fields 'status' and 'errorMessage' (if error) and a 'videos'-field with the result of get() if no error.
     */
    public function getAllUserVideos($uid) {
        $ret['status'] = 'fail';
        $ret['errorMessage'] = null;

        $uid = htmlspecialchars($uid);  // Check that someone does not hack you.

        // Check if a numeric id is more than 0.
        if (!is_numeric($uid) || $uid <= 0) {
            $ret['errorMessage'] = 'Fikk ingen korrekt bruker-id';
            return $ret;
        }

        // Check if connection to database was successfully established.
        if ($this->db == null) {
            $ret['errorMessage'] = 'Kunne ikke koble til databasen.';
            return $ret;
        }

        try {
            $sth = $this->db->prepare('SELECT * FROM video WHERE uid = :uid ORDER BY vid DESC');
            $sth->bindParam(':uid', $uid);
            $sth->execute();

            $i = 0;
            // While-loop will get every video via get()-function.
            while($row = $sth->fetch(PDO::FETCH_ASSOC))
            {
                $ret['status'] = 'ok';
                $ret['videos'][$i] = $this->get(htmlspecialchars($row['vid']), false);
                $i++;
            }
        } catch (PDOException $ex) {
            $ret['errorMessage'] = "Problemer med å bruke databasen, prøv igjen senere eller kontakt administrator.";//$ex->getMessage();
        }
        return $ret;
    }


    /**
     * Edit video-info.
     * 
     * @param int The id to the video to edit.
     * @param int The id to the user who edit (to check for correct user).
     * @param string The new title.
     * @param string The new description.
     * @param string The new topic.
     * @param string The new course_code.
     * 
     * @return array[] Returns an associative array with fields 'status' and evt. 'errorMessage' if status is 'fail'.
     */

    function update($vid, $uid, $title, $description, $topic, $course_code) {
        $ret['status'] = 'fail';
        $ret['errorMessage'] = null;

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

        // Try to check if uid is the correct id

        try {
            $sql = "SELECT uid FROM video WHERE vid = :vid";
            $sth = $this->db->prepare($sql);
            $sth->bindParam(':vid', $vid);
            $sth->execute();
           
            $teacher;
            while($row = $sth->fetch(PDO::FETCH_ASSOC))
            {
                $teacher = $row['uid'];
            }

            // If the person who uploaded video is the one who register, update video-info
            if ($uid == $teacher) {
                $sql = "UPDATE video SET title = :title, description = :description, topic = :topic, course_code = :course_code WHERE vid = :vid";
                $sth = $this->db->prepare ($sql);
                $sth->bindParam(':title', $title);
                $sth->bindParam(':description', $description);
                $sth->bindParam(':topic', $topic);
                $sth->bindParam(':course_code', $course_code);
                $sth->bindParam(':vid', $vid);
                $sth->execute();

                if ($sth->rowCount() > 0) {
                    $ret['status'] = 'ok';
                }
                else {
                    $ret['errorMessage'] = "Klarte ikke å oppdatere video-informasjonen. Prøv igjen senere. Vennligst ta kontakt med administrator om problemet vedvarer.";
                }
            
                $teacher;
                while($row = $sth->fetch(PDO::FETCH_ASSOC))
                {
                    $techer = $row['uid'];
                }
            }
        } catch (PDOException $ex) {
            $ret['errorMessage'] = "Problemer med å bruke databasen, prøv igjen senere eller kontakt administrator.";//$ex->getMessage();
        }
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

        try {
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
        } catch (PDOException $ex) {
            $ret['errorMessage'] = "Problemer med å bruke databasen, prøv igjen senere eller kontakt administrator.";//$ex->getMessage();
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
        $ret['comments'][0]['text'] = 'Ingen kommentarer';      // Will return this string if no comments found
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

        try {
            $sth = $this->db->prepare('SELECT cid, vid, uid, text, timestamp FROM comment WHERE vid = :vid');
            $sth->bindParam(':vid', $vid);
            $sth->execute();
            $ret['status'] = 'ok';
            $i = 0;

            $userManager = new UserManager(DB::getDBConnection());
            // Get all comments
            while($row = $sth->fetch(PDO::FETCH_ASSOC))
            {
                $ret['comments'][$i]['cid'] = $row['cid'];
                $ret['comments'][$i]['vid'] = $row['vid'];
                $ret['comments'][$i]['uid'] = $row['uid'];
                $ret['comments'][$i]['text'] = $row['text'];
                $ret['comments'][$i]['timestamp'] = $row['timestamp'];
                $ret['comments'][$i]['userInfo'] = $userManager->getUser($row['uid']);      //Info about writer of the comment.
                $i++;
            }
        } catch (PDOException $ex) {
            $ret['errorMessage'] = "Problemer med å bruke databasen, prøv igjen senere eller kontakt administrator.";//$ex->getMessage();
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

        if ($rating >= Constants::RATING_MIN && $rating <= Constants::RATING_MAX) {
            $res = $this->getUserRating($uid, $vid);

            if ($res['status'] != 'ok') {
                try {
                    $sql = "INSERT INTO rated (vid, uid, rating) VALUES (:vid, :uid, :rating)";
                    $sth = $this->db->prepare ($sql);
                    $sth->bindParam(':vid', $vid);
                    $sth->bindParam(':uid', $uid);
                    $sth->bindParam(':rating', $rating);
                    $sth->execute();
                    //print_r($sth->errorInfo());

                    if ($sth->rowCount()==1) {
                        $ret['status'] = 'ok';
                        $ret['cid'] = $this->db->lastInsertId();
                    }
                    else {
                        $ret['errorMessage'] = 'Fikk ikke lagt til rating i databasen.';
                    }
                } catch (PDOException $ex) {
                    $ret['errorMessage'] = "Problemer med å bruke databasen, prøv igjen senere eller kontakt administrator.";//$ex->getMessage();
                }
            }
            else {
                $ret['errorMessage'] = 'Har allerede lagt til en rating. Rating er ' . $res['rating'];
            }
        }
        else {
            $ret['errorMessage'] = 'Rating er ikke imellom ' . Constants::RATING_MIN . ' og ' . Constants::RATING_MAX . '.';
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
        $ret['result'] = null;
        $ret['errorMessage'] = null;
        $sql;

        // Check if connection to database was successfully established.
        if ($this->db == null) {
            $ret['errorMessage'] = 'Kunne ikke koble til databasen.';
            return $ret;
        }

        $searchText = htmlspecialchars($searchText);
        $sql;                                               // Set sql-variable ready.

            if((isset($options['title']) && $options['title'] == true)
                || (isset($options['description']) && $options['description'] == true)
                || (isset($options['topic']) && $options['topic'] == true)
            
                || (isset($options['course_code']) && $options['course_code'] == true)
                || (isset($options['timestamp']) && $options['timestamp'] == true)
                || (isset($options['firstname']) && $options['firstname'] == true)
                || (isset($options['lastname']) && $options['lastname'] == true)) {
                $sql = "SELECT vid FROM video WHERE";

                $firstFlag = true;                                         // If first adding in sql.
                if (isset($options['title']) && $options['title'] == true) {
                    if(!$firstFlag) {
                        $sql = $sql . " OR";
                    }
                    $firstFlag = false;
                    $sql = $sql . " title LIKE :text";
                }
                if (isset($options['description']) && $options['description'] == true) {
                    if(!$firstFlag) {
                        $sql = $sql . " OR";
                    }
                    $firstFlag = false;
                    $sql = $sql . " description LIKE :text";
                }
                if (isset($options['topic']) && $options['topic'] == true) {
                    if(!$firstFlag) {
                        $sql = $sql . " OR";
                    }
                    $firstFlag = false;
                    $sql = $sql . " topic LIKE :text";
                }
                if (isset($options['course_code']) && $options['course_code'] == true) {
                    if(!$firstFlag) {
                        $sql = $sql . " OR";
                    }
                    $firstFlag = false;
                    $sql = $sql . " course_code LIKE :text";
                }
                if (isset($options['firstname']) && $options['firstname'] == true) {
                    //Search if necessarry?
                    $userManager = new UserManager($this->db);
                    $userFirstnameSearchRet = $userManager->search($searchText,"firstname");
                    for($i=0;$i < count($userFirstnameSearchRet['uids']);$i++) {
                        if(!$firstFlag) {
                            $sql = $sql . " OR";
                        }
                        $firstFlag = false;
                        $sql = $sql . " uid LIKE " . $userFirstnameSearchRet['uids'][$i];
                    }
                }
                if (isset($options['lastname']) && $options['lastname'] == true) {
                    //Search if necessarry?
                    $userManager = new UserManager($this->db);
                    $userLastnameSearchRet = $userManager->search($searchText,"lastname");
                    for($i=0;$i < count($userLastnameSearchRet['uids']);$i++) {
                        if(!$firstFlag) {
                            $sql = $sql . " OR";
                        }
                        $firstFlag = false;
                        $sql = $sql . " uid LIKE " . $userLastnameSearchRet['uids'][$i];
                    }
                }
                $sql = $sql . " ORDER BY vid DESC";
           }
           else {
            $ret['errorMessage'] = 'Ingen valg er satt, kan derfor ikke gi noen resultater.';
            return $ret;
        }

        try {
            $sth = $this->db->prepare($sql);
            //echo $sql;
            $sth->bindValue(':text', "%" . $searchText . "%");
            
            //Send in all uids for firstname
            /*for($i=0;$i < count($userFirstnameSearchRet['uids']);$i++) {
                $search = "%" . $userFirstnameSearchRet['uids'][$i] . "%";
                $searchParam = ':firstname' . $i;
                $sth->bindParam($searchParam, $search);
            }

            //Send in all uids for firstname
            for($i=0;$i < count($userLastnameSearchRet['uids']);$i++) {
                $search = "%" . $userLastnameSearchRet['uids'][$i] . "%";
                $searchParam = ':lastname' . $i;
                $sth->bindParam($searchParam, $search);
            }*/

            $sth->execute();

            $i = 0;

            $ret['status'] = 'ok';

            while($row = $sth->fetch(PDO::FETCH_ASSOC))
            {
                $ret['result'][$i] = $this->get(htmlspecialchars($row['vid']), false);
                $i++;
            }
        } catch (PDOException $ex) {
            $ret['errorMessage'] = "Problemer med å bruke databasen, prøv igjen senere eller kontakt administrator.";//$ex->getMessage();
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
        $ret['rating'] = 0;                     // Returns if none found.
        $ret['numberOfRatings'] = 0;             // Returns if none found.
        $ret['errorMessage'] = "Vi fikk ikke noe resultat";

        $vid = htmlspecialchars($vid);

        $sql = "SELECT AVG(rating) AS rating, COUNT(rating) AS numberOfRatings FROM rated WHERE vid = :vid GROUP BY vid";

        try {
            $sth = $this->db->prepare($sql);
            $sth->bindParam(":vid", $vid);
            $sth->execute();

            while($row = $sth->fetch(PDO::FETCH_ASSOC))
            {
                $ret['status'] = 'ok';
                $ret['rating'] = htmlspecialchars($row['rating']);
                $ret['numberOfRatings'] = htmlspecialchars($row['numberOfRatings']);
            }
        } catch (PDOException $ex) {
            $ret['errorMessage'] = "Problemer med å bruke databasen, prøv igjen senere eller kontakt administrator.";//$ex->getMessage();
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

        try {
            $sth = $this->db->prepare($sql);
            $sth->bindParam(":vid", $vid);
            $sth->bindParam(":uid", $uid);
            $sth->execute();

            while($row = $sth->fetch(PDO::FETCH_ASSOC))
            {
                $ret['status'] = 'ok';
                $ret['rating'] = htmlspecialchars($row['rating']);
                $ret['errorMessage'] = "";
            }
        } catch (PDOException $ex) {
            $ret['errorMessage'] = "Problemer med å bruke databasen, prøv igjen senere eller kontakt administrator.";//$ex->getMessage();
        }
        
        return $ret;
    }
}
