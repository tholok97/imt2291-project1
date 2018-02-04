<html>
<head></head>
<body>
  <form method="post" enctype="multipart/form-data">
    <label for="pickFile">Velg en fil</label><input type="file" name="fileToUpload" id="pickFile"><br>
    <label for="title">Tittel</label><input type="text" name="title" id="title"><br>
    <label for="descr">Beskrivelse</label><input type="text" name="descr" id="descr"><br>
    <label for="user">User</label><input type="text" name="user" id="user"><br>
    <label for="topic">Topic</label><input type="text" name="topic" id="topic"><br>
    <label for="course">Course code</label><input type="text" name="course" id="course"><br>
    <input type="submit" name='submit' value="Lagre filen i databasen">
  </form>
<?php
    require_once "../src/classes/VideoManager.php";
if(isset($_POST['submit'])) {
  $video = new VideoManager(DB::getDBConnection());

  $res = $video->upload($_POST['title'],$_POST['descr'],$_POST['user'],$_POST['topic'],$_POST['course'],$_FILES['fileToUpload']);

  print_r($res);
}
?>
</body>
</html>