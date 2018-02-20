<?php

require_once dirname(__FILE__) . '/DB.php';
require_once dirname(__FILE__) . '/User.php';
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
            $stmt->bindParam(':thumbnail', $thumbnail);

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
     * Adds video to playlist
     * @param $vid
     * @param $pid
     * @return assoc array with fields: status, message
     */
    public function addVideoToPlaylist($vid, $pid) {

        // prepare ret
        $ret['status'] = 'fail';
        $ret['message'] = "";

        try {

            $stmt = $this->dbh->prepare('
INSERT INTO in_playlist (vid, pid, position)
VALUES (:vid, :pid, :position)
            ');


            $stmt->bindParam(':vid', $vid);
            $stmt->bindParam(':pid', $pid);
            $stmt->bindParam(':position', $pid); // TODO OOOOO!

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
     * Returns Playlist object of playlist in system
     * @param $pid
     * @return assoc array with fields: status, message, playlist
     */
    public function getPlaylist($pid) {

        // prepare ret
        $ret['status'] = 'fail';
        $ret['message'] = "";
        $ret['playlist'] = null;

        // TODO



        return $ret;
    }

    /**
     * Update playlist with values given
     * @param $pid
     * @param $title
     * @param $description
     * @param $thumbnail
     * @return assoc array with fields: status, message
     */
    public function updatePlaylist($pid, $title, $description, $thumbnail) {

        // prepare ret
        $ret['status'] = 'fail';
        $ret['message'] = "";
        $ret['playlist'] = null;

        // TODO


        return $ret;
    }
}

/*
 * TODO
 *
 *  addPlaylist (takes playlist as parameter and adds it to db. gives back pid)
 *  addToPlaylist (takes video and playlist and adds video to playlist)
 *  addMaintainerToPlaylist 
 *  getPlaylist (returns playlist object with all contents)
 *  removePlaylist (takes pid as parameter and removes it)
 *  updatePlaylist (takes playlist (with pid set) and updates metainfo
 *  removeFromPlaylist (takes video and playlist and removes video from playlist)
 *  reorderVideo (takes an old and new position, and swaps the video at the old 
        position with the one at the new
 */
