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

            $stmt = $this->dbh->prepare('SELECT * FROM User WHERE username=:username');

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
}
