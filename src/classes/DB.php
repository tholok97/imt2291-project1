<?php

class DB {
  private static $db=null;
  private $dsn = 'mysql:dbname=imt2291_project1_db;host=localhost';
  private $user = 'root';
  private $password = '';
  private $dbh = null;

  private function __construct($dsn) {
    $this->dsn = $dsn;
    try {
        $this->dbh = new PDO($this->dsn, $this->user, $this->password);
    } catch (PDOException $e) {

        // NOTE IKKE BRUK DETTE I PRODUKSJON
        echo 'Connection failed: ' . $e->getMessage();
    }
  }

  public static function getDBConnection($dsn = 'mysql:dbname=imt2291_project1_db;host=localhost') {
      if (DB::$db==null) {
        DB::$db = new self($dsn);
      }
      return DB::$db->dbh;
  }
}
