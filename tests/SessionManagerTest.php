<?php

// need to start session because playing with session stuff
session_start();

use PHPUnit\Framework\TestCase;

require_once dirname(__FILE__) . '/../src/classes/SessionManager.php';

class SessionManagerTest extends TestCase {

    private $sessionManager = null;
    private $sessionVariableName = "testingStorage";

    protected function setup() {

        // construct sm
        $this->sessionManager = new SessionManager($this->sessionVariableName);
    }

    protected function teardown() {
        $this->sessionManager = null;
    }

    public function testPut() {

        // prepare what to test put
        $testname = 'test';
        $testobj = array('thomas er kul", "thomas er megakul"');
        
        // put it
        $this->sessionManager->put($testname, $testobj);

        // assert that field was actually set
        if (!isset($_SESSION[$this->sessionVariableName][$testname])) {
            $this->fail("Session variable wasn't set after put");
        }

        // test that it was put
        $this->assertEquals(
            $testobj,
            $_SESSION[$this->sessionVariableName][$testname],
            "Object stored in session should be equal to object put"
        );

    }

    public function testGet() {

        // prepare what to test get
        $testname = 'test';
        $testobj = array('thomas er kul", "thomas er megakul"');
        
        // hardput it into session
        $_SESSION[$this->sessionVariableName][$testname] = $testobj;

        // try and get it
        $ret = $this->sessionManager->get($testname);


        // assert that success
        if ($ret == null) {
            $this->fail("Get of valid object failed");
        }

        // assert that correct object was returned
        $this->assertEquals(
            $testobj,
            $ret,
            "Gotten object should be same as inserted one"
        );

        // assert that get of non-existant object fails
        $this->assertEquals(
            null,
            $this->sessionManager->get('NOTANACTUALTHING'),
            "Invalid get should return null"
        );
    }

    public function testClean() {

        // insert some stuff
        $testname = 'test';
        $testobj = array('thomas er kul", "thomas er megakul"');
        $_SESSION[$this->sessionVariableName][$testname] = $testobj;

        // clean
        $this->sessionManager->clean();

        // assert that it was actually cleaned
        $this->assertEquals(
            false,
            isset($_SESSION[$this->sessionVariableName]),
            "Clean should clean.. session not clean NEVER CLEAN"
        );

    }

}
