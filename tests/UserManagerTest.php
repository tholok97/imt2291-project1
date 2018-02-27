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
        if (!$this->dbh->query('DELETE FROM wants_privilege')) {
            $this->fail("Couldn't clean up database..");
        }
        if (!$this->dbh->query('DELETE FROM user')) {
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
            INSERT INTO user (username, firstname, lastname, password_hash, privilege_level)
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
            'testuser@thing.com', 
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

        // assert that adding same user again doesn't go well
        $ret = $this->userManager->addUser($user, $password);
        $this->assertEquals(
            'fail',
            $ret['status'],
            "shouldn't be able to add user twice (duplicate username)"
        );


        // assert that adding user with non-email username fails
        $user->username = 'notanemail';
        $ret = $this->userManager->addUser($user, $password);
        $this->assertEquals(
            'fail',
            $ret['status'],
            "Shouldnt' be able to add user with non-email username"
        );


        // add test for user actually inserted in db???
    }

    public function testIsValidUser() {

        // test user data
        $username = 'testuser';
        $password = 'testpassword';

        // generate hash
        $hash = password_hash($password, PASSWORD_DEFAULT);

        // insert testuser into database
        $stmt = $this->dbh->prepare('
            INSERT INTO user (username, firstname, lastname, password_hash, privilege_level)
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

    public function testGetUid() {

        // test user data
        $username = 'testuser';
        $password = 'testpassword';

        // generate hash
        $hash = password_hash($password, PASSWORD_DEFAULT);

        // insert testuser into database
        $stmt = $this->dbh->prepare('
            INSERT INTO user (username, firstname, lastname, password_hash, privilege_level)
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





        // assert that getting uid of inserted user is ok
        $res = $this->userManager->getUID($username);
        $this->assertEquals(
            'ok',
            $res['status'],
            "Getting uid of inserted user should be ok : " . $res['message']
        );

        // assert uid returned is the correct one
        $this->assertEquals(
            $uid,
            $res['uid'],
            "uid returned should be the same as the one we got when inserted with : " . 
                    $res['message']
        );

        // assert that getting uid of non-existant user is fail
        $res = $this->userManager->getUID("justrandomusername");
        $this->assertEquals(
            'fail',
            $res['status'],
            "getting uid of non-existant user should fail : " . $res['message']
        );
    }

    public function testSearch() {

        // array of test users
        $testusers[0]['username'] = 't';
        $testusers[1]['username'] = 'tom';
        $testusers[2]['username'] = 'tomas';
        $testusers[0]['firstname'] = 'lokken';
        $testusers[1]['firstname'] = 'lok';
        $testusers[2]['firstname'] = 'x';
        $testusers[0]['lastname'] = 'per';
        $testusers[1]['lastname'] = 'errr';
        $testusers[2]['lastname'] = 'pxx';

        $testusersUIDs = array();

        // insert users int db
        for ($i = 0; $i < count($testusers); $i++) {

            // insert testuser into database
            $stmt = $this->dbh->prepare('
                INSERT INTO user (username, firstname, lastname, password_hash, privilege_level)
                VALUES (:username, :firstname, :lastname, "hashhhh", 2)
            ');

            $stmt->bindParam(':username', $testusers[$i]['username']);
            $stmt->bindParam(':firstname', $testusers[$i]['firstname']);
            $stmt->bindParam(':lastname', $testusers[$i]['lastname']);

            if (!$stmt->execute()) {
                $this->fail("Couldn't insert test user");
            }

            // store uid
            array_push($testusersUIDs, $this->dbh->lastInsertId());
        }


        // assert that searh for returns all three of our users (username serach)
        $ret = $this->userManager->search('t', 'username');
        $this->assertEquals(
            3,
            count($ret['uids']),
            "search for t should give all three test users (gave ".count($ret['uids']).")"
        );

        // assert that the uids returned were the correct ones
        $this->assertEquals(
            [],
            array_diff($ret['uids'], $testusersUIDs),
            "Search should return uids of inserted users"
        );


        // assert that search for om returns two users
        $ret = $this->userManager->search('om', 'username');
        $this->assertEquals(
            2,
            count($ret['uids']),
            "search for om should give two test users (gave ".count($ret['uids']).")"
        );


        // assert that searching for firstname works
        $ret = $this->userManager->search('lok', 'firstname');
        $this->assertEquals(
            2,
            count($ret['uids']),
            "Search should only return 2"
        );

        // assert that searching for lastname works
        $ret = $this->userManager->search('er', 'lastname');
        $this->assertEquals(
            2,
            count($ret['uids']),
            "Search should only return 2"
        );


        // assert that giving invalid searchwhere fails
        $ret = $this->userManager->search('er', 'drop tables');
        $this->assertEquals(
            'fail',
            $ret['status'],
            "Invalid searchwhere should give fail"
        );
    }

    public function testSearchMultipleFields() {

        // array of test users
        $testusers[0]['username'] = 't';
        $testusers[1]['username'] = 'tom';
        $testusers[2]['username'] = 'tomas';
        $testusers[0]['firstname'] = 'lokken';
        $testusers[1]['firstname'] = 'lok';
        $testusers[2]['firstname'] = 'x';
        $testusers[0]['lastname'] = 'per';
        $testusers[1]['lastname'] = 'errr';
        $testusers[2]['lastname'] = 'pxx';

        $testusersUIDs = array();

        // insert users int db
        for ($i = 0; $i < count($testusers); $i++) {

            // insert testuser into database
            $stmt = $this->dbh->prepare('
                INSERT INTO user (username, firstname, lastname, password_hash, privilege_level)
                VALUES (:username, :firstname, :lastname, "hashhhh", 2)
            ');

            $stmt->bindParam(':username', $testusers[$i]['username']);
            $stmt->bindParam(':firstname', $testusers[$i]['firstname']);
            $stmt->bindParam(':lastname', $testusers[$i]['lastname']);

            if (!$stmt->execute()) {
                $this->fail("Couldn't insert test user");
            }

            // store uid
            array_push($testusersUIDs, $this->dbh->lastInsertId());
        }


        // assert that searching valid string in valid place is ok
        $ret = $this->userManager->searchMultipleFields('o', array('firstname', 'lastname'));
        $this->assertEquals(
            'ok',
            $ret['status'],
            "Searching for valid thing that is in one of given fields should ".
            "be okay (gave message ".$ret['message'].")"
        );


        // assert that seraching for a valid username in lastnames gives no results
        $ret = $this->userManager->searchMultipleFields('tomas', array('firstname', 'lastname'));
        $this->assertEquals(
            0,
            count($ret['uids']),
            "Searching string isn't in the given fields, should give 0 (is in the other though)"
        );

        // assert that searching with invalid field gives fail
        $ret = $this->userManager->searchMultipleFields('tomas', array('firstname', 'invalid'));
        $this->assertEquals(
            'fail',
            $ret['status'],
            "Search with non-sensical field should give fail"
        );

    }


    public function testWantsPrivilegeLevel() {
        
        // test user data
        $username = 'testuser';
        $password = 'testpassword';

        // generate hash
        $hash = password_hash($password, PASSWORD_DEFAULT);

        // insert testuser into database
        $stmt = $this->dbh->prepare('
            INSERT INTO user (username, firstname, lastname, password_hash, privilege_level)
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

        

        // assert that requesting privilege_level for valid user is successful
        $ret = $this->userManager->requestPrivilege($uid, 1);
        $this->assertEquals(
            'ok',
            $ret['status'],
            "Requesting privlege level for valid user should be fine"
        );

        // assert that requesting privilege_level for invalid user fails
        $ret = $this->userManager->requestPrivilege(-1, 1);
        $this->assertEquals(
            'fail',
            $ret['status'],
            "Requesting privlege level for invalid user should fail"
        );

        // assert that requesting invalid privilege level fails
        $ret = $this->userManager->requestPrivilege($uid, 3);
        $this->assertEquals(
            'fail',
            $ret['status'],
            "Requesting invalid privilege_level should fail"
        );

    }

    public function testGetUser() {

        // test user data
        $username = 'testuser';
        $password = 'testpassword';

        // generate hash
        $hash = password_hash($password, PASSWORD_DEFAULT);

        // insert testuser into database
        $stmt = $this->dbh->prepare('
            INSERT INTO user (username, firstname, lastname, password_hash, privilege_level)
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






        // assert that testuser with valid uid gives corret user

        $ret = $this->userManager->getUser($uid);
        $this->assertEquals(
            'ok',
            $ret['status'],
            "Getting uid of valid user should return ok : " . $ret['message']
        );

        $this->assertEquals(
            $username,
            $ret['user']->username,
            "User returned should have correct username"
        );


        // assert that getting invalid user gives fail
        $ret = $this->userManager->getUser(-1);
        $this->assertEquals(
            'fail',
            $ret['status'],
            "Getting invalid user should fail"
        );
    }

    /**
     * @depends testWantsPrivilegeLevel
     * @depends testAddUser
     */
    public function testGetWantsPrivilege() {

        $testuser = new User(
            'test1@koko',
            'test2',
            'test3',
            0
        );

        $testpassword = '123';

        // insert user
        $ret_adduser = $this->userManager->addUser($testuser, $testpassword);

        // request privilege 1
        $ret_request = $this->userManager->requestPrivilege($ret_adduser['uid'], 1);


        // assert that getting all requests returns this  testuser

        $ret = $this->userManager->getWantsPrivilege();
        $this->assertEquals(
            'ok',
            $ret['status'],
            "Getting all wants should be fine"
        );

        $this->assertEquals(
            $ret_adduser['uid'],
            $ret['wants'][0]['uid'],
            "uid of first wants should be uid of test user"
        );
    }

    /**
     * @depends testAddUser
     * @depends testGetUser
     */
    public function testUpdateUser() {
        
        $testuser = new User(
            'test1@koko',
            'test2',
            'test3',
            0
        );

        $testpassword = '123';

        // insert user
        $ret_adduser = $this->userManager->addUser($testuser, $testpassword);

        $testuser->uid = $ret_adduser['uid'];


        // assert that updating only privilege_level and firstname does just that

        $testuser->firstname = 'Thomas';
        $testuser->privilege_level = 2;


        $ret = $this->userManager->updateUser($testuser);
        $this->assertEquals(
            'ok',
            $ret['status'],
            "Updating should be OK! : " . $ret['message']
        );

        $gottenUser = $this->userManager->getUser($testuser->uid)['user'];

        $this->assertEquals(
            $testuser->firstname,
            $gottenUser->firstname,
            "Firstname of gotten user should be same as testuser (updated)"
        );


        // assert that updating non-existent user fails
        $testuser->uid = -1;
        $ret = $this->userManager->updateUser($testuser);
        $this->assertEquals(
            'fail',
            $ret['status'],
            "Updating on non-existent user should fail"
        );
    }

    /**
     * @depends testGetUser
     * @depends testUpdateUser
     * @depends testWantsPrivilegeLevel
     */
    public function testGrantPrivilege() {

        $testuser = new User(
            'test1@koko',
            'test2',
            'test3',
            0
        );

        $wants_privilege = 1;

        $testpassword = '123';

        // insert user
        $ret_adduser = $this->userManager->addUser($testuser, $testpassword);

        $testuser->uid = $ret_adduser['uid'];

        
        // register privilege request
        $ret_wants = $this->userManager->requestPrivilege($testuser->uid, $wants_privilege);



        // assert that granting the privilege is successful
        $ret = $this->userManager->grantPrivilege($testuser->uid, $wants_privilege);

        $this->assertEquals(
            'ok',
            $ret['status'],
            "Granting registered privilege should be fine : " . $ret['message']
        );

        // assert that  privilege was actually granted
        $ret_getuser = $this->userManager->getUser($testuser->uid);
        $this->assertEquals(
            $wants_privilege,
            $ret_getuser['user']->privilege_level,
            "User in db should have correct privilege_level : " . $ret['message']
        );

    }

    /**
     * @depends testWantsPrivilegeLevel
     * @depends testAddUser
     */
    public function testDeletePrivilegeRequest() {

        $testuser = new User(
            'test1@koko',
            'test2',
            'test3',
            0
        );

        $wants_privilege = 1;

        $testpassword = '123';

        // insert user
        $ret_adduser = $this->userManager->addUser($testuser, $testpassword);

        $testuser->uid = $ret_adduser['uid'];

        // register privilege request
        $ret_wants = $this->userManager->requestPrivilege($testuser->uid, $wants_privilege);



        // assert that deleting is successful
        $ret = $this->userManager->deletePrivilegeRequest($testuser->uid, $wants_privilege);
        $this->assertEquals(
            'ok',
            $ret['status'],
            "Deleting valid request shouldn't fail : " . $ret['message']
        );

        // assert that request was actually deleted
        $ret_getrequests = $this->userManager->getWantsPrivilege();
        $this->assertEquals(
            false,
            in_array(
                [
                    'uid' => $testuser->uid, 
                    '0' => $testuser->uid, 
                    'privilege_level' => $wants_privilege,
                    '1' => $wants_privilege
                ], 
                $ret_getrequests['wants']
            ),
            "User should no longer have a privilege request registered in the db"
        );

        // assert that deleting invalid user is unsuccesful
        $ret = $this->userManager->deletePrivilegeRequest(-1, $wants_privilege);
        $this->assertEquals(
            'fail',
            $ret['status'],
            "Deleting invalid user should fail"
        );
    }

}
