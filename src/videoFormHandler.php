<?php

session_start();

require_once dirname(__FILE__) . '/classes/VideoManager.php';

// Go to video-page
header('Location: ../videos/' . $_POST['id']);
exit();