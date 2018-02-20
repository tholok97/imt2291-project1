<?php

/**
 * Represents a single user in the system. Public properties because used as 
 * a 'struct'
 */
class User {
    
    /**
     * Properties taken pretty much directly from the database table 'User'
     */
    public $uid = -1;
    public $username;
    public $firstname;
    public $lastname;

    /** 
     * Either 0 (Student), 1 (Lecturer) or 2 (Admin)
     * An admin has all the rights of a lecturer and a student, and a lecturer 
     * has all the rights of a student.
     */
    public $privilege_level = 0;

    /**
     * Just constructs a User object
     *
     * @param $username
     * @param $firstname
     * @param $lastname
     * @param $privilege_level
     */
    public function __construct($username, $firstname, $lastname, $privilege_level) {
        $this->username = $username;
        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->privilege_level = $privilege_level;
    }
}
