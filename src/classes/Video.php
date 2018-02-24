<?php

/**
 * Represents a single video in the system. Public properties because used as 
 * a 'struct'
 */
class Video {
    
    /**
     * Properties taken from database table 'video' and rating-values from 'rated'
     */
    public $vid = -1;
    public $title;
    public $description;
    public $url;
    /*public $thumbnail;*/
    public $uid;
    public $topic;
    public $course_code;
    public $timestamp;
    public $view_count;
    public $mime;
    public $size;
    public $position;

    /**
     * Just constructs a Video object
     *
     * @param int $vid - video id
     * @param string $title
     * @param string $description
     * @param string $url - url to the video file
     * @param int $uid - id to user who uploaded video
     * @param string $topic
     * @param string $course_code
     * @param string $timestamp
     * @param int $view_count - how many views of the video
     * @param string $mime - the format the video is in
     * @param int $size 
     */
    public function __construct($vid, $title, $description, $url, /*$thumbnail,*/ $uid, $topic, $course_code, $timestamp, $view_count, $mime, $size) {
        $this->vid = $vid;
        $this->title = $title;
        $this->description = $description;
        $this->url = $url;
        /*$this->thumbnail = $thumbnail;*/
        $this->uid = $uid;
        $this->topic = $topic;
        $this->course_code = $course_code;
        $this->timestamp = $timestamp;
        $this->view_count = $view_count;
        $this->mime = $mime;
        $this->size = $size;
    }
}
