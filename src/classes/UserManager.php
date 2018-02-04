<?php

require_once 'DB.php';
require_once '../constants.php';
require_once '../../config.php';

/**
 * Manages users in the systems. Talks to the db.
 */
class UserManager {

    /**
     * Adds a user to the system.
     *
     * @param User $user User to be added to the system
     * @param string $password The password to give the user
     *
     * @return TODO
     */
    public function addUser($user, $password) {

        // TODO
    }

    /**
     * Gets a user from the system based on uid
     *
     * @param int $uid The id of the user
     *
     * @return TODO
     */
    public function getUser($uid) {

        // TODO
    }

    /**
     * Removes a user from the system based on uid
     *
     * * Should remove all rows associated with that user also
     *
     * @param int $uid The id of the user
     *
     * @return TODO
     */
    public function removeUser($uid) {
        
        // TODO
    }
}
