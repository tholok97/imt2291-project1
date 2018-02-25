<?php

class SessionManager {

    private $sessionVariableName = null;

    /**
     * Construct the object
     * @param $SessionVariableName what to call this object in the session
     */
    public function __construct($sessionVariableName= 'SessionManagerStorage') {
        $this->sessionVariableName = $sessionVariableName;
    }

    /**
     * Store a named object in the session
     * @param $name
     * @param $object
     * @param $serialize
     * @return void
     */
    public function put($name, $object, $serialize = false) {
        if ($serialize) {                           // If we want to serialize (if object inside).
            $object = serialize($object);
        }
        $_SESSION[$this->sessionVariableName][$name] = $object;
    }

    /**
     * Get a named object from the session
     * @param $name
     * @param $unserialize
     * @return the object or null if error
     */
    public function get($name, $unserialize = false) {
        if (!isset($_SESSION[$this->sessionVariableName][$name])) {
            return null;
        } else {
            $ret = $_SESSION[$this->sessionVariableName][$name];
            if ($unserialize) {                           // If we want to unserialize (if object inside).
                $ret = unserialize($ret);
            }
            return $ret;
        }
    }

    /**
     * Reset the session storage used by this object
     * @return void
     */
    public function clean() {
        unset($_SESSION[$this->sessionVariableName]);
    }

    /**
     * Unset something
     * @param $name
     * @return void
     */
    public function remove($name) {
        unset($_SESSION[$this->sessionVariableName][$name]);
    }

    /**
     * Print contents of session manager storage
     * FOR DEBUG
     * @return void
     */
    public function print() {
        print_r($_SESSION[$this->sessionVariableName]);
    }

}
