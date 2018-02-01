<?php

/**
 * Environment-dependant constants.
 *
 * NOTE: This file will change. Remember to check the `config_example.php` 
 * file once in a while
 */

class Constants {

    /**
     * DB constants. Used when connecting to database. Change to make php use 
     * different website (put in your own db details)
     */
    const $DB_DSN = 'mysql:dbname=imt2291_project1;host=127.0.0.1';
    const $DB_USER = 'root';
    const $DB_PASSWORD = 'veldigsikkertpassord';

    /**
     * Db constants for use during testing. Same as above but SHOULD POINT TO 
     * A DIFFERENT DATABASE. One that is disposable
     */
    const $DB_TEST_DSN = 'mysql:dbname=imt2291_project1_test;host=127.0.0.1';
    const $DB_TEST_USER = 'root';
    const $DB_TEST_PASSWORD = 'veldigsikkertpassord';
}
