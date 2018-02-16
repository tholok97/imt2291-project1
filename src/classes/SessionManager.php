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
     * @return void
     */
    public function put($name, $object) {
        $_SESSION[$this->sessionVariableName][$name] = $object;
    }

    /**
     * Get a named object from the session
     * @param $name
     * @return the object or null if error
     */
    public function get($name) {
        if (!isset($_SESSION[$this->sessionVariableName][$name])) {
            return null;
        } else {
            return $_SESSION[$this->sessionVariableName][$name];
        }
    }

    /**
     * Reset the session storage used by this object
     * @return void
     */
    public function clean() {
        unset($_SESSION[$this->sessionVariableName]);
    }

}
