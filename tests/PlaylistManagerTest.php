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

    }
}
