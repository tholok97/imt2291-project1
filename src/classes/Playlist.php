<?php

/**
 * Represents single playlist in the system. Everything is public to 
 * impersonate a struct
 */
class Playlist {
    
    public $pid = -1;
    public $title;
    public $description;
    //topic is the topics the videos cover
    //thumbnail provided through script
    
    /**
     * Array of Video objects
     */
    public $videos;

    /**
     * Array of User objects
     */
    public $maintainers;


    /*
     * fields set directly (no constructor)
     */
}
