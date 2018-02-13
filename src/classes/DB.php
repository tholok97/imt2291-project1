<?php

require_once dirname(__FILE__) . '/../../config.php';

class DB {
  private static $db=null;
  private $dsn;
  private $user;
  private $password;
  private $dbh;

  private function __construct($dsn, $user, $password) {
    $this->dsn = $dsn;
    $this->user = $user;
    $this->password = $password;

    try {
        $this->dbh = new PDO($this->dsn, $this->user, $this->password);
    } catch (PDOException $e) {

        // NOTE IKKE BRUK DETTE I PRODUKSJON
        echo 'Connection failed: ' . $e->getMessage();
    }
  }


  public static function getDBConnection($dsn = Config::DB_DSN, $user = Config::DB_USER, $password = Config::DB_PASSWORD) {
      if (DB::$db==null) {
        DB::$db = new self($dsn, $user, $password);
      }
      return DB::$db->dbh;
  }
}
