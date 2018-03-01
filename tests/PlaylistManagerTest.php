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
        $this->thumbnail['tmp_name'] = Config::TEST_THUMBNAIL_PATH;



        // set up test properties


        
    }

    protected function teardown() {
        if (!$this->dbh->query('DELETE FROM subscribes_to')) {
            $this->fail("Couldn't clean up database.. subscribes_to");
        }
        if (!$this->dbh->query('DELETE FROM in_playlist')) {
            $this->fail("Couldn't clean up database.. in_playlist");
        }
        if (!$this->dbh->query('DELETE FROM maintains')) {
            $this->fail("Couldn't clean up database.. maintains");
        }
        if (!$this->dbh->query('DELETE FROM playlist')) {
            $this->fail("Couldn't clean up database.. playlist");
        }
        if (!$this->dbh->query('DELETE FROM video')) {
            $this->fail("Couldn't clean up database.. video");
        }
        if (!$this->dbh->query('DELETE FROM user')) {
            $this->fail("Couldn't clean up database.. user");
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
        $res = $this->playlistManager->updatePlaylist(-1, $testtitle, $testdescription);
        $this->assertEquals(
            'fail',
            $res['status'],
            "Updating invalid pid should fail"
        );



        // try and update
        $res = $this->playlistManager->updatePlaylist($res_add['pid'], $testtitle, $testdescription);


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

    /*
     * @depends testAddVideoToPlaylist
     */
    public function testSwapPositionsInPlaylist() {

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

        // add testvideo 1
        $this->dbh->query("
INSERT INTO video (title, description, thumbnail, uid, topic, course_code, timestamp, view_count, mime, size)
VALUES ('','','',$testuid,'$testtopic','','',0,'','')
        ");
        $testvid1 = $this->dbh->lastInsertId();

        // add testvideo 2
        $this->dbh->query("
INSERT INTO video (title, description, thumbnail, uid, topic, course_code, timestamp, view_count, mime, size)
VALUES ('','','',$testuid,'$testtopic','','',0,'','')
        ");
        $testvid2 = $this->dbh->lastInsertId();

        // add videos to playlist
        $this->playlistManager->addVideoToPlaylist($testvid1, $testpid);
        $this->playlistManager->addVideoToPlaylist($testvid2, $testpid);






        // assert that swapping invalid videos fails
        $ret = $this->playlistManager->swapPositionsInPlaylist(-1, -1, $testpid);
        $this->assertEquals(
            'fail',
            $ret['status'],
            "Swapping two invalid videos should fail"
        );

        // assert that swapping in invalid playlist fails
        $ret = $this->playlistManager->swapPositionsInPlaylist(
            $testvid1, 
            $testvid2, 
            -1
        );
        $this->assertEquals(
            'fail',
            $ret['status'],
            "Swapping in invalid playlist should fail"
        );


        // assert that swapping valid stuff is ok
        $ret = $this->playlistManager->swapPositionsInPlaylist(
            1,
            2,
            $testpid
        );
        $this->assertEquals(
            'ok',
            $ret['status'],
            "Swapping valid stuff should be ok : " . $ret['message']
        );


        // assert that swapping happened successfully
        $stmt = $this->dbh->query("
SELECT position 
FROM in_playlist
WHERE vid=$testvid1 AND pid=$testpid
        ");

        $this->assertEquals(
            2,
            $stmt->fetchAll()[0]['position'],
            "Position of vid1 after swap should be 2"
        );

        $stmt = $this->dbh->query("
SELECT position 
FROM in_playlist
WHERE vid=$testvid2 AND pid=$testpid
        ");

        $this->assertEquals(
            1,
            $stmt->fetchAll()[0]['position'],
            "Position of vid2 after swap should be 1"
        );
    }

    /**
     * @depends testAddPlaylist
     */
    public function testSearchPlaylist() {

        
        // playlists to test with
        
        $testplaylists[0]['title'] = "the title";
        $testplaylists[0]['description'] = "the description";

        $testplaylists[1]['title'] = "apple";
        $testplaylists[1]['description'] = "orange";

        $testplaylists[2]['title'] = "the tit";
        $testplaylists[2]['description'] = "the des";

        // insert them
        for ($i = 0; $i < count($testplaylists); ++$i) {
            $res = $this->playlistManager->addPlaylist(
                $testplaylists[$i]['title'], 
                $testplaylists[$i]['description'], 
                $this->thumbnail
            );
            $testplaylists[$i]['pid'] = $res['pid'];
        }


        // assert that valid search is okay
        $res = $this->playlistManager->searchPlaylists('string', 'title');
        $this->assertEquals(
            'ok',
            $res['status'],
            "Searching for valid thing should be okay"
        );

        // assert that invalid search field is fail
        $res = $this->playlistManager->searchPlaylists('string', 'notarealfield');
        $this->assertEquals(
            'fail',
            $res['status'],
            "Searching in invalid field should fail"
        );

        
        // assert correct amount of stuff returned from search in title
        $res = $this->playlistManager->searchPlaylists('the', 'title');
        $this->assertEquals(
            2,
            count($res['playlists']),
            "Number of playlists in searchresults should be 2 "
        );

        // assert correct amount of stuff returned from search in description
        $res = $this->playlistManager->searchPlaylists('des', 'description');
        $this->assertEquals(
            2,
            count($res['playlists']),
            "Number of playlists in searchresults should be 2 "
        );





    }

    /**
     * @depends testAddPlaylist
     */
    public function testSearchPlaylistMultipleFields() {

        
        // playlists to test with
        
        $testplaylists[0]['title'] = "the title";
        $testplaylists[0]['description'] = "the description";

        $testplaylists[1]['title'] = "apple";
        $testplaylists[1]['description'] = "the orange";

        $testplaylists[2]['title'] = "the tit";
        $testplaylists[2]['description'] = "des";

        // insert them
        for ($i = 0; $i < count($testplaylists); ++$i) {
            $res = $this->playlistManager->addPlaylist(
                $testplaylists[$i]['title'], 
                $testplaylists[$i]['description'], 
                $this->thumbnail
            );
            $testplaylists[$i]['pid'] = $res['pid'];
        }


        // assert that valid search is okay
        $res = $this->playlistManager->searchPlaylistsMultipleFields('string', ['title']);
        $this->assertEquals(
            'ok',
            $res['status'],
            "Searching for valid thing should be okay"
        );

        // assert that invalid search field is fail
        $res = $this->playlistManager->searchPlaylistsMultipleFields('string', ['notarealfield']);
        $this->assertEquals(
            'fail',
            $res['status'],
            "Searching in invalid field should fail"
        );

        
        // assert correct amount of stuff returned from search
        $res = $this->playlistManager->searchPlaylistsMultipleFields('the', ['title', 'description']);
        $this->assertEquals(
            3,
            count($res['playlists']),
            "Number of playlists in searchresults should be 3 "
        );

        // assert correct amount of stuff returned from search
        $res = $this->playlistManager->searchPlaylistsMultipleFields('des', ['description']);
        $this->assertEquals(
            2,
            count($res['playlists']),
            "Number of playlists in searchresults should be 2 "
        );

        // assert correct amount of stuff returned from search in nothing is 0
        $res = $this->playlistManager->searchPlaylistsMultipleFields('des', array());
        $this->assertEquals(
            0,
            count($res['playlists']),
            "Should be 0 (didn't search anywhere)"
        );

        // assert correct amount of stuff returned from search for everything returns everything
        $res = $this->playlistManager->searchPlaylistsMultipleFields('', ['title', 'description']);
        $this->assertEquals(
            3,
            count($res['playlists']),
            "Search for everything should return everything"
        );

    }


    public function testSubscribeUserToPlaylist() {

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



        // assert that subscribing invalid user fails
        $ret = $this->playlistManager->subscribeUserToPlaylist(-1, $testpid);
        $this->assertEquals(
            'fail',
            $ret['status'],
            "Subscribing invalid user should fail"
        );
        
        // assert that subscribing to invalid playlist fails
        $ret = $this->playlistManager->subscribeUserToPlaylist($testuid, -1);
        $this->assertEquals(
            'fail',
            $ret['status'],
            "Subscribing to invalid playlist should fail"
        );



        // assert that subscribing valid user is okay
        $ret = $this->playlistManager->subscribeUserToPlaylist($testuid, $testpid);
        $this->assertEquals(
            'ok',
            $ret['status'],
            "Subscribing valid user to valid playlist should be okay"
        );

        // assert that subscription was registered correctly in db

        $stmt = $this->dbh->prepare('
SELECT *
FROM subscribes_to
WHERE uid=:uid AND pid=:pid
        ');

        $stmt->bindParam(':uid', $testuid);
        $stmt->bindParam(':pid', $testpid);

        $stmt->execute();

        $this->assertEquals(
            1,
            $stmt->rowCount(),
            "Select should select exactly one entry"
        );
        

    }

    /**
     * @depends testSubscribeUserToPlaylist
     */
    public function testIsSubscribed() {

        // add test playlist
        $testtitle = "Sometitle";
        $testdescription = "somedescription";
        $res_addplaylist = $this->playlistManager->addPlaylist($testtitle, $testdescription, $this->thumbnail);
        $testpid = $res_addplaylist['pid'];

        // add testuser 1
        $this->dbh->query("
INSERT INTO user (username, firstname, lastname, password_hash, privilege_level)
VALUES ('','','','',0)
        ");
        $testuid1 = $this->dbh->lastInsertId();

        // add testuser 2
        $this->dbh->query("
INSERT INTO user (username, firstname, lastname, password_hash, privilege_level)
VALUES ('','','','',0)
        ");
        $testuid2 = $this->dbh->lastInsertId();


        // subscribe user 
        $this->playlistManager->subscribeUserToPlaylist($testuid1, $testpid);





        // assert that checking for subscription on valid user and playlist is ok
        $ret = $this->playlistManager->isSubscribed($testuid1, $testpid);
        $this->assertEquals(
            'ok',
            $ret['status'],
            "Valid check should be okay"
        );


        // assert that result is correct
        $this->assertEquals(
            'true',
            $ret['subscribed'],
            "This user should be subscribed"
        );

        // assert that result for non-subscribed user is correct
        $ret = $this->playlistManager->isSubscribed($testuid2, $testpid);
        $this->assertEquals(
            'false',
            $ret['subscribed'],
            "This user should not be subscribed"
        );

    }

    /**
     * @depends testSubscribeUserToPlaylist
     */
    public function testGetSubscribedPlaylists() {

        // add test playlist
        $testtitle = "Sometitle";
        $testdescription = "somedescription";
        $res_addplaylist = $this->playlistManager->addPlaylist($testtitle, $testdescription, $this->thumbnail);
        $testpid = $res_addplaylist['pid'];

        // add testuser 1
        $this->dbh->query("
INSERT INTO user (username, firstname, lastname, password_hash, privilege_level)
VALUES ('','','','',0)
        ");
        $testuid1 = $this->dbh->lastInsertId();

        // add testuser 2
        $this->dbh->query("
INSERT INTO user (username, firstname, lastname, password_hash, privilege_level)
VALUES ('','','','',0)
        ");
        $testuid2 = $this->dbh->lastInsertId();


        // subscribe user 
        $this->playlistManager->subscribeUserToPlaylist($testuid1, $testpid);





        // assert that checking for subscription is okay
        $ret = $this->playlistManager->getSubscribedPlaylists($testuid1);
        $this->assertEquals(
            'ok',
            $ret['status'],
            "Checking for subscription should be okay : " . $ret['message']
        );

        // assert that one result was returned
        $this->assertEquals(
            1,
            count($ret['playlists']),
            "One playlist should be returned as subscribed"
        );

        // assert that correctly subscribed
        $this->assertEquals(
            $testtitle,
            $ret['playlists'][0]->title,
            "User should be subscribed to this playlist (isn't)"
        );



        // assert that other user not subscribed
        $ret = $this->playlistManager->getSubscribedPlaylists($testuid2);
        $this->assertEquals(
            0,
            count($ret['playlists']),
            "This user shouldn't be subscribed to any playlists"
        );
    }


    /**
     * @depends testAddMaintainerToPlaylist
     */
    public function testGetPlaylistsUserMaintains() {

        // add test playlist
        $testtitle = "Sometitle";
        $testdescription = "somedescription";
        $res_addplaylist = $this->playlistManager->addPlaylist($testtitle, $testdescription, $this->thumbnail);
        $testpid = $res_addplaylist['pid'];

        // add testuser 1
        $this->dbh->query("
INSERT INTO user (username, firstname, lastname, password_hash, privilege_level)
VALUES ('','','','',0)
        ");
        $testuid1 = $this->dbh->lastInsertId();

        // add testuser 2
        $this->dbh->query("
INSERT INTO user (username, firstname, lastname, password_hash, privilege_level)
VALUES ('','','','',0)
        ");
        $testuid2 = $this->dbh->lastInsertId();


        // subscribe user 
        $this->playlistManager->addMaintainerToPlaylist($testuid1, $testpid);





        // assert that checking for subscription is okay
        $ret = $this->playlistManager->getPlaylistsUserMaintains($testuid1);
        $this->assertEquals(
            'ok',
            $ret['status'],
            "Checking for what playlists user maintains should be okay : " . $ret['message']
        );

        // assert that one result was returned
        $this->assertEquals(
            1,
            count($ret['playlists']),
            "One playlist should be returned as maintainer of"
        );

        // assert that correctly subscribed
        $this->assertEquals(
            $testtitle,
            $ret['playlists'][0]->title,
            "User should be maintainer of this playlist (isn't)"
        );



        // assert that other user not subscribed
        $ret = $this->playlistManager->getPlaylistsUserMaintains($testuid2);
        $this->assertEquals(
            0,
            count($ret['playlists']),
            "This user shouldn't be a maintainer to any playlists"
        );
    }



}
