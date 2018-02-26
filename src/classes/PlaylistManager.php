<?php

require_once dirname(__FILE__) . '/DB.php';
require_once dirname(__FILE__) . '/User.php';
require_once dirname(__FILE__) . '/UserManager.php';
require_once dirname(__FILE__) . '/Playlist.php';
require_once dirname(__FILE__) . '/VideoManager.php';
require_once dirname(__FILE__) . '/../constants.php';
require_once dirname(__FILE__) . '/../../config.php';

/**
 * Manages playlists in the system. Interfaces with db
 */
class PlaylistManager {

    /**
     * database connection
     */
    private $dbh = null;

    public function __construct($dbh) {
        $this->dbh = $dbh;
    }


    /**
     * Add new playlist with title and description
     * @param $title
     * @param $description
     * @param $thumbnail
     * @return assoc array with fields: status, pid, message
     */
    public function addPlaylist($title, $description, $thumbnail) {

        // prepare ret
        $ret['status'] = 'fail';
        $ret['pid'] = null;
        $ret['message'] = "";

        try {
            
            $stmt = $this->dbh->prepare('
INSERT INTO playlist (title, description, thumbnail)             
VALUES (:title, :description, :thumbnail)
            ');

            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':description', $description);
            $stmt->bindValue(':thumbnail', getThumbnail($thumbnail));

            if ($stmt->execute()) {

                $ret['pid'] = $this->dbh->lastInsertId();
                $ret['status'] = 'ok';
            } else {
                $ret['message'] = "Statement didn't execute correclty";
            }

        } catch (PDOException $ex) {
            $ret['message'] = $ex->getMessage();
        }

        return $ret;

    }

