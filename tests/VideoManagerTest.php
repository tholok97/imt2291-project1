<?php

use PHPUnit\Framework\TestCase;

require_once dirname(__FILE__) . '/../src/classes/DB.php';
require_once dirname(__FILE__) . '/../src/classes/UserManager.php';         //Needed because we need a user when uploading videos.
require_once dirname(__FILE__) . '/../src/classes/User.php';
require_once dirname(__FILE__) . '/../src/classes/VideoManager.php';
require_once dirname(__FILE__) . '/../src/classes/Video.php';
require_once dirname(__FILE__) . '/testFunctions.php';

class UserManagerTest extends TestCase {

    private $userManager = null;
    private $videoManager = null;
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

        // setup videomanager and usermanager
        $this->videoManager = new VideoManager($this->dbh);
        $this->userManager = new UserManager($this->dbh);
    }

    protected function teardown() {
        if (!$this->dbh->query('DELETE FROM comment')) {
            $this->fail("Couldn't clean up database..");
        }
        if (!$this->dbh->query('DELETE FROM video')) {
            $this->fail("Couldn't clean up database..");
        }
        if (!$this->dbh->query('DELETE FROM user')) {
            $this->fail("Couldn't clean up database..");
        }
    }

    public function testComment() {
        // make test user (is needed to upload a video)
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

        $ret = $this->userManager->login("testuser", $password);

        $this->assertEquals(
            'ok',
            $ret['status'],
            'login not ok for valid user'
        );

        $uid = $ret['uid'];
        // Make a testvideo
        $ret = uploadVideoTestdata("Test video", "This is a test video", $uid, "Testvideos", "IMT2263");
        
        $this->assertEquals(
            'ok',
            $ret['status'],
            'Uploading video not ok: ' . $ret['errorMessage']
        );

        $vid = $ret['vid'];
        
        // Insert two comments
        $ret = $this->videoManager->comment("I comment my own video", $uid, $vid);

        $this->assertEquals(
            'ok',
            $ret['status'],
            'Commenting not ok on first comment: ' . $ret['errorMessage']
        );

        $ret = $this->videoManager->comment("I comment my own video again", $uid, $vid);

        $this->assertEquals(
            'ok',
            $ret['status'],
            'Commenting not ok on second comment: ' . $ret['errorMessage']
        );

        $this->videoManager->getComments($vid);

        $this->assertEquals(
            'ok',
            $ret['status'],
            'Getting comments not ok: ' . $ret['errorMessage']
        );

    }
}
