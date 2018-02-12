<?php

use PHPUnit\Framework\TestCase;

require_once dirname(__FILE__) . '/../src/classes/DB.php';
require_once dirname(__FILE__) . '/../src/classes/UserManager.php';         //Needed because we need a user when uploading videos.
require_once dirname(__FILE__) . '/../src/classes/User.php';
require_once dirname(__FILE__) . '/../src/classes/VideoManager.php';
require_once dirname(__FILE__) . '/../src/classes/Video.php';
require_once dirname(__FILE__) . '/testFunctions.php';

class VideoManagerTest extends TestCase {

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
        if (!$this->dbh->query('DELETE FROM rated')) {
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

    public function testRate() {
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

        // make test user 2 (is needed to rate the same video twice)
        $user = new User(
            'testuser2', 
            'firstname2', 
            'secondname2', 
            0
        );

        $password = 'testpassword2';

        // assert that adding new user goes well
        $ret = $this->userManager->addUser($user, $password);
        $this->assertEquals(
            'ok',
            $ret['status'],
            "Couldn't add valid user :" . $ret['message']
        );

        $ret = $this->userManager->login("testuser2", $password);

        $this->assertEquals(
            'ok',
            $ret['status'],
            'login not ok for valid user (user 2)'
        );

        $uid2 = $ret['uid'];
        
        // Make a testvideo
        $ret = uploadVideoTestdata("Test video", "This is a test video", $uid, "Testvideos", "IMT2263");
        
        $this->assertEquals(
            'ok',
            $ret['status'],
            'Uploading video not ok: ' . $ret['errorMessage']
        );

        $vid = $ret['vid'];

        $firstRate = 4;

        // Insert a rating
        $ret = $this->videoManager->addRating($firstRate, $uid, $vid);

        $this->assertEquals(
            'ok',
            $ret['status'],
            'Rating not ok on first rate: ' . $ret['errorMessage']
        );

        // Check the rating the user did.
        $ret = $this->videoManager->getUserRating($uid, $vid);

        $this->assertEquals(
            'ok',
            $ret['status'],
            'Getting user rating not ok on first rate: ' . $ret['errorMessage']
        );

        $this->assertEquals(
            $firstRate,
            $ret['rating'],
            'Rating gotten from userRating not the same (' . $firstRate . ') on first rating: ' . $ret['rating']
        );

        $ret = $this->videoManager->getRating($vid);

        $this->assertEquals(
            'ok',
            $ret['status'],
            'Getting all video ratings not ok on first rate: ' . $ret['errorMessage']
        );

        $this->assertEquals(
            $firstRate,
            $ret['rating'],
            'Rating gotten from all video ratings not the same (' . $firstRate . ') on first rating: ' . $ret['rating']
        );

        $secondRate = 16;

        // Check if errorMessage if new addRating with same user on same video.
        $ret = $this->videoManager->addRating($secondRate, $uid, $vid);

        $this->assertEquals(
            'fail',
            $ret['status'],
            'Rating do not fail on second rate when using same user.'
        );

        $ret = $this->videoManager->addRating($secondRate, $uid2, $vid);

        $this->assertEquals(
            'ok',
            $ret['status'],
            'Rating not ok on second rate when using another user: ' . $ret['errorMessage']
        );

        $ret = $this->videoManager->getUserRating($uid2, $vid);

        $this->assertEquals(
            'ok',
            $ret['status'],
            'Getting user rating not ok on second rate when using another user: ' . $ret['errorMessage']
        );

        $this->assertEquals(
            $secondRate,
            $ret['rating'],
            'Rating gotten from userRating not the same (' . $secondRate . ') on second rating when using another user: ' . $ret['rating']
        );

        $ret = $this->videoManager->getRating($vid);

        $this->assertEquals(
            'ok',
            $ret['status'],
            'Getting all video ratings not ok on second rate: ' . $ret['errorMessage']
        );

        $this->assertEquals(
            ($firstRate + $secondRate)/2,
            $ret['rating'],
            'Rating not the correct answer (' . ($firstRate + $secondRate)/2 . ') on second rating, but: ' . $ret['rating']
        );

    }
}