    /**
     * Get position next video in playlist should have
     * @param $pid
     * @return assoc array with fields: status, message, posiiton
     */
    public function getNextPosition($pid) {

        // prepare ret
        $ret['status'] = 'fail';
        $ret['message'] = "";
        $ret['position'] = null;


        try {
            
            $stmt = $this->dbh->prepare('
SELECT MAX(position)
FROM in_playlist
WHERE pid=:pid
            ');

            $stmt->bindParam(':pid', $pid);

            if ($stmt->execute()) {

                $ret['status'] = 'ok';

                $max = $stmt->fetchAll()[0]['MAX(position)'];


                // if no entries yet -> 1
                // if entry -> max + 1
                if ($max == null) {
                    $ret['position'] = 1;
                } else {
                    $ret['position'] = $max + 1;
                }

            } else {
                $ret['message'] = "Statement didn't execute correclty";
            }

        } catch (PDOException $ex) {
            $ret['message'] = $ex->getMessage();
        }



        return $ret;

    }

    /**
     * Adds video to playlist
     * @param $vid
     * @param $pid
     * @return assoc array with fields: status, message
     */
    public function addVideoToPlaylist($vid, $pid) {

        // prepare ret
        $ret['status'] = 'fail';
        $ret['message'] = "";

        // FETCH NEXT POSITION
        $res_position = $position = $this->getNextPosition($pid);

        if ($res_position['status'] == 'fail') {
            $ret['message'] = $res_position['message'];
            return $ret;
        }


        try {

            $stmt = $this->dbh->prepare('
INSERT INTO in_playlist (vid, pid, position)
VALUES (:vid, :pid, :position)
            ');


            $stmt->bindParam(':vid', $vid);
            $stmt->bindParam(':pid', $pid);
            $stmt->bindParam(':position', $res_position['position']);

            if ($stmt->execute()) {
                $ret['status'] = 'ok';
            } else {
                $ret['message'] = "Statement didn't execute correclty";
            }

        } catch (PDOException $ex) {
            $ret['message'] = $ex->getMessage();
        }

        return $ret;

    }

    /**
     * add maintainer to playlist
     * @param $uid
     * @param $pid
     * @return assoc array with fields: status, message
     */
    public function addMaintainerToPlaylist($uid, $pid) {

        // prepare ret
        $ret['status'] = 'fail';
        $ret['message'] = "";

        try {

            $stmt = $this->dbh->prepare('
INSERT INTO maintains (uid, pid)
VALUES (:uid, :pid)
            ');


            $stmt->bindParam(':uid', $uid);
            $stmt->bindParam(':pid', $pid);

            if ($stmt->execute()) {
                $ret['status'] = 'ok';
            } else {
                $ret['message'] = "Statement didn't execute correclty";
            }

        } catch (PDOException $ex) {
            $ret['message'] = $ex->getMessage();
        }

        return $ret;
    }

    /**
     * Removes video from playlist
     * @param $vid
     * @param $pid
     * @return assoc array with fields: status, message
     */
    public function removeVideoFromPlaylist($vid, $pid) {


        // prepare ret
        $ret['status'] = 'fail';
        $ret['message'] = "";

        try {

            $stmt = $this->dbh->prepare('
DELETE FROM in_playlist
WHERE vid=:vid AND pid=:pid
            ');


            $stmt->bindParam(':vid', $vid);
            $stmt->bindParam(':pid', $pid);

            if ($stmt->execute()) {
                $ret['status'] = 'ok';
            } else {
                $ret['message'] = "Statement didn't execute correclty";
            }

        } catch (PDOException $ex) {
            $ret['message'] = $ex->getMessage();
        }

        return $ret;

    }

    /**
     * Removes maintainer from playlist
     * @param $uid
     * @param $pid
     * @return assoc array with fields: status, message
     */
    public function removeMaintainerFromPlaylist($uid, $pid) {

        // prepare ret
        $ret['status'] = 'fail';
        $ret['message'] = "";

        try {

            $stmt = $this->dbh->prepare('
DELETE FROM maintains
WHERE uid=:uid AND pid=:pid
            ');


            $stmt->bindParam(':uid', $uid);
            $stmt->bindParam(':pid', $pid);

            if ($stmt->execute()) {
                $ret['status'] = 'ok';
            } else {
                $ret['message'] = "Statement didn't execute correclty";
            }

        } catch (PDOException $ex) {
            $ret['message'] = $ex->getMessage();
        }

        return $ret;
    }

    /**
     * Completely removes playlist (including references from other tables)
     * @param $pid
     * @return assoc array with fields: status, message
     */
    public function removePlaylist($pid) {

        // prepare ret
        $ret['status'] = 'fail';
        $ret['message'] = "";


        // REMOVE ALL MAINTAINERS
        try {

            $stmt = $this->dbh->prepare('
DELETE FROM maintains
WHERE pid=:pid
            ');


            $stmt->bindParam(':pid', $pid);

            if ($stmt->execute()) {

                // NO OP. FLOW CONTINUES AFTER CATCH

            } else {
                $ret['message'] = "Statement didn't execute correclty";
                return $ret;
            }

        } catch (PDOException $ex) {
            $ret['message'] = $ex->getMessage();
            return $ret;
        }


        // REMOVE ALL VIDEOS
        try {

            $stmt = $this->dbh->prepare('
DELETE FROM in_playlist
WHERE pid=:pid
            ');


            $stmt->bindParam(':pid', $pid);

            if ($stmt->execute()) {

                // NO OP. FLOW CONTINUES AFTER CATCH

            } else {
                $ret['message'] = "Statement didn't execute correclty";
                return $ret;
            }

        } catch (PDOException $ex) {
            $ret['message'] = $ex->getMessage();
            return $ret;
        }

        // REMOVE PLAYLIST
        try {

            $stmt = $this->dbh->prepare('
DELETE FROM playlist
WHERE pid=:pid
            ');


            $stmt->bindParam(':pid', $pid);

            if ($stmt->execute()) {

                if ($stmt->rowCount() == 1) {
                    // NO OP. FLOW CONTINUES AFTER CATCH
                } else {
                    $ret['message'] = "no playlist was deleted";
                    return $ret;
                }
            } else {
                $ret['message'] = "Statement didn't execute correclty";
                return $ret;
            }

        } catch (PDOException $ex) {
            $ret['message'] = $ex->getMessage();
            return $ret;
        }

        $ret['status'] = 'ok';


        return $ret;
    }

    /**
     * Update playlist with values given
     * @param $pid
     * @param $title
     * @param $description
     * @return assoc array with fields: status, message
     */
    public function updatePlaylist($pid, $title, $description) {

        // prepare ret
        $ret['status'] = 'fail';
        $ret['message'] = "";

        try {

            $stmt = $this->dbh->prepare('
UPDATE playlist
SET title=:title, description=:description
WHERE pid=:pid
            ');


            $stmt->bindParam(':pid', $pid);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':description', $description);

            if ($stmt->execute()) {
                if ($stmt->rowCount()) {
                    $ret['status'] = 'ok';
                } else {
                    $ret['message'] = "No row updated";
                }
            } else {
                $ret['message'] = "Statement didn't execute correclty";
            }

        } catch (PDOException $ex) {
            $ret['message'] = $ex->getMessage();
        }



        return $ret;
    }

    /**
     * Returns Playlist object of playlist in system
     * @param $pid
     * @return assoc array with fields: status, message, playlist
     */
    public function getPlaylist($pid) {

        // prepare ret
        $ret['status'] = 'fail';
        $ret['message'] = "";
        $ret['playlist'] = null;


        // FETCH ALL MAINTAINERS
        $ret_maintainers = $this->getMaintainersOfPlaylist($pid);

        // if failed -> early return)
        if ($ret_maintainers['status'] == 'fail') {
            $ret['message'] = "Couldn't fetch maintainers : " . $ret_maintainers['message'];
        }


        // FETCH ALL VIDEOS
        $ret_videos= $this->getVideosFromPlaylist($pid);

        // if failed -> early return)
        if ($ret_videos['status'] == 'fail') {
            $ret['message'] = "Couldn't fetch videos : " . $ret_videos['message'];
        }


        // make return playlist
        $ret['playlist'] = new Playlist();

        // set values gathered so far
        $ret['playlist']->maintainers = $ret_maintainers['users'];
        $ret['playlist']->videos = $ret_videos['videos'];
        $ret['playlist']->pid = $pid;


        // FETCH METADATA
        try {

            $stmt = $this->dbh->prepare('
SELECT *
FROM playlist
WHERE pid=:pid
            ');



            $stmt->bindParam(':pid', $pid);

            if ($stmt->execute()) {

                if ($stmt->rowCount() == 1) {
                    $row = $stmt->fetchAll()[0];

                    // set rest of playlist values
                    $ret['playlist']->title = $row['title'];
                    $ret['playlist']->description = $row['description'];

                } else {
                    $ret['message'] = "More than one playlist returned from db";
                    return $ret;
                }


            } else {
                $ret['message'] = "Statement didn't execute correclty";
                return $ret;
            }

        } catch (PDOException $ex) {
            $ret['message'] = $ex->getMessage();
            return $ret;
        }



        // if got this far -> status ok
        $ret['status'] = 'ok';

        return $ret;
    }


    /**
     * Swap positions of two videos in playlist
     *
     * @param $pos1
     * @param $pos2
     * @param $pid
     * @return assoc array with fields: status, message
     */
    public function swapPositionsInPlaylist($pos1, $pos2, $pid) {

        // prepare ret
        $ret['status'] = 'fail';
        $ret['message'] = "";

        $entryPos1Vid = -1;
        $entryPos2Vid = -1;

        // get video with pos1
        try {

            $stmt = $this->dbh->prepare('
SELECT vid
FROM in_playlist
WHERE pid=:pid AND position=:position
            ');

            $stmt->bindParam(':pid', $pid);
            $stmt->bindParam(':position', $pos1);

            if ($stmt->execute()) {
                $rows = $stmt->fetchAll();
                if (count($rows) > 0) {
                    $entryPos1Vid = $rows[0]['vid'];
                } else {
                    $ret['message'] = "No entries for pos1!!";
                    return $ret;
                }
            } else {
                $ret['message'] = "Statement for pos1 didn't execute right";
                return $ret;
            }

        } catch (PDOException $ex) {
            $ret['message'] = $ex->getMessage();
            return $ret;
        }




        // get video with pos2
        try {

            $stmt = $this->dbh->prepare('
SELECT vid
FROM in_playlist
WHERE pid=:pid AND position=:position
            ');

            $stmt->bindParam(':pid', $pid);
            $stmt->bindParam(':position', $pos2);

            if ($stmt->execute()) {
                $rows = $stmt->fetchAll();
                if (count($rows) > 0) {
                    $entryPos2Vid = $rows[0]['vid'];
                } else {
                    $ret['message'] = "No entries for pos1!!";
                    return $ret;
                }
            } else {
                $ret['message'] = "Statement for pos2 didn't execute right";
                return $ret;
            }

        } catch (PDOException $ex) {
            $ret['message'] = $ex->getMessage();
            return $ret;
        }





        // update the db entry for pos 1
        try {

            $stmt = $this->dbh->prepare('
UPDATE in_playlist
SET position=:position
WHERE vid=:vid AND pid=:pid
            ');

            $stmt->bindParam(':position', $pos2);
            $stmt->bindParam(':vid', $entryPos1Vid);
            $stmt->bindParam(':pid', $pid);

            if ($stmt->execute()) {

                if ($stmt->rowCount() > 0) {

                    // NO OP. FLOW CONTINUES UNDER CATCH
                } else {
                    $ret['message'] = "Didn't update any entries..";
                    return $ret;
                }

            } else {
                $ret['message'] = "Statement to update pos1 didn't execute right";
                return $ret;
            }

        } catch (PDOException $ex) {
            $ret['message'] = $ex->getMessage();
            return $ret;
        }

        // update the db entry for pos 2
        try {

            $stmt = $this->dbh->prepare('
UPDATE in_playlist
SET position=:position
WHERE vid=:vid AND pid=:pid
            ');

            $stmt->bindParam(':position', $pos1);
            $stmt->bindParam(':vid', $entryPos2Vid);
            $stmt->bindParam(':pid', $pid);

            if ($stmt->execute()) {

                if ($stmt->rowCount() > 0) {

                    // NO OP. FLOW CONTINUES UNDER CATCH
                } else {
                    $ret['message'] = "Didn't update any entries..";
                    return $ret;
                }

            } else {
                $ret['message'] = "Statement to update pos2 didn't execute right";
                return $ret;
            }

        } catch (PDOException $ex) {
            $ret['message'] = $ex->getMessage();
            return $ret;
        }







        // if got this far -> ok
        $ret['status'] = 'ok';


        return $ret;
    }

    /**
     * returns all associated maintainers of playlist in db
     * @param $pid
     * @return assoc array with fields: status, message, users (User objects)
     */
    public function getMaintainersOfPlaylist($pid) {

        // prepare ret
        $ret['message'] = "";
        $ret['status'] = 'fail';
        $ret['users'] = array();

        // array to hold uids of maintainers
        $uids = array();


        // GET UIDS
        try {
            
            $stmt = $this->dbh->prepare('
SELECT uid
FROM maintains
WHERE pid=:pid
            ');

            $stmt->bindParam(':pid', $pid);

            if ($stmt->execute()) {

                // add all (if any) found uids to uids
                foreach($stmt->fetchAll() as $row) {
                    array_push($uids, $row['uid']);
                }

            } else {
                $ret['message'] = "Statement didn't execute correclty";
                return $ret;
            }

        } catch (PDOException $ex) {
            $ret['message'] = $ex->getMessage();
            return $ret;
        }

        // prepare userManager
        $userManager = new UserManager($this->dbh);

        // FOR EACH UID IN UIDS -> FETCH USER OBJECT FROM USERMANAGER
        foreach ($uids as $uid) {
            $ret_getuser = $userManager->getUser($uid);

            if ($ret_getuser['status'] == 'fail') {
                $ret['message'] = "Couldn't get user object : " . $ret_getuser['message'];
                return $ret;
            } else {
                array_push($ret['users'], $ret_getuser['user']);
            }
        }


        // if got this far -> ok
        $ret['status'] = 'ok';

        return $ret;
    }

    /**
     * get all videos (Video objects) associated with playlist
     * @param $pid
     * @return assoc array with fields: status, message, videos
     */
    public function getVideosFromPlaylist($pid) {

        // prepare ret
        $ret['status'] = 'fail';
        $ret['message'] = "";
        $ret['videos'] = array();

        // array to hold vis
        $vids = array();

        // FIRST GET ALL VID'S
        try {
            
            $stmt = $this->dbh->prepare('
SELECT vid, position
FROM in_playlist
WHERE pid=:pid
ORDER BY position
            ');

            $stmt->bindParam(':pid', $pid);

            if ($stmt->execute()) {

                // add all (if any) found vids to vids
                foreach($stmt->fetchAll() as $row) {
                    array_push($vids, $row);
                }

            } else {
                $ret['message'] = "Statement didn't execute correclty";
                return $ret;
            }

        } catch (PDOException $ex) {
            $ret['message'] = $ex->getMessage();
            return $ret;
        }




        // get videomanager
        $videoManager = new VideoManager($this->dbh);

        // FOR EACH VID -> APPEND CORRESPONDING VIDEO OBJECT TO RET
        foreach ($vids as $vid) {

            $ret_getvideo = $videoManager->get($vid['vid'], false);

            if ($ret_getvideo['status'] == 'fail') {
                $ret['message'] = "Couldn't get video object : " . $ret_getvideo['message'];
                return $ret;
            } else {
                $video = $ret_getvideo['video'];
                $video->position = $vid['position'];
                array_push($ret['videos'], $video);
            }
        }

        // if got this far -> success
        $ret['status'] = 'ok';

        return $ret;
    }

    /**
     * Returns list of search results given search string and stuff to search for
     * @param $searchfor string to search for
     * @param $searchwhere either description or title
     * @return assoc array with fields: status, message, playlists (array of playlist objects)
     */
    public function searchPlaylists($searchfor, $searchwhere) {

        // prepare ret
        $ret['status'] = 'fail';
        $ret['message'] = "";
        $ret['playlists'] = array();

        // Can't use PDO prepared statements for field, so have to do 
        // anti-hack test manually
        if ($searchwhere != 'title' && $searchwhere != 'description') {
            $ret['messsage'] = 'Invalid field specified';
            return $ret;
        }

        // try and search
        try {

            $stmt = $this->dbh->prepare("
SELECT *
FROM playlist
WHERE $searchwhere LIKE :search
            ");

            $stmt->bindValue(':search', '%' . $searchfor . '%');

            if ($stmt->execute()) {
                foreach ($stmt->fetchAll() as $row) {

                    // build statement objects
                    $ret_getplaylist = $this->getPlaylist($row['pid']);

                    if ($ret_getplaylist['status'] == 'fail') {
                        $ret['message'] = "Couldn't bould playlist from pid in search : " . $ret_getplaylist['message'];
                        return $ret;
                    }

                    array_push($ret['playlists'], $ret_getplaylist['playlist']);

                }
                $ret['status'] = 'ok';
            } else {
                $ret['message'] = "Statement didn't execute right";
            }

        } catch (PDOException $ex) {
            $ret['message'] = $ex->getMessage();
        }


        return $ret;
        
    }

    /**
     * Search for searchstring in title and/or description
     * @param $searchfor
     * @param $searchwhere array out of title, description
     * @return assoc array with fields: status, message, playlists (array of playlist objects)
     */
    public function searchPlaylistsMultipleFields($searchfor, $searchwheres) {

        // prepare ret
        $ret['status'] = 'fail';
        $ret['message'] = "";
        $ret['playlists'] = array();

        // if no fields given just return ok
        if (count($searchwheres) == 0) {
            $ret['status'] = 'ok';
            return $ret;
        }

        // for each searchwhere in searchwheres -> search for searchfor
        foreach ($searchwheres as $searchwhere) {

            $ret_search = $this->searchPlaylists($searchfor, $searchwhere);

            if ($ret_search['status'] != 'ok') {
                $ret['message'] = "Couldn't do search for " . $searchfor . ". Error message: " . $searchwhere;
                return $ret;
            }


            $ret['playlists'] = array_unique(
                array_merge(
                    $ret['playlists'], 
                    $ret_search['playlists']
                ), SORT_REGULAR);


        }

        // if got this far -> ok
        $ret['status'] = 'ok';

        return $ret;
    }

    /**
     * Register a user as a subscriber to a playlist in the system
     * @param $user
     * @param $pid
     * @return assoc array with fields status, message
     */
    public function subscribeUserToPlaylist($uid, $pid) {
        
        // prepare ret
        $ret['status'] = 'fail';
        $ret['message'] = "";

        try {
            
            $stmt = $this->dbh->prepare('
INSERT INTO subscribes_to (uid, pid)
VALUES (:uid, :pid)
            ');

            $stmt->bindParam(':uid', $uid);
            $stmt->bindParam(':pid', $pid);

            if ($stmt->execute()) {

                $ret['status'] = 'ok';

            } else {
                $ret['message'] = "Statement didn't execute correclty";
            }

        } catch (PDOException $ex) {
            $ret['message'] = $ex->getMessage();
        }

        return $ret;
    }

    /**
     * is user subscribed to playlist?
     * @param $uid
     * @param $pid
     * @return assoc array with fields: status, message, subscribed ('true' / 'false')
     */
    public function isSubscribed($uid, $pid) {

        // prepare ret
        $ret['status'] = 'fail';
        $ret['message'] = "";
        $ret['subscribed'] = null;


        try {
            
            $stmt = $this->dbh->prepare('
SELECT *
FROM subscribes_to
WHERE uid=:uid AND pid=:pid
            ');

            $stmt->bindParam(':uid', $uid);
            $stmt->bindParam(':pid', $pid);

            if ($stmt->execute()) {

                $ret['status'] = 'ok';

                if ($stmt->rowCount() == 1) {
                    $ret['subscribed'] = 'true';
                } else {
                    $ret['subscribed'] = 'false';
                }


            } else {
                $ret['message'] = "Statement didn't execute correclty";
            }

        } catch (PDOException $ex) {
            $ret['message'] = $ex->getMessage();
        }


        return $ret;

    }


    /**
     * Get playlists a user is subscribed to
     * @param $uid
     * @return assoc array with fields: status, message, playlists
     */
    public function getSubscribedPlaylists($uid) {

        // prepare ret
        $ret['status'] = 'fail';
        $ret['message'] = "";
        $ret['playlists'] = array();

        try {
            
            $stmt = $this->dbh->prepare('
SELECT pid
FROM subscribes_to
WHERE uid=:uid
            ');

            $stmt->bindParam(':uid', $uid);

            if ($stmt->execute()) {


                // for each pid subscribed to, add playlist to ret
                foreach ($stmt->fetchAll() as $row) {

                    // get playlist with pid
                    $ret_get = $this->getPlaylist($row['pid']);

                    if ($ret_get['status'] != 'ok') {
                        $ret['message'] = "Couldn't get subscribed playlist : " . $ret_get['message'];
                        return $ret;
                    }

                    array_push($ret['playlists'], $ret_get['playlist']);
                }

            } else {
                $ret['message'] = "Statement didn't execute correclty";
            }

        } catch (PDOException $ex) {
            $ret['message'] = $ex->getMessage();
        }

        // if got this far -> ok
        $ret['status'] = 'ok';

        return $ret;
    }




    /**
     * return playlists a user is a maintainer of
     * @param $uid
     * @return assoc array of fields: status, message, playlists
     */
    public function getPlaylistsUserMaintains($uid) {


        // prepare ret
        $ret['status'] = 'fail';
        $ret['message'] = "";
        $ret['playlists'] = array();

        try {
            
            $stmt = $this->dbh->prepare('
SELECT pid
FROM maintains
WHERE uid=:uid
            ');

            $stmt->bindParam(':uid', $uid);

            if ($stmt->execute()) {


                // for each pid subscribed to, add playlist to ret
                foreach ($stmt->fetchAll() as $row) {

                    // get playlist with pid
                    $ret_get = $this->getPlaylist($row['pid']);

                    if ($ret_get['status'] != 'ok') {
                        $ret['message'] = "Couldn't get maintainers playlist : " . $ret_get['message'];
                        return $ret;
                    }

                    array_push($ret['playlists'], $ret_get['playlist']);
                }

            } else {
                $ret['message'] = "Statement didn't execute correclty";
            }

        } catch (PDOException $ex) {
            $ret['message'] = $ex->getMessage();
        }

        // if got this far -> ok
        $ret['status'] = 'ok';

        return $ret;
    }

}

/*
 * TODO
 *
 *  getPlaylist (returns playlist object with all contents)
 *  reorderVideo (takes an old and new position, and swaps the video at the old 
        position with the one at the new
 */
