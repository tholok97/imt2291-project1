<?php

use PHPUnit\Framework\TestCase;

require_once dirname(__FILE__) . '/../src/classes/DB.php';
require_once dirname(__FILE__) . '/../src/classes/User.php';
require_once dirname(__FILE__) . '/../src/classes/PlaylistManager.php';

class PlaylistManagerTest extends TestCase {

    private $playlistManager = null;
    private $dbh = null;

    private $thumbnail = null;

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
        $this->playlistManager = new PlaylistManager(DB::getDBConnection());

        // setup thumbnail
        if (!$this->thumbnail = file_get_contents(Config::TEST_THUMBNAIL_PATH)) {
            $this->fail("Couldn't load test thumbnail");
        }



        // set up test properties


        
    }

    protected function teardown() {
        if (!$this->dbh->query('DELETE FROM in_playlist')) {
            $this->fail("Couldn't clean up database..");
        }
        if (!$this->dbh->query('DELETE FROM maintains')) {
            $this->fail("Couldn't clean up database..");
        }
        if (!$this->dbh->query('DELETE FROM playlist')) {
            $this->fail("Couldn't clean up database..");
        }
        if (!$this->dbh->query('DELETE FROM video')) {
            $this->fail("Couldn't clean up database..");
        }
        if (!$this->dbh->query('DELETE FROM user')) {
            $this->fail("Couldn't clean up database..");
        }
    }


    public function testAddPlaylist() {

        // testdata
        $testtitle = "Sometitle";
        $testdescription = "somedescription";

        // try and add playlist
        $res = $this->playlistManager->addPlaylist($testtitle, $testdescription, $this->thumbnail);

        // assert that we got pid back
        $this->assertNotNull(
            $res['pid'],
            "pid should be int"
        );

        // try and fetch inserted stuff from db
        $stmt = $this->dbh->prepare('SELECT * FROM playlist WHERE pid=:pid');
        $stmt->bindParam(':pid', $res['pid']);
        $stmt->execute();

        // if no result -> fail
        if ($stmt->rowCount() == 0) {
            $this->fail("Nothing inserted after addplaylist");
        }

        // assert that correct stuff was inserted
        
        $row = $stmt->fetchAll()[0];
        
        $this->assertEquals(
            $testtitle,
            $row['title'],
            "wrong title inserted"
        );

        $this->assertEquals(
            $testdescription,
            $row['description'],
            "wrong description inserted"
        );

    }

    /**
     * @depends testAddPlaylist
     */
    public function testGetNextPosition() {

        // add test playlist
        $testtitle = "Sometitle";
        $testdescription = "somedescription";
        $res_addplaylist = $this->playlistManager->addPlaylist($testtitle, $testdescription, $this->thumbnail);
        $testpid = $res_addplaylist['pid'];

        // add testuser
        $this->dbh->query("
INSERT INTO user (username, firstname, lastname, password_hash, privilege_level)
VALUES ('','','','',0)
        ");
        $testuid = $this->dbh->lastInsertId();

        // add testvideo1
        $this->dbh->query("
INSERT INTO video (title, description, thumbnail, uid, topic, course_code, timestamp, view_count, mime, size)
VALUES ('','','',$testuid,'','','',0,'','')
        ");
        $testvid1 = $this->dbh->lastInsertId();




        // assert that succeeds for valid pid
        $res = $this->playlistManager->getNextPosition($testpid);
        $this->assertEquals(
            'ok',
            $res['status'],
            "should succeed for valid pid"
        );




        // get next position for playlist
        $res = $this->playlistManager->getNextPosition($testpid);

        // assert is 0 (ince none added
        $this->assertEquals(
            1,
            $res['position'],
            "initla position should be 1"
        );



        // insert one video

        $this->dbh->query("
INSERT INTO in_playlist (vid, pid, position)
VALUES ($testvid1, $testpid, 1)
        ");



        // get next position for playlist
        $res = $this->playlistManager->getNextPosition($testpid);

        // assert is 2 (since there is one from before)
        $this->assertEquals(
            2,
            $res['position'],
            "second video should get position 2"
        );



    }

    /**
     * @depends testAddPlaylist
     * @depends testGetNextPosition
     */
    public function testAddVideoToPlaylist() {

        // add test playlist
        $testtitle = "Sometitle";
        $testdescription = "somedescription";
        $res_addplaylist = $this->playlistManager->addPlaylist($testtitle, $testdescription, $this->thumbnail);
        $testpid = $res_addplaylist['pid'];

        // add testuser
        $this->dbh->query("
INSERT INTO user (username, firstname, lastname, password_hash, privilege_level)
VALUES ('','','','',0)
        ");
        $testuid = $this->dbh->lastInsertId();

        // add testvideo
        $this->dbh->query("
INSERT INTO video (title, description, thumbnail, uid, topic, course_code, timestamp, view_count, mime, size)
VALUES ('','','',$testuid,'','','',0,'','')
        ");
        $testvid = $this->dbh->lastInsertId();




        // add video to playlist
        $res = $this->playlistManager->addVideoToPlaylist($testvid, $testpid);


        // assert that adding it was ok
        $this->assertEquals(
            'ok',
            $res['status'],
            "Adding video should be okay"
        );

        // assert that was actually added

        $stmt = $this->dbh->prepare("
SELECT * 
FROM in_playlist
WHERE vid=:vid AND pid=:pid
        ");

        $stmt->bindParam(':vid', $testvid);
        $stmt->bindParam(':pid', $testpid);

        $stmt->execute();

        if ($stmt->rowCount() == 0) {
            $this->fail("Wasn't inserted into db");
        }




        // assert that adding invalid video fails
        $ret = $this->playlistManager->addVideoToPlaylist(-1, $testpid);
        $this->assertEquals(
            'fail',
            $ret['status'],
            "Adding invalid video should fail"
        );

        // assert that adding to invalid playlist fails
        $ret = $this->playlistManager->addVideoToPlaylist($testvid, -1);
        $this->assertEquals(
            'fail',
            $ret['status'],
            "Adding to invalid playlist should fail"
        );

    }

    /**
     * @depends testAddPlaylist
     */
    public function testAddMaintainerToPlaylist() {

        // add test playlist
        $testtitle = "Sometitle";
        $testdescription = "somedescription";
        $res_addplaylist = $this->playlistManager->addPlaylist($testtitle, $testdescription, $this->thumbnail);
        $testpid = $res_addplaylist['pid'];

        // add testuser
        $this->dbh->query("
INSERT INTO user (username, firstname, lastname, password_hash, privilege_level)
VALUES ('','','','',0)
        ");
        $testuid = $this->dbh->lastInsertId();



        // add maintainer to playlist
        $res = $this->playlistManager->addMaintainerToPlaylist($testuid, $testpid);


        // assert that adding it was ok
        $this->assertEquals(
            'ok',
            $res['status'],
            "Adding maintainer should be ok"
        );

        // assert that was actually added

        $stmt = $this->dbh->prepare("
SELECT * 
FROM maintains
WHERE uid=:uid AND pid=:pid
        ");

        $stmt->bindParam(':uid', $testuid);
        $stmt->bindParam(':pid', $testpid);

        $stmt->execute();

        if ($stmt->rowCount() == 0) {
            $this->fail("Wasn't inserted into db");
        }


        // assert that adding invalid video fails
        $ret = $this->playlistManager->addMaintainerToPlaylist(-1, $testpid);
        $this->assertEquals(
            'fail',
            $ret['status'],
            "Adding invalid user should fail"
        );

        // assert that adding to invalid playlist fails
        $ret = $this->playlistManager->addMaintainerToPlaylist($testuid, -1);
        $this->assertEquals(
            'fail',
            $ret['status'],
            "Adding to invalid playlist should fail"
        );
    }

    /**
     * @depends testAddPlaylist
     * @depends testAddVideoToPlaylist
     */
    public function testRemoveVideoFromPlaylist() {


        // add test playlist
        $testtitle = "Sometitle";
        $testdescription = "somedescription";
        $res_addplaylist = $this->playlistManager->addPlaylist($testtitle, $testdescription, $this->thumbnail);
        $testpid = $res_addplaylist['pid'];

        // add testuser
        $this->dbh->query("
INSERT INTO user (username, firstname, lastname, password_hash, privilege_level)
VALUES ('','','','',0)
        ");
        $testuid = $this->dbh->lastInsertId();

        // add testvideo
        $this->dbh->query("
INSERT INTO video (title, description, thumbnail, uid, topic, course_code, timestamp, view_count, mime, size)
VALUES ('','','',$testuid,'','','',0,'','')
        ");
        $testvid = $this->dbh->lastInsertId();


        // add video to playlist
        $this->playlistManager->addVideoToPlaylist($testvid, $testpid);



        // remove from playlist
        $res = $this->playlistManager->removeVideoFromPlaylist($testvid, $testpid);
        $this->assertEquals(
            'ok',
            $res['status'],
            "Removing video from playlist should be fine"
        );

        // test that was actually removed

        $stmt = $this->dbh->prepare("
SELECT * 
FROM in_playlist
WHERE vid=:vid AND pid=:pid
        ");

        $stmt->bindParam(':vid', $testvid);
        $stmt->bindParam(':pid', $testpid);

        $stmt->execute();

        if ($stmt->rowCount() != 0) {
            $this->fail("Video wasn't removed");
        }

    }

    /**
     * @depends testAddPlaylist
     * @depends testAddMaintainerToPlaylist
     */
    public function testRemoveMaintainerFromPlaylist() {

        // add test playlist
        $testtitle = "Sometitle";
        $testdescription = "somedescription";
        $res_addplaylist = $this->playlistManager->addPlaylist($testtitle, $testdescription, $this->thumbnail);
        $testpid = $res_addplaylist['pid'];

        // add testuser
        $this->dbh->query("
INSERT INTO user (username, firstname, lastname, password_hash, privilege_level)
VALUES ('','','','',0)
        ");
        $testuid = $this->dbh->lastInsertId();


        // add video to playlist
        $this->playlistManager->addMaintainerToPlaylist($testuid, $testpid);



        // remove user from maintainers
        $res = $this->playlistManager->removeMaintainerFromPlaylist($testuid, $testpid);
        $this->assertEquals(
            'ok',
            $res['status'],
            "Removing maintainer from playlist should be fine"
        );

        // test that was actually removed

        $stmt = $this->dbh->prepare("
SELECT * 
FROM maintains
WHERE uid=:uid AND pid=:pid
        ");

        $stmt->bindParam(':uid', $testuid);
        $stmt->bindParam(':pid', $testpid);

        $stmt->execute();

        if ($stmt->rowCount() != 0) {
            $this->fail("Maintainer wasn't removed wasn't removed");
        }
    }

    /**
     * @depends testAddPlaylist
     * @depends testAddVideoToPlaylist
     * @depends testAddMaintainerToPlaylist
     */
    public function testRemovePlaylist() {

        // add test playlist
        $testtitle = "Sometitle";
        $testdescription = "somedescription";
        $res_addplaylist = $this->playlistManager->addPlaylist($testtitle, $testdescription, $this->thumbnail);
        $testpid = $res_addplaylist['pid'];

        // add testuser
        $this->dbh->query("
INSERT INTO user (username, firstname, lastname, password_hash, privilege_level)
VALUES ('','','','',0)
        ");
        $testuid = $this->dbh->lastInsertId();

        // add testvideo
        $this->dbh->query("
INSERT INTO video (title, description, thumbnail, uid, topic, course_code, timestamp, view_count, mime, size)
VALUES ('','','',$testuid,'','','',0,'','')
        ");
        $testvid = $this->dbh->lastInsertId();


        // add user as maintainer of playlist
        $this->playlistManager->addMaintainerToPlaylist($testuid, $testpid);

        // add video to playlist
        $this->playlistManager->addVideoToPlaylist($testvid, $testpid);




        // remove playlist
        $res = $this->playlistManager->removePlaylist($testpid);


        // assert fine
        $this->assertEquals(
            'ok',
            $res['status'],
            "Deleting playlist should be fine"
        );


        // assert that was actually removed

        $stmt = $this->dbh->prepare("
SELECT * 
FROM playlist
WHERE pid=:pid
        ");

        $stmt->bindParam(':pid', $testpid);

        $stmt->execute();

        if ($stmt->rowCount() != 0) {
            $this->fail("Playlist wasn't removed properly");
        }




        // assert that removing invalid playlist fails
        $res = $this->playlistManager->removePlaylist(-1);
        $this->assertEquals(
            'fail',
            $res['status'],
            "Deleting invalid playlist should fail"
        );
    }

    /*
     * @depends testAddPlaylist
     */
    public function testUpdatePlaylist() {

        // testdata
        $testtitle = "Sometitle";
        $testdescription = "somedescription";

        // add playlist
        $res_add = $this->playlistManager->addPlaylist($testtitle, $testdescription, $this->thumbnail);


        // change some stuff
        $testtitle = "newtitle";
        $testdescription = "newdescription";


        // assert not okay to update invalid pid
        $res = $this->playlistManager->updatePlaylist(-1, $testtitle, $testdescription, $this->thumbnail);
        $this->assertEquals(
            'fail',
            $res['status'],
            "Updating invalid pid should fail"
        );



        // try and update
        $res = $this->playlistManager->updatePlaylist($res_add['pid'], $testtitle, $testdescription, $this->thumbnail);


        // assert ok
        $this->assertEquals(
            'ok',
            $res['status'],
            "Updating should be okay"
        );


        // try and fetch inserted stuff from db
        $stmt = $this->dbh->prepare('SELECT * FROM playlist WHERE pid=:pid');
        $stmt->bindParam(':pid', $res_add['pid']);
        $stmt->execute();

        // assert that correct stuff is in db
        
        $row = $stmt->fetchAll()[0];
        
        $this->assertEquals(
            $testtitle,
            $row['title'],
            "wrong title after update"
        );

        $this->assertEquals(
            $testdescription,
            $row['description'],
            "wrong description after update"
        );

    }

    /**
     * @depends testAddPlaylist
     * @depends testAddMaintainerToPlaylist
     */
    public function testGetMaintainersOfPlaylist() {
        
        // add test playlist
        $testtitle = "Sometitle";
        $testdescription = "somedescription";
        $res_addplaylist = $this->playlistManager->addPlaylist($testtitle, $testdescription, $this->thumbnail);
        $testpid = $res_addplaylist['pid'];


        $testusername = "dory";

        // add testuser
        $this->dbh->query("
INSERT INTO user (username, firstname, lastname, password_hash, privilege_level)
VALUES ('$testusername','','','',0)
        ");
        $testuid = $this->dbh->lastInsertId();


        // add maintainer to playlist
        $res = $this->playlistManager->addMaintainerToPlaylist($testuid, $testpid);




        // get all maintainers
        $res = $this->playlistManager->getMaintainersOfPlaylist($testpid);

        // assert ok
        $this->assertEquals(
            'ok',
            $res['status'],
            "Getting maintainers of valid playlist shouldn't fail"
        );

        // assert stuff was actually returned
        $this->assertEquals(
            1,
            count($res['users']),
            "Number of users returned should be 1"
        );



        // assert correct one returned
        $this->assertEquals(
            $testusername,
            $res['users'][0]->username,
            "User returned wasn't the correct one"
        );

    }

    /**
     * @depends testAddPlaylist
     * @depends testAddVideoToPlaylist
     */
    public function testGetVideosFromPlaylist() {

        // add test playlist
        $testtitle = "Sometitle";
        $testdescription = "somedescription";
        $res_addplaylist = $this->playlistManager->addPlaylist($testtitle, $testdescription, $this->thumbnail);
        $testpid = $res_addplaylist['pid'];

        // add testuser
        $this->dbh->query("
INSERT INTO user (username, firstname, lastname, password_hash, privilege_level)
VALUES ('','','','',0)
        ");
        $testuid = $this->dbh->lastInsertId();


        $testtopic = "horses";

        // add testvideo
        $this->dbh->query("
INSERT INTO video (title, description, thumbnail, uid, topic, course_code, timestamp, view_count, mime, size)
VALUES ('','','',$testuid,'$testtopic','','',0,'','')
        ");
        $testvid = $this->dbh->lastInsertId();


        // add video to playlist
        $res_addvideo = $this->playlistManager->addVideoToPlaylist($testvid, $testpid);




        // get videos from playlist
        $res = $this->playlistManager->getVideosFromPlaylist($testpid);



        // assert success
        $this->assertEquals(
            'ok',
            $res['status'],
            "Getting all videos should be successful"
        );

        // assert stuff actually returned
        $this->assertEquals(
            1,
            count($res['videos']),
            "Number of videos returned should be 1"
        );


        // assert correct stuff returned
        $this->assertEquals(
            $testtopic,
            $res['videos'][0]->topic,
            "Incorrect video returned"
        );

    }

    public function testGetPlaylist() {

        // add test playlist
        $testtitle = "Sometitle";
        $testdescription = "somedescription";
        $res_addplaylist = $this->playlistManager->addPlaylist($testtitle, $testdescription, $this->thumbnail);
        $testpid = $res_addplaylist['pid'];


        $testusername = "tommyboi";

        // add testuser
        $this->dbh->query("
INSERT INTO user (username, firstname, lastname, password_hash, privilege_level)
VALUES ('$testusername','','','',0)
        ");
        $testuid = $this->dbh->lastInsertId();


        $testtopic = "horses";

        // add testvideo
        $this->dbh->query("
INSERT INTO video (title, description, thumbnail, uid, topic, course_code, timestamp, view_count, mime, size)
VALUES ('','','',$testuid,'$testtopic','','',0,'','')
        ");
        $testvid = $this->dbh->lastInsertId();



        // add user as maintainer
        $res_addmaintainer = $this->playlistManager->addMaintainerToPlaylist($testuid, $testpid);

        // add video to playlist
        $res_addvideo = $this->playlistManager->addVideoToPlaylist($testvid, $testpid);



        // assert that getting invalid playlist fails
        $res = $this->playlistManager->getPlaylist(-1);
        $this->assertEquals(
            'fail',
            $res['status'],
            "Getting invalid playlist should fail"
        );


        // get playlist
        $res = $this->playlistManager->getPlaylist($testpid);

        // assert ok
        $this->assertEquals(
            'ok',
            $res['status'],
            "Getting valid playlist should be fine" 
        );


        // assert that returned correct number of videos
        $this->assertEquals(
            1,
            count($res['playlist']->videos),
            "Should return 1 video"
        );

        // assert that returned correct number of maintainers
        $this->assertEquals(
            1,
            count($res['playlist']->maintainers),
            "Should return 1 video"
        );


        // assert that correct video returned
        $this->assertEquals(
            $testuid,
            $res['playlist']->videos[0]->uid,
            "Correct user should be returned"
        );

        // assert that correct maintainer returned
        $this->assertEquals(
            $testusername,
            $res['playlist']->maintainers[0]->username,
            "Correct user should be returned"
        );


    }

}