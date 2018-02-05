<?php

use PHPUnit\Framework\TestCase;

require_once dirname(__FILE__) . '/../src/classes/DB.php';
require_once dirname(__FILE__) . '/../src/classes/UserManager.php';
require_once dirname(__FILE__) . '/../src/classes/User.php';

class UserManagerTest extends TestCase {

    private $userManager = null;
    private $dbh = null;

    protected function setup() {

        // setup database connection
        $this->dbh = DB::getDBConnection(
            Config::DB_TEST_DSN, 
            Config::DB_TEST_USER,
            Config::DB_TEST_PASSWORD
        );

        // assert that it could connect
        if ($this->dbh == null) {
            $this->fail("Couldn't make connection to database");
        }

        // setup usermanager
        $this->userManager = new UserManager($this->dbh);
    }

    protected function teardown() {
        if (!$this->dbh->query('DELETE FROM User')) {
            $this->fail("Couldn't clean up database..");
        }
    }

    public function testLogin() {

        // test user data
        $username = 'testuser';
        $password = 'testpassword';

        // generate hash
        $hash = password_hash($password, PASSWORD_DEFAULT);

        // insert testuser into database
        $stmt = $this->dbh->prepare('
            INSERT INTO User (username, firstname, lastname, password_hash, privilege_level)
            VALUES (:username, "firstname", "lastname", :hash, 2)
        ');

        $stmt->bindParam(':username', $username);
        $stmt->bindValue(':hash', $hash);

        if (!$stmt->execute()) {
            $this->fail("Couldn't insert test user");
        }

        if (!password_verify($password, $hash)) {
            $this->fail("Password isn't right..");
        }


        // asssert that logging in with valid credentials is successful
        $res = $this->userManager->login($username, $password);
        $this->assertEquals(
            'ok',
            $res['status'],
            'login not ok for valid user'
        );

        // assert that returned an uid
        if (!isset($res['uid'])) {
            $this->fail("Didn't get a uid from login function");
        }

        // asssert that logging in with invalid password is unsuccessful
        $res = $this->userManager->login($username, "invalid");
        $this->assertEquals(
            'fail',
            $res['status'],
            'login not ok for valid user'
        );

        // asssert that logging in with invalid username is unsuccessful
        $res = $this->userManager->login("invalid", $password);
        $this->assertEquals(
            'fail',
            $res['status'],
            'login not ok for valid user'
        );

    }

    public function testAddUser() {

        // make test user
        $user = new User(
            'testuser', 
            'firstname', 
            'secondname', 
            2
        );

        $password = 'testpassword';

        // assert that adding new user goes well
        $ret = $this->userManager->addUser($user, $password);
        $this->assertEquals(
            'ok',
            $ret['status'],
            "Couldn't add valid user :" . $ret['message']
        );

        // assert that adding same user again doesn't work goes well
        $ret = $this->userManager->addUser($user, $password);
        $this->assertEquals(
            'fail',
            $ret['status'],
            "shouldn't be able to add user twice (duplicate username)"
        );
    }

    public function testIsValidUser() {

        // test user data
        $username = 'testuser';
        $password = 'testpassword';

        // generate hash
        $hash = password_hash($password, PASSWORD_DEFAULT);

        // insert testuser into database
        $stmt = $this->dbh->prepare('
            INSERT INTO User (username, firstname, lastname, password_hash, privilege_level)
            VALUES (:username, "firstname", "lastname", :hash, 2)
        ');

        $stmt->bindParam(':username', $username);
        $stmt->bindValue(':hash', $hash);

        if (!$stmt->execute()) {
            $this->fail("Couldn't insert test user");
        }

        if (!password_verify($password, $hash)) {
            $this->fail("Password isn't right..");
        }


        // store uid
        $uid = $this->dbh->lastInsertId();


        // test that isValidUser gives true for our test user
        $res = $this->userManager->isValidUser($uid);
        $this->assertEquals(
            true,
            $res['valid'],
            "id of inserted test user should be valid"
        );
        
        
        // test that isValidUser gives false for non-existant user
        $res = $this->userManager->isValidUser(923923);
        $this->assertEquals(
            false,
            $res['valid'],
            "isValidUser should return false for non-valid uid"
        );
        
    }


}
