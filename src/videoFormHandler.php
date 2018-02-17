<?php

session_start();

require_once dirname(__FILE__) . '/classes/VideoManager.php';
require_once dirname(__FILE__) . '/classes/SessionManager.php';

// Go to video-page
header('Location: ../videos/' . $_POST['id']);
exit();
