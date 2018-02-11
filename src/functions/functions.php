<?php

/**
 * Get a timestamp of current time in Oslo, Norway.
 * 
 * @return datetime Date in datetime-format "y-m-d h:i:s".
 */
function setTimestamp() {
    // Datetime from: https://stackoverflow.com/questions/41177335/php-get-current-time-in-specific-time-zone
		$tz = 'Europe/Oslo';
		$tz_obj = new DateTimeZone($tz);
		$now = new DateTime("now", $tz_obj);
        $now_formatted = $now->format('Y-m-d H:i:s');
        return $now_formatted;
}

/**
 * Get a thumbnail from video.
 * 
 * @param array[] An $_FILES[] array. Example $_FILES['uploadedFile'] as input.
 * 
 * @return string/blob A thumbnail as string/blob
 */
function getThumbnail($videoRef) {
    return file_get_contents(dirname(__FILE__) . "/../../temp/temp.png");
}
