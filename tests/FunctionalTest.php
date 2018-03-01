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
    protected $dbh;


    protected $testusername = "usernameVERYVERYUNIQUESOUNIQUEWOW";
    protected $testpassword = "passwordDAAAAMNTHATISONEUNIQUEPASSWORD";
    protected $testuid;

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
        if (!$this->dbh->query("DELETE FROM video WHERE uid=$this->testuid")) {
            $this->fail("Couldn't clean up database (maintains)..");
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

}
