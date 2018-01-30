<?php

class VideoManager {
  
    /**
     * Upload video to service.
     * 
     * @param string The title of the video.
     * @param string Description of the video.
     * @param int The user who uploaded the video.
     * @param array[] An $_FILES[] array. Example $_FILES['uploadedFile'] as input.
     * @return array[] Returns an associative array with the fields 'status', 'id' and 'errorMessage' (if error).
     */
    public function upload($title, $description, $owner, $videoRef) {
        $ret['status'] = 'fail';
        $ret['id'] = null;
        $ret['errorMessage'] = null;
        
        // If not someone who is trying to hack us.
        if (is_uploaded_file($videoRef['tmp_name'])) {
            // If file size not too big.
            if($videoRef['size'] < 5000 /*Some number*/) {
                $sql = "INSERT INTO videos (title, description, mime, owner) VALUES (:title, :description, :mime, :owner)";
                $sth = $db->prepare ($sql);
                $sth->bindParam(':title', $title);
                $sth->bindParam(':description', $description);
                $sth->bindParam(':mime', $videoRef['type']);
                $sth->bindParam(':owner', $owner);
                $sth->execute ();
                if ($sth->rowCount()==1) {
                    $id = $db->lastInsertId();
                    if (!file_exists('uploadedFiles/'.$owner)) { // Brukeren har ikke lastet opp filer tidligere
                      @mkdir('uploadedFiles/'.$owner);
                    }
                    if (@move_uploaded_file($_FILES['fileToUpload']['tmp_name'], "uploadedFiles/{$owner}/$id")) {
                      echo $twig->render('uploadSuccess.html', array('fname'=>$name, 'size'=>$size));
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
     * Returns video and info.
     * 
     * @param int The video's id.
     * 
     * @return array[] Returns an associative array with the fields 'status', 'id', 'title', 'url', 'description', 'stars', 'comments',  and 'errorMessage' (if error).
     */
    public function get($id) {
        $ret['status'] = 'fail';
        $ret['id'] = null;
        $ret['title'] = null;
        $ret['url'] = null;
        $ret['description'] = null;
        $ret['stars'] = null;           //? Hvis jeg forstår det riktig blir dette i tilfelle et gjennomsnitt av alle ratingene brukerne gjør
        $ret['comments'] = null;
        $ret['errorMessage'] = null;
        
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
    public function comment($title, $description, $stars, $userID, $videoID) {
        $ret['status'] = 'fail';
        $ret['id'] = null;
        $ret['errorMessage'] = null;
        
        return $ret;
    }
  }