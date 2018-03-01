<?php

require_once dirname(__FILE__) . '/DB.php';
require_once dirname(__FILE__) . '/User.php';
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
        $ret['message'] = 'test';

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
     * @return assoc array with fields: status, message, uid
     */
    public function addUser($user, $password) {
        
        // prepare ret
        $ret['status'] = 'fail';
        $ret['message'] = '';
        $ret['uid'] = null;

        // if non-email (if doesn't contain @) -> early fail
        if (strpos($user->username, '@') === false) {
            $ret['message'] = "Invalid username supplied (must be email (must contain @))";
            return $ret;
        }

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
                $ret['uid'] = $this->dbh->lastInsertId();

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
     * Return array of uid's that have a given field searchwere LIKE %searchfor%
     * @param $searchfor what to search for
     * @param $searchwhere should be one of username, firstname, lastname
     *
     * @return assoc array with fields: status, uids (array of uids), message
     */
    public function search($searchfor, $searchwhere) {

        // prepare ret
        $ret['status'] = 'fail';
        $ret['uids'] = array();
        $ret['message'] = '';

        // IMPORTANT: pdo doesn't accept column names as bindable parameters, 
        // so have to sanitize input manually:
        if (!in_array($searchwhere, ['username', 'firstname', 'lastname'])) {
            $ret['message'] = "Searchwhere is not valid. is $searchwhere (see doc string)";
            return $ret;
        }


        // try and fetch uid array
        try {

            // NOTE: $searchwhere was sanitized above!! has to use it like this 
            // because pdo doesn't accept column names as bindable paramters
            $stmt = $this->dbh->prepare("
                SELECT uid
                FROM user
                WHERE $searchwhere LIKE :search
            ");

            $stmt->bindValue(':search', '%' . $searchfor . '%');

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

    /**
     * Search for $searchfor in all fields in $fields and returns uids
     *
     * @param $searchfor
     * @param $fields array of fields to search (out of username, firstname, lastname)
     *
     * @return assoc array with fields: status, uids (array of uids), message
     */
    public function searchMultipleFields($searchfor, $fields) {

        // prepare ret
        $ret['status'] = 'fail';
        $ret['uids'] = array();
        $ret['message'] = '';

        // if no fields given, just return ok
        if (count($fields) == 0) {
            $ret['status'] = 'ok';
        }

        // search with all the specified fields
        foreach($fields as $field) {

            $result = $this->search($searchfor, $field);

            if ($result['status'] == 'fail') {
                $ret['status'] = 'fail';
                $ret['message'] = "One of the searches failed with message: " . $result['message'];
                return $ret;
            }  else {
                $ret['status'] = 'ok';
                $ret['uids'] = array_merge($ret['uids'], $result['uids']);
            }
        }

        return $ret;
    }

    /**
     * Register that user $uid wants privilege level $privilege_level
     *
     * @param $uid
     * @param $privilege_level
     *
     * @return assoc array with fields: status, message
     */
    public function requestPrivilege($uid, $privilege_level) {
        
        // prepare ret value
        $ret['status'] = 'fail';
        $ret['message'] = '';


        // try and update db
        try {
            
            $stmt = $this->dbh->prepare('
                INSERT INTO wants_privilege (uid, privilege_level) 
                VALUES (:uid, :privilege_level)
            ');

            $stmt->bindParam(':uid', $uid);
            $stmt->bindParam(':privilege_level', $privilege_level);

            if ($stmt->execute()) {
                $ret['status'] = 'ok';
            } else {
                $ret['message'] = "Statement didn't exeute right : " . $stmt->errorCode();
            }
        } catch (PDOException $ex) {
            $ret['message'] = $ex->getMessage();
        }

        return $ret;
    }

    /**
     * Return user with given $uid
     * @param $uid
     *
     * @return assoc array with fields: status, user, message
     */
    public function getUser($uid) {

        // parepare ret
        $ret['status'] = 'fail';
        $ret['user'] = null;
        $ret['message'] = '';

        try {
            
            $stmt = $this->dbh->prepare('
                SELECT *
                FROM user
                WHERE uid = :uid
            ');

            $stmt->bindParam(':uid', $uid);

            if ($stmt->execute()) {

                $rows = $stmt->fetchAll();

                // success if more than one uid returned
                if (count($rows) > 0) {
                
                    $ret['status'] = 'ok';

                    $row = $rows[0];
                    $ret['user'] = new User(
                        $row['username'],
                        $row['firstname'],
                        $row['lastname'],
                        $row['privilege_level']
                    );
                    $ret['user']->uid = $row['uid'];

                } else {
                    $ret['message'] = "No users with uid " . $uid;
                }

            } else {
                $ret['message'] = "statememnt didn't execute right : " . $stmt->errorCode();
            }
        } catch (PDOException $ex) {
            $ret['message'] = $ex->getMessage();
        }

        return $ret;
    }

    /**
     * Return what users want what privileges
     * @return assoc array with fields: status, 
     * wants (assoc array with fields: uid, privilege_leve), message
     */
    public function getWantsPrivilege() {

        // prepare ret
        $ret['status'] = 'fail';
        $ret['wants'] = array();
        $ret['message'] = '';

        try {

            $stmt = $this->dbh->prepare('
                SELECT *
                FROM wants_privilege
            ');

            if ($stmt->execute()) {

                $ret['status'] = 'ok';
                
                foreach ($stmt->fetchAll() as $row) {
                    array_push($ret['wants'], $row);
                }
            } else {
                $ret['message'] = "Statement didn't exeute right : " . $stmt->errorCode();
            }

        } catch (PDOException $ex) {
            $ret['message'] = $ex->getMessage();
        }

        return $ret;
    }


    /**
     * Updates user in db with given user object (ignores uid (!!))
     * @param $user
     * @return assoc array with fields: status, message
     */
    public function updateUser($user) {

        // prepare ret
        $ret['status'] = 'fail';
        $ret['message'] = '';

        try {


            $stmt = $this->dbh->prepare('
                UPDATE user
                SET username=:username,
                    firstname=:firstname,
                    lastname=:lastname,
                    privilege_level=:privilege_level
                WHERE uid=:uid
            ');

            $stmt->bindParam(':username', $user->username);
            $stmt->bindParam(':firstname', $user->firstname);
            $stmt->bindParam(':lastname', $user->lastname);
            $stmt->bindParam(':privilege_level', $user->privilege_level);
            $stmt->bindParam(':uid', $user->uid);

            if ($stmt->execute()) {

                if ($stmt->rowCount() == 1) {
                    $ret['status'] = 'ok';
                } else {
                    $ret['message'] = "No update was done (/ too many..)";
                }
            } else {
                $ret['message'] = "Statement didn't execute right : " . $stmt->errorCode();
            }


        } catch (PDOException $ex) {
            $ret['message'] = $ex->getMessage();
        }

        
        return $ret;
    }

    /**
     * Grant given privilege to given user
     * @param $uid
     * @param $privilege_level
     *
     * @return assoc array with fields: status, message
     */
    public function grantPrivilege($uid, $privilege_level) {

        // prepare ret
        $ret['status'] = 'fail';
        $ret['message'] = '';

        // first, update user with new privilege

        // get user
        $ret_getuser = $this->getUser($uid);

        if ($ret_getuser['status'] != 'ok') {
            $ret['message'] = "couldn't get user for update : " . $ret_getuser['message'];
            return $ret;
        }

        // upate user
        $ret_getuser['user']->privilege_level = $privilege_level;
        $ret_update = $this->updateUser($ret_getuser['user']);

        if ($ret_update['status'] != 'ok') {
            $ret['message'] = "couldn't update privilege" . $ret_update['message'];
            return $ret;
        }

        $ret['status'] = 'ok';

        
        return $ret;

    }

    /**
     * Delete request entry that looks like $uid, $privilege_level
     * @param $uid
     * @param $privilege_level
     * @return assoc array with fields: status, message
     */
    public function deletePrivilegeRequest($uid, $privilege_level) {

        // prepare ret
        $ret['status'] = 'fail';
        $ret['message'] = '';

        // delete request entry
        try {
            
            $stmt = $this->dbh->prepare('
                DELETE FROM wants_privilege
                WHERE   uid = :uid
                AND     privilege_level = :privilege_level
            ');

            $stmt->bindParam(':uid', $uid);
            $stmt->bindParam(':privilege_level', $privilege_level);

            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    $ret['status'] = 'ok';
                } else {
                    $ret['message'] = "No rows were affected..";
                }
            } else {
                $ret['message'] = "Statement didn't execute right : " . $stmt->errorCode();
            }

        } catch (PDOException $ex) {
            $ret['message'] = $ex->getMessage();
        }
        
        return $ret;
        
    }

}
