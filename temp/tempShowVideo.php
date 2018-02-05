<html>
<head></head>
<body>
<form method='post'>
<label>Video id</label> <input type='number' name='id'></input><input type='submit' name='submit' value='Se video'></input>
</form>

<?php
require_once "../src/classes/VideoManager.php";

if (isset($_POST['submit'])) {
    $video = new VideoManager(DB::getDBConnection());

    $res = $video->get($_POST['id']);

    print_r($res);

    echo '<br><br><br><video width="320" height="240" controls>
    <source src="' . $res['video']->url . '" type="' . $res['video']->mime . '">
    Your browser does not support the video tag.
  </video>
  ';
}
?>

</body>
</html>