<?php

session_start();

require_once dirname(__FILE__) . '/vendor/autoload.php';
require_once dirname(__FILE__) . '/src/classes/UserManager.php';
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




// page stores parameter passed by GET. Contains an indication of what 
// page to be shown
$page = @$_GET['page'];



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

} else {


    // Switch on page (DEBUG: just indicate that it's working)
    
    switch ($page) {
    case 'upload':
        $twig_file_to_render = 'upload.twig';
        break;
    case 'admin':
        $twig_file_to_render = 'admin.twig';

        // DEBUG: dummy wants privilege data
        $wantsPrivilege[0]['uid'] = 13;
        $wantsPrivilege[0]['privilege_level'] = 1;
        $wantsPrivilege[1]['uid'] = 14;
        $wantsPrivilege[1]['privilege_level'] = 2;

        $twig_arguments = array('wantsPrivilege' => $wantsPrivilege);

        break;
    case 'videos':
        $twig_file_to_render = 'debug.twig';
        $twig_arguments = array('message' => 'DEBUG: vidoes page');
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
