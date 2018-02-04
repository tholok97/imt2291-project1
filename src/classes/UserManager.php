<?php

require_once dirname(__FILE__) . '/DB.php';
require_once dirname(__FILE__) . '/../constants.php';
require_once dirname(__FILE__) . '/../../config.php';

/**
 * Manages users in the systems. Talks to the db.
 */
class UserManager {

    /**
     * database connection
     */
    private $dbh = null;

    public function __construct($dbh) {
        $this->dbh = $dbh;
    }

    /**
     * is this a valid user?
     *
     * @param int $uid 
     *
     * @return assosiative with ['status'], ['message'], ['valid']
     */
    public function isValidUser($uid) {
        
        // prepare ret
        $ret['status'] = 'fail';
        $ret['valid'] = null;
        $ret['message'] = '';

        // try and check if valid
        try {

            $stmt = $this->dbh->prepare('SELECT * FROM user WHERE user.uid = :uid');
            $stmt->bindParam('uid', $uid);
            $ok = $stmt->execute();

            if (!$ok) {
                $ret[':message'] = 'PDO didn\'t execute right';
            } else {
                $ret['status'] = 'ok';
                $ret[':valid'] = ($stmt->fetchAll().length() > 0);
            }

        } catch (PDOException $ex) {
            $ret['message'] = 'PDO exception: ' . $ex->getMessage();
        }

        return $ret;
    }


    /**
     * Try and login
     *
     * @param string $username
     * @param string $password
     *
     * @return assosiative array that looks like: ret['status'], ret['uid'], 
     *         ret['message']
     */
    public function login($username, $password) {

        // prepare ret
        $ret['status'] = 'fail';
        $ret['uid'] = null;
        $ret['message'] = '';

        // try and check if valid user
        try {

            $hash = password_hash($password, PASSWORD_DEFAULT);

            //print_r($hash);

            $stmt = $this->dbh->prepare('SELECT * FROM User WHERE User.username = :username AND User.password_hash = :hash');

            $stmt->bindValue(':username', $username, PDO::PARAM_STR);
            $stmt->bindValue(':hash', $hash, PDO::PARAM_STR);

            print_r($stmt);


            if (!$stmt->execute()) {
                $ret['message'] = 'PDO didn\'t execute right';
            } else {

                $rows = $stmt->fetchAll();

                // if user exists ->  YAY
                if (count($rows) > 0) {

                    $ret['status'] = 'ok';
                    $ret['uid'] = $rows[0]['uid'];
                }
            }

        } catch (PDOException $ex) {

            $ret['message'] = $ex->getMessage();
        }

        return $ret;
    }

    /**
     * Adds a user to the system.
     *
     * @param User $user User to be added to the system
     * @param string $password The password to give the user
     *
     * @return TODO
     */
    public function addUser($user, $password) {

        // TODO
    }

    /**
     * Gets a user from the system based on uid
     *
     * @param int $uid The id of the user
     *
     * @return TODO
     */
    public function getUser($uid) {

        // TODO
    }

    /**
     * Removes a user from the system based on uid
     *
     * * Should remove all rows associated with that user also
     *
     * @param int $uid The id of the user
     *
     * @return TODO
     */
    public function removeUser($uid) {
        
        // TODO
    }
}
