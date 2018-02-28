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
            'testuser@e', 
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

        $ret = $this->userManager->login("testuser@e", $password);

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
            'testuser@e', 
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

        $ret = $this->userManager->login("testuser@e", $password);

        $this->assertEquals(
            'ok',
            $ret['status'],
            'login not ok for valid user'
        );

        $uid = $ret['uid'];

        // make test user 2 (is needed to rate the same video twice)
        $user = new User(
            'testuser2@e', 
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

        $ret = $this->userManager->login("testuser2@e", $password);

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

        $secondRate = 5;

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

    public function testSearch() {
        // make test user (is needed to upload a video)
        $user = new User(
            'testuser@e', 
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

        $ret = $this->userManager->login("testuser@e", $password);

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

        $ret = uploadVideoTestdata("Test video 2", "This is the second test video", $uid, "Testvideos", "IMT2263");
        
        $this->assertEquals(
            'ok',
            $ret['status'],
            'Uploading video not ok: ' . $ret['errorMessage']
        );

        $vid = $ret['vid'];
        
        // Test search
        $searchPlaces['title'] = true;
        $ret = $this->videoManager->search("Test video 2", $searchPlaces); // Will search on common places.

        $this->assertEquals(
            'ok',
            $ret['status'],
            'Searching not ok on first search: ' . $ret['errorMessage']
        );

        //print_r($ret);

        $this->assertEquals(
            '1',
            count($ret['result']),
            'Search result not 1 on first search but: ' . count($ret['result'])
        );

        //Search only in firstname and lastname. 
        $searchPlaces['title'] = true;
        $searchPlaces["firstname"] = true;
        $searchPlaces["lastname"] = true;

        $ret = $this->videoManager->search("name", $searchPlaces);

        $this->assertEquals(
            'ok',
            $ret['status'],
            'Searching not ok on second search: ' . $ret['errorMessage']
        );

        //print_r($ret);

        $this->assertEquals(
            '2',
            count($ret['result']),
            'Search result not 2 on second search but: ' . count($ret['result'])
        );

    }

    function testUpdateAndGet() {
        // make test user (is needed to upload a video)
        $user = new User(
            'testuser@e', 
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

        $ret = $this->userManager->login("testuser@e", $password);

        $this->assertEquals(
            'ok',
            $ret['status'],
            'login not ok for valid user'
        );

        $uid = $ret['uid'];
        // Make a testvideo

        $title = "Test video";
        $description = "This is a test video";
        $topic = "Testvideos";
        $course_code = "IMT2263";

        $ret = uploadVideoTestdata($title, $description, $uid, $topic, $course_code);
        
        $this->assertEquals(
            'ok',
            $ret['status'],
            'Uploading video not ok: ' . $ret['errorMessage']
        );

        $vid = $ret['vid'];

        $ret = $this->videoManager->get($vid);

        //Check that getting video is ok and title is correct.
        $this->assertEquals(
            'ok',
            $ret['status'],
            'Getting video not ok: ' . $ret['errorMessage']
        );

        $this->assertEquals(
            $title,
            $ret['video']->title,
            'Getting title not the same as the title sent in under upload: ' . $ret['errorMessage']
        );

        //Checking getAllUserVideos also here:
        $ret = $this->videoManager->getAllUserVideos($uid);
        
        // If status ok
        $this->assertEquals(
            'ok',
            $ret['status'],
            'Getting all user videos not ok: ' . $ret['errorMessage']
        );

        // If at least got 1 video returned and that is the one we uploaded.
        $this->assertEquals(
            $title,
            $ret['videos'][0]['video']->title,
            'Getting title not the same as the title sent in under upload when getting all user videos: ' . $ret['errorMessage']
        );
        $newTitle = "Testvideo updated";

        $ret = $this->videoManager->update($vid, $uid,$newTitle, $description, $topic, $course_code);

        $this->assertEquals(
            'ok',
            $ret['status'],
            'Updating video not ok: ' . $ret['errorMessage']
        );

        $ret = $this->videoManager->get($vid);

        //Check that getting video is ok and title is correct.
        $this->assertEquals(
            'ok',
            $ret['status'],
            'Getting video not ok after updating: ' . $ret['errorMessage']
        );

        $this->assertEquals(
            $newTitle,
            $ret['video']->title,
            'Getting title not the same as the title sent in under update: ' . $ret['errorMessage']
        );
    }
}
