<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\DbUnit\TestCaseTrait;
use Behat\Mink\Element\DocumentElement;
use Behat\Mink\Element\NodeElement;

require_once dirname(__FILE__) . '/../src/classes/DB.php';
require_once dirname(__FILE__) . '/../vendor/autoload.php';
require_once dirname(__FILE__) . '/testFunctions.php';



class FunctionalTests extends TestCase {
    protected $baseUrl = "http://localhost/imt2291-project1/";
    protected $session;
    protected $user;
    protected $userData;

    /**
     * vids of added videos. used in teardown (to properly teardown 
     * in_playlist for example)
     */
    protected $vidsOfAddedVideos = array();


    protected $testusername = "usernameVERYVERYUNIQUESOUNIQUEWOW";
    protected $testpassword = "passwordDAAAAMNTHATISONEUNIQUEPASSWORD";
    protected $testuid;

    /**
     * set when creating playlist. Used in teardown
     */
    protected $testpid = -1;

    protected $testPlaylistTitleString = "playlistTitleOOOOHSOUNIQUEWOWWW";



    protected function setup() {

        $driver = new \Behat\Mink\Driver\GoutteDriver();
        $this->session = new \Behat\Mink\Session($driver);
        $this->session->start();

        $this->dbh = DB::getDBConnection();

        // assert that it could connect
        if ($this->dbh == null) {
            $this->fail("Couldn't make connection to database");
        }


        // INSERT TEST USERS



        // generate hash
        $hash = password_hash($this->testpassword, PASSWORD_DEFAULT);

        // insert testuser into database
        $stmt = $this->dbh->prepare('
            INSERT INTO user (username, firstname, lastname, password_hash, privilege_level)
            VALUES (:username, "firstname", "lastname", :hash, 2)
        ');

        $stmt->bindParam(':username', $this->testusername);
        $stmt->bindValue(':hash', $hash);


        if (!$stmt->execute()) {
            $this->fail("Couldn't insert test user : " . $stmt->errorCode());
        }

        $this->testuid = $this->dbh->lastInsertId();

        if (!password_verify($this->testpassword, $hash)) {
            $this->fail("Password isn't right..");
        }
    }

    protected function teardown() {

        // remove "in_playlist" entries that reference vids we've created
        foreach ($this->vidsOfAddedVideos as $vid) {
            if (!$this->dbh->query("DELETE FROM in_playlist WHERE vid=$vid")) {
                $this->fail("Couldn't clean up database (in_playlist)..");
            }
        }

        if (!$this->dbh->query("DELETE FROM video WHERE uid=$this->testuid")) {
            $this->fail("Couldn't clean up database (video)..");
        }
        if (!$this->dbh->query("DELETE FROM maintains WHERE uid=$this->testuid")) {
            $this->fail("Couldn't clean up database (maintains)..");
        }
        if (!$this->dbh->query("delete from playlist where title='$this->testPlaylistTitleString'")) {
            $this->fail("couldn't clean up database (playlist)..");
        }
        if (!$this->dbh->query("delete from user where uid=$this->testuid")) {
            $this->fail("couldn't clean up database (user)..");
        }
    }

    /**
     * Test that can load login page and assert that header is "Login page"
     */
    public function testInitialPage() {


        $this->session->visit($this->baseUrl);
        $page = $this->session->getPage();

        $this->assertInstanceOf(
            NodeElement::Class,
            $page->find('css', 'h1'),
            'Should have a h1'
        );


        $header = $page->find('css', 'h1');
        $this->assertEquals(
            'Login page',
            $header->getText(),
            "Header should be 'Login page'"
        );

    }

    protected function loginToSite() {

        // Go to login page
        $this->session->visit($this->baseUrl);
        $page = $this->session->getPage();



        // Logging in
        $form = $page->find('css', 'form');
        $this->assertInstanceOf(NodeElement::Class, $form, 'Unable to locate login form');

        $page->find('css', 'input[name="username"]')->setValue($this->testusername);
        $page->find('css', 'input[name="password"]')->setValue($this->testpassword);
        $form->submit();

        $page = $this->session->getPage();

        return $page;
    }


    /**
     * Test can login
     */
    public function testCanLogin() {


        $page = $this->loginToSite();


        // Check that we are logged in
        $this->assertInstanceOf(
            NodeElement::Class,
            $page->find('css', '.alert-success'),
            "Unable to find 'Logged in!' msg"
        );


        // Reload the page
        $this->session->visit($this->baseUrl);
        $page = $this->session->getPage();


        // Check that we are logged in
        $this->assertEquals(
            "Spillelister du abonnerer på:",
            $page->find('css', 'h1')->getText(),
            "Unable to assert that logged in"
        );

    }



    /**
     * Test if can add playlist through UI
     * @depends testCanLogin
     */
    public function testAddPlaylist() {


        // login
        $page = $this->loginToSite();


        // move to createPlaylist
        $this->session->visit('./createPlaylist');

        $page = $this->session->getPage();

        // Check that we are logged in
        $this->assertInstanceOf(
            NodeElement::Class,
            $page->find('css', 'h1'),
            "Unable to find header in playlist page"
        );
        $this->assertEquals(
            "Lag spilleliste",
            $page->find('css', 'h1')->getText(),
            "h1 should indicate is on create playlist page"
        );


        // FILL OUT FORM

        $form = $page->find('css', '.createPlaylistForm');
        $this->assertInstanceOf(NodeElement::Class, $form, 'Unable to locate createPlaylist form');

        $page->find('css', 'input[name="title"]')->setValue($this->testPlaylistTitleString);
        $page->find('css', 'input[name="description"]')->setValue($this->testPlaylistTitleString);
        // ignore thumbnail completely... set to empty string in db

        $form->submit();


        // get page
        $page = $this->session->getPage();

        // assert success
        $this->assertInstanceOf(
            NodeElement::Class,
            $page->find('css', '.alert-success'),
            "creating of playlist didn't go well (message not success)"
        );

    }


    /**
     * @depends testCanLogin
     */
    public function testAddVideo() {

        $page = $this->loginToSite();


        $page = $this->session->visit('./upload');

        $page = $this->session->getPage();



        /*
         * Assert that form is there, but don't try and add through it. 
         * Difficult to get video to work. 
         * Instead add directly through VideoManager class below
         */

        $form = $page->find('css', '.uploadForm');
        $this->assertInstanceOf(NodeElement::Class, $form, 'Unable to locate createPlaylist form');

        $this->assertInstanceOf(NodeElement::Class, $page->find('css', 'input[name="title"]'), "title field not found");
        $this->assertInstanceOf(NodeElement::Class, $page->find('css', 'textarea[name="descr"]'), "descr field not found");
        $this->assertInstanceOf(NodeElement::Class, $page->find('css', 'input[name="topic"]'), "topic field not found");
        $this->assertInstanceOf(NodeElement::Class, $page->find('css', 'input[name="course"]'), "course field not found");
        $this->assertInstanceOf(NodeElement::Class, $page->find('css', 'input[name="submit"]'), "submit button not found");





        // testvideos
        $testvideos[0]['title'] = "title1";
        $testvideos[1]['title'] = "title2";
        $testvideos[2]['title'] = "title3";




        // add directly through video manager class (Using function provided
        // by Yngve)

        for ($i = 0; $i < count($testvideos); ++$i) {

            $ret = uploadVideoTestdata($testvideos[$i]['title'], "This is a test video", $this->testuid, "Testvideos", "IMT2263", $this->dbh);
            
            $this->assertEquals(
                'ok',
                $ret['status'],
                'Uploading video not ok: ' . $ret['errorMessage']
            );

            $testvideos[$i]['vid'] = $ret['vid'];

        }



        // go to all videos page and assert that videos are here

        $this->session->visit('./videos');
        $page = $this->session->getPage();



        // assert that every test video is present in "all videos"

        foreach ($testvideos as $testvideo) {
            $xpath = '//h4/text()[contains(.,' . $testvideo['title'] . ')]';

            $this->assertInstanceOf(
                NodeElement::Class,
                $this->session->getPage()->find('xpath', $this->session->getSelectorsHandler()->selectorToXpath('xpath', $xpath)),
                "Video should be present in list of all videos"
            );

        }

    }



    /**
     * login, add 3 videos and rearrange them (put the last one at the front)
     *
     *
     * -- Some explanation: --
     *  Our system allows you to reposition videos by swapping the positions of 
     *  two videos at a time. This is what we're testing here. The UI has 
     *  you entering in the position of videos to swap, and then you "submit" 
     *  to swap. 
     *
     *
     * @depends testCanLogin
     * @depends testAddVideo
     * @depends testAddPlaylist
     */
    public function testAddToPlaylistAndRearrangeVideos() {


        $page = $this->loginToSite();


        // ADD THE VIDEOS (this is tested in "testAddVideo")

        // testvideos
        $testvideos[0]['title'] = "title1";
        $testvideos[1]['title'] = "title2";
        $testvideos[2]['title'] = "title3";




        // add directly through video manager class (Using function provided
        // by Yngve)

        for ($i = 0; $i < count($testvideos); ++$i) {

            $ret = uploadVideoTestdata($testvideos[$i]['title'], "This is a test video", $this->testuid, "Testvideos", "IMT2263", $this->dbh);
            
            $this->assertEquals(
                'ok',
                $ret['status'],
                'Uploading video not ok: ' . $ret['errorMessage']
            );

            $testvideos[$i]['vid'] = $ret['vid'];

            array_push($this->vidsOfAddedVideos, $ret['vid']);
        }



        // ADD PLAYLIST (this code is tested in "testAddPlaylist")


        // move to createPlaylist
        $this->session->visit('./createPlaylist');

        $page = $this->session->getPage();

        // FILL OUT FORM

        $form = $page->find('css', '.createPlaylistForm');
        $this->assertInstanceOf(NodeElement::Class, $form, 'Unable to locate createPlaylist form');

        $page->find('css', 'input[name="title"]')->setValue($this->testPlaylistTitleString);
        $page->find('css', 'input[name="description"]')->setValue($this->testPlaylistTitleString);
        // ignore thumbnail completely... set to empty string in db

        $form->submit();



        /*
         * 3 videos and 1 playlists now exist in the system.
         */



        // ADD VIDEOS TO PLAYLIST


        // move to each video page and add video to playlist

        foreach ($testvideos as $testvideo) {

            $this->session->visit($this->baseUrl . '/videos/' . $testvideo['vid']);


            $page = $this->session->getPage();


            // assert that form needed to add to playlist is present
            $this->assertInstanceOf(
                NodeElement::Class,
                $page->find('css', '.addToPlaylistForm'),
                "add to playlist form field not present"
            );


            // FILL OUT FORM
            $form = $page->find('css', '.addToPlaylistForm');
            $this->assertInstanceOf(NodeElement::Class, $form, 'Unable to locate addToPlaylist form');

            $page->find('css', 'input[name="playlistTitle"]')->setValue($this->testPlaylistTitleString);
            // ignore thumbnail completely... set to empty string in db

            $form->submit();



            // reload page -> check that alert is success
            $page = $this->session->getPage();
            $this->assertInstanceOf(
                NodeElement::Class,
                $page->find('css', '.alert-success'),
                "Adding wasn't a success!? no success message"
            );
        }




        // REARRANGE VIDEOS


        // move to admin page for playlist
        $this->session->visit($this->baseUrl . '/playlists');

        $page = $this->session->getPage();




        // click button that takes us to playlist page
        $xpath = '//h4[text()[contains(.,"' . $this->testPlaylistTitleString . '")]]/../form';
        $form = $this->session->getPage()->find('xpath', $this->session->getSelectorsHandler()->selectorToXpath('xpath', $xpath));
        $this->assertInstanceOf(NodeElement::Class, $form, 'Unable to locate view playlist form');
        $form->submit();




        $page = $this->session->getPage();




        // move to admin page
        $form = $page->find('css', '.adminButton');
        $this->assertInstanceOf(NodeElement::Class, $form, 'Unable to move to admin page for playlist');
        $form->submit();


        $page = $this->session->getPage();





        // fill out rearrange form
        $form = $page->find('css', '.rearrangeForm');
        $this->assertInstanceOf(NodeElement::Class, $form, '');

        $page->find('css', 'input[name="position1"]')->setValue(1);
        $page->find('css', 'input[name="position2"]')->setValue(3);
        // ignore thumbnail completely... set to empty string in db

        $form->submit();



        $page = $this->session->getPage();



        // ASSERT THAT VIDEOS ARE NOW REARRANGED

        // assert that somewhere on the screen there is now the text '1 - <thirdtitle>' and '3 - <firsttitle>' 
        // (because they should be swapped

        // (this "somewhere" is actually the list of videos (where you can delete). 
        // Open the admin page for a playlist with videos in it to see)


        $xpath = '//*[contains(.,"1 - ' . $testvideos[2]['title'] . '")]';
        $form = $this->session->getPage()->find('xpath', $this->session->getSelectorsHandler()->selectorToXpath('xpath', $xpath));
        $this->assertInstanceOf(NodeElement::Class, $form, 'Unable to assert that videos were actually swapped');

        $xpath = '//*[contains(.,"3 - ' . $testvideos[0]['title'] . '")]';
        $form = $this->session->getPage()->find('xpath', $this->session->getSelectorsHandler()->selectorToXpath('xpath', $xpath));
        $this->assertInstanceOf(NodeElement::Class, $form, 'Unable to assert that videos were actually swapped');


    }

}
