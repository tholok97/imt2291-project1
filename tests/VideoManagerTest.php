<?php

use PHPUnit\Framework\TestCase;

require_once dirname(__FILE__) . '/../src/classes/DB.php';
require_once dirname(__FILE__) . '/../src/classes/VideoManager.php';
require_once dirname(__FILE__) . '/../src/classes/Video.php';

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

        // setup videomanager
        $this->videoManager = new VideoManager($this->dbh);
    }

    protected function teardown() {
        if (!$this->dbh->query('DELETE FROM User')) {
            $this->fail("Couldn't clean up database..");
        }
    }

    public function testComment() {
        
    }


}
