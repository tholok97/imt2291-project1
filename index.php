<?php

session_start();

require_once dirname(__FILE__) . '/vendor/autoload.php';
require_once dirname(__FILE__) . '/src/classes/UserManager.php';
require_once dirname(__FILE__) . '/src/functions/functions.php';
require_once dirname(__FILE__) . '/src/classes/VideoManager.php';
require_once dirname(__FILE__) . '/src/classes/SessionManager.php';

/*
 * Entry-point to the entire site. Users are shown the sites they want using 
 * the "page" GET paramter (RewriteRule makes this transparent to the user).
 */



// loader used to fetch files for twig
$loader = new Twig_Loader_Filesystem(__DIR__. '/templates');

// setup twig
$twig = new Twig_Environment($loader, array());

/**
 * Used during rendering of page to select which twig file to render
 * This variable is set during parsing of the $page GET parameter
 * Null by default because during development we want the site to crash properly
 */
$twig_file_to_render = null;

/**
 * Used during rendering of page to send arguments to twig
 * This variable is set during parsing of the $page GET parameter
 */
$twig_arguments = array();



/**
 * used to check status of user logged-in-ed-ness
 */
$userManager = new UserManager(DB::getDBConnection());

/**
 * Used to use video-content
 */
$videoManager = new VideoManager(DB::getDBConnection());

/**
 * Used to use video-content
 */
$sessionManager = new SessionManager();




// page stores parameter passed by GET. Contains an indication of what 
// page to be shown
$page = htmlspecialchars(@$_GET['page']);

// Parameter 1 to be used by page
$param1 = htmlspecialchars(@$_GET['param1']);

// Parameter 2 to be used by page
$param2 = htmlspecialchars(@$_GET['param2']);

//echo "Page: " . $page . ", Param1: " . $param1 . ", Param2: " . $param2;



// The ye old huge if-else of stuff..

if ($page == 'register') {

    $twig_file_to_render = 'register.twig';
} else if (!isset($_SESSION['uid'])) {

    // user is not logged in -> send to login page
    $twig_file_to_render = 'login.twig';

    // TODO: show "please login first" message?

} else if (!$userManager->isValidUser($_SESSION['uid'])) {

    // NOT VALID USER -> bad... show anti-hacker message?
    $twig_file_to_render = 'login.twig';

} else if (!isset($_GET['page'])) {

    // If page is unset show index page, if it is set load correct page based on it
    $twig_file_to_render = 'index.twig';
    $twig_arguments = array('user' => $userManager->getUser(htmlspecialchars($_SESSION['uid']))    //User who is looking on the site.
    );

} else {

    // Switch on page (DEBUG: just indicate that it's working)
    
    switch ($page) {
    case 'upload':
        $twig_file_to_render = 'upload.twig';
        $twig_arguments = array('user' => $userManager->getUser(htmlspecialchars($_SESSION['uid']))    //User who is looking on the site.
        );
        break;
    case 'admin':
        $twig_file_to_render = 'admin.twig';
        $twig_arguments = array('user' => $userManager->getUser(htmlspecialchars($_SESSION['uid']))    //User who is looking on the site.
        );

        // get info
        $ret_wants = buildWantsPrivilege($userManager);

        // if went fine -> show wants
        // if didn't go fine -> show error
        if ($ret_wants['status'] == 'ok') {
            $twig_arguments = array(
                'wantsPrivilege' => $ret_wants['wantsPrivilege'],
                'wantsMessage' => $ret_wants['message']
            );
        } else {
            $twig_arguments = array('wantsMessage' => "Error getting privilege requests: " . 
                $ret_wants['message']);
        }

        break;
    case 'videos':
        if ($param1 == "") {                    // Just page parameter.
            $searchAfter['title'] = true;
            $result = $videoManager->search("",$searchAfter);         // If we search with an empty string we should get all videos.
            if ($result['status'] == 'ok') {
                // Get lecturers firstname and lastname for every hit.
                for($i=0;$i < count($result['result']); $i++) {
                    $res = $userManager->getUser($result['result'][$i]['video']->uid);
                    if ($res['status'] == 'ok') {
                        $result['result'][$i]['lecturer']['firstname'] = $res['user']->firstname;
                        $result['result'][$i]['lecturer']['lastname'] = $res['user']->lastname;
                    }
                }
                $twig_file_to_render = 'showAllVideos.twig';
            $twig_arguments = array('user' => $userManager->getUser(htmlspecialchars($_SESSION['uid'])),    //User who is looking on the site.
                                    'result' => $result['result']);
            }
            else {
                // If error go to index (which most likely was the place they come from with an error-message).
                header('Location: ../');
            }
            
        }
        else if ($param1 != "" && $param2 == "") {                                  // A parameter.
            $video = $videoManager->get($param1);
            $comments = $videoManager->getComments($video['video']->vid);
            $rating = $videoManager->getRating($video['video']->vid);
            $userRating = $videoManager->getUserRating(htmlspecialchars($_SESSION['uid']),$video['video']->vid);      // Check user has rated, and eventually get that rate.
            if($video['status'] == 'ok') {
                $twig_file_to_render = 'showVideo.twig';
                $twig_arguments = array('video' => $video['video'],
                'comments' => $comments['comments'],
                'teacher' => $userManager->getUser($video['video']->uid),   // Publishing user info.
                'rating' => $rating,
                'userRating' => $userRating,
                'user' => $userManager->getUser(htmlspecialchars($_SESSION['uid']))    //User who watch the video.
                );            
            }
            else {
                $twig_file_to_render = 'debug.twig';
                $twig_arguments = array('message' => 'Error: ' . $video['errorMessage']);
            }
        }
        else {
            $video = $videoManager->get($param1);   // To check the uid
            if($video['status'] == 'ok') {
                if($video['video']->uid == htmlspecialchars($_SESSION['uid'])) {
                    $twig_file_to_render = 'editVideo.twig';
                    $twig_arguments = array('video' => $video['video'],
                    'user' => $userManager->getUser(htmlspecialchars($_SESSION['uid']))    //User who edit the video.
                    );
                }
            }
        }
        break;
    case 'search':
        if ($param1 == "result") {                    // Result shuld be retrived.
            $result = $sessionManager->get("searchResult", true);
            if($result != null) {
                 // Get lecturers firstname and lastname for every hit.
                 for($i=0;$i < count($result)-1; $i++) {
                    $res = $userManager->getUser($result[$i]['video']->uid);
                    if ($res['status'] == 'ok') {
                        $result[$i]['lecturer']['firstname'] = $res['user']->firstname;
                        $result[$i]['lecturer']['lastname'] = $res['user']->lastname;
                    }
                }
                $twig_file_to_render = 'showSearch.twig';
                //print_r($result);
                $twig_arguments = array('result' => $result,
                'user' => $userManager->getUser(htmlspecialchars($_SESSION['uid'])),    //User who is looking on the site.
                'toRoot' => '/..'
            );
            }
            else {
                 // Go to search-page without parameters
                header('Location: ../search');
            }
        }
        else if($param1 == "") {                       // Only page parameter, show search-site
            $twig_file_to_render = 'advancedSearch.twig';
            $twig_arguments = array('user' => $userManager->getUser(htmlspecialchars($_SESSION['uid']))    //User who is looking on the site.
            );
        }
        else {                                         // Some unexpected input, reset so we get correct sending of searchForm
            // Go to search-page without parameters
            header('Location: ../search');
        }
        break;
    case 'logout':

        // unset session uid to indicate logged out
        unset($_SESSION['uid']);
        $twig_file_to_render = 'login.twig';
        break;
    default:
        $twig_file_to_render = 'notfound.twig';
    }
}

// Render page
echo $twig->render($twig_file_to_render, $twig_arguments);
