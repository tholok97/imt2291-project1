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
function getThumbnail($thumbnailRef) {
    return file_get_contents($thumbnailRef['tmp_name']);
}

/**
 * Build wantsPrivilege for admin page
 *
 * @param $userManager
 *
 * @return assoc array with fields: status, wantsPrivilege, message
 */
function buildWantsPrivilege($userManager) {

    // prepare ret
    $ret['status'] = 'fail';
    $ret['wantsPrivilege'] = array();;
    $ret['message'] = '';

    // fetch wants from db
    $ret_wants = $userManager->getWantsPrivilege();

    // if OK, send wants
    // if not OK, send error message
    if ($ret_wants['status'] == 'ok') {

        // for each want, fetch username and add twig arguments
        foreach ($ret_wants['wants'] as $want) {

            // get more details about user
            $ret_get = $userManager->getUser($want['uid']);

            if ($ret_get['status'] == 'ok') {

                array_push($ret['wantsPrivilege'], [
                    'user' => $ret_get['user'],
                    'wouldLike' => $want['privilege_level']
                ]);

            } else {
                $ret['status'] = 'fail';
                $ret['message'] = "Couldn't fetch name: " . $ret_get['message'];
                return $ret;
            }
        }

        // message if empty
        if (count($ret['wantsPrivilege']) == 0) {
            $ret['message'] = "Ingen har forespurt å få høyere privilegium..";
        }


        // if got this far without fail -> success
        $ret['status'] = 'ok';

    } else {
        $ret['message'] = 'Feil ved henting av forespørsler : ' . 
            $ret_wants['message'];
    }

    return $ret;
}
