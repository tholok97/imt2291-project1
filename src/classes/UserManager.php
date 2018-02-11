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

        try {

            $stmt = $this->dbh->prepare('SELECT * FROM user WHERE username=:username');

            $stmt->bindParam(':username', $username);

            if ($stmt->execute()) {

                foreach ($stmt->fetchAll() as $row) {

                    if (password_verify($password, $row['password_hash'])) {
                        $ret['status'] = 'ok';
                        $ret['uid'] = $row['uid'];
                    }
                }

                if ($ret['status'] != 'ok') {
                    $ret['messsage'] = 'No user with given username had that password';
                }
            } else {
                $ret['message'] = "select didn't execute right";
            }

        } catch (PDOException $ex) {
            $ret['status'] = 'fail';
            $ret['message'] = $ex->getMessage();
        }

        return $ret;
    }



    /**
     * add given user with given password to db
     *
     * @param User $user 
     * @param string $password
     *
     * @return assoc array with fields: status, message
     */
    public function addUser($user, $password) {
        
        // prepare ret
        $ret['status'] = 'fail';
        $ret['message'] = '';

        // try and insert
        try {


            // FIRST check that username is unique
            $stmt = $this->dbh->prepare('
SELECT *
FROM user
WHERE username = :username
            ');

            $stmt->bindParam(':username', $user->username);

            if ($stmt->execute()) {
                if (count($stmt->fetchAll()) > 0) {
                    $ret['message'] = "Duplicate username..."; 
                    return $ret;
                }
            } else {
                $ret['message'] = "couldn't assert that username is unique"; 
                return $ret;
            }




            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $this->dbh->prepare('
INSERT INTO user (username, firstname, lastname, password_hash, privilege_level)
VALUES (:username, :firstname, :lastname, :password_hash, :privilege_level)
            ');


            $stmt->bindParam(':username', $user->username);
            $stmt->bindParam(':firstname', $user->firstname);
            $stmt->bindParam(':lastname', $user->lastname);
            $stmt->bindParam(':password_hash', $hash);
            $stmt->bindParam(':privilege_level', $user->privilege_level);

            // try and execute 
            if ($stmt->execute()) {

                // success!
                $ret['status'] = 'ok'; 
            } else {

                // fail...
                $ret['message'] = "PDO didn't execute right";
            }


        } catch (PDOException $ex) {
            $ret['message'] = $ex->getMessage();
        }

        return $ret;
    }



    /**
     * tests if given uid points to valid user
     *
     * @param $uid
     *
     * @return assosiative array that has fields: ['status'], ['valid'], 
     *         ['message']
     */
    public function isValidUser($uid) {

        // prepare ret
        $ret['status'] = 'fail';
        $ret['valid'] = null;
        $ret['message'] = '';

        // try and check db for uid
        try {

            $stmt = $this->dbh->prepare('
                SELECT *
                FROM user
                WHERE uid = :uid
            ');

            $stmt->bindParam(':uid', $uid);

            if ($stmt->execute()) {
                
                if (count($stmt->fetchAll()) > 0) {
                    $ret['valid'] = true; 
                } else {
                    $ret['valid'] = false;
                }
            } else {
                $ret['message'] = "statement didn't execute right";
            }

        } catch (PDOException $ex) {
            $ret['message'] = $ex->getMessage();
        }

        return $ret;

    }

    /*
     * try to get uid of user with given username
     *
     * @param $username
     *
     * @return assoc array with fields: status, uid, message
     */
    public function getUID($username) {
        
        // prepare ret
        $ret['status'] = 'fail';
        $ret['uid'] = null;
        $ret['message'] = '';

        // try and find user with given username, and return uid of this user
        try {

            $stmt = $this->dbh->prepare('
                SELECT *
                FROM user
                WHERE username = :username
            ');

            $stmt->bindParam(':username', $username);

            if ($stmt->execute()) {

                $rows = $stmt->fetchAll();

                // if one row returned -> OK, give uid
                // if no row returned -> fail
                // if more than one row returned -> fail, internal error?
                if (count($rows) == 1) {
                    $ret['uid'] = $rows[0]['uid'];
                    $ret['status'] = 'ok';
                } else if (count($rows) == 0) {
                    $ret['message'] = "select returned nothing (doesn't exist?)";
                    $ret['status'] = 'fail';
                } else {
                    $ret['message'] = "select returned more than one uid???!?!";
                    $ret['status'] = 'fail';
                }
            } else {
                $ret['message'] = "select didn't execute properly";
            }

        } catch (PDOException $ex) {
            $ret['message'] = $ex->getMessage();
        }

        return $ret;
    }

    /**
     * Return array of uid's that are LIKE %inputusername%
     * @param $username
     *
     * @return assoc array with fields: status, uids (array of uids), message
     */
    public function searchUsername($username) {

        // prepare ret
        $ret['status'] = 'fail';
        $ret['uids'] = array();
        $ret['message'] = '';


        // try and fetch uid array
        try {
            
            $stmt = $this->dbh->prepare('
                SELECT uid
                FROM user
                WHERE user.username LIKE :search
            ');

            $stmt->bindValue(':search', '%' . $username . '%');

            if ($stmt->execute()) {

                $ret['status'] = 'ok';



                // for each hit, add to uids
                foreach($stmt->fetchAll() as $row) {
                    array_push($ret['uids'], $row['uid']);
                }
            } else {
                $ret['message'] = 'statement didn\'t execute properly : ' . $stmt->errorCode();
            }

        } catch (PDOException $ex) {
            $ret['message'] = $ex->getMessage();
        }


        return $ret;
    }

}
