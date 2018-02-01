<?

/*
 * Entry-point to the entire site. Users are shown the sites they want using 
 * the "page" GET paramter (RewriteRule makes this transparent to the user).
 */

// If page is unset show index page
if (!isset($_GET['page'])) {
    echo 'Index page';
    exit();
}

// page stores parameter passed by GET. Contains an indication of what page to 
// be shown
$page = $_GET['page'];

// Switch on page (DEBUG: just indicate that it's working)
switch ($page) {
case 'admin':
    echo 'Admin page';
    break;
case 'videos':
    echo 'Videos page';
    break;
case 'lecturer':
    echo 'Lecturer page';
    break;
case 'playlists':
    echo 'Playlist page';
    break;
default:
    echo '404 - page not found';
}
