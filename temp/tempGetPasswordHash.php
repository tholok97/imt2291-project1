<html>
<head></head>
<body>
<form method='post'>
<label>Password to hash</label> <input type='password' name='pass'></input><input type='submit' name='submit' value='Se hash'></input>
</form>
<?php
if (isset($_POST['submit'])) {
    echo "\n\nHash: " . password_hash($_POST['pass'], PASSWORD_DEFAULT);
}
?>