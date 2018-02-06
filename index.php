<?

require_once 'vendor/autoload.php';
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

// If page is unset show index page, if it is set load correct page based on it
if (!isset($_GET['page'])) {

    $twig_file_to_render = 'index.twig';

} else {

    // page stores parameter passed by GET. Contains an indication of what 
    // page to be shown
    $page = $_GET['page'];

    // Switch on page (DEBUG: just indicate that it's working)
    switch ($page) {
    case 'admin':
        $twig_file_to_render = 'debug.twig';
        $twig_arguments = array('message' => 'admin page');
        break;
    case 'videos':
        $twig_file_to_render = 'debug.twig';
        $twig_arguments = array('message' => 'vidoes page');
        break;
    default:
        $twig_file_to_render = 'notfound.twig';
    }
}

// Render page
echo $twig->render($twig_file_to_render, $twig_arguments);
