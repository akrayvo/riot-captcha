<?php

// error display on. turn off if not testing
error_reporting(E_ALL);
ini_set('display_errors', 1);
//error_reporting(0);
//ini_set('display_errors', 0);

/*
* DESCRIPTION HERE
*/

// include the class file and create a new object
require_once('../RiotCaptcha.php');

if (!empty($_POST['form_submitted'])) {
    echo '<b>POST VARIABLES</b><pre>';
    var_dump($_POST);
    echo '</pre>';
    die();
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Captcha Example - Basic</title>
    <link rel="stylesheet" href="./style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>

<body>
    <h1>Captcha Example - Basic</h1>

    <?php
    RiotCaptcha::setCaptchaTextFilePath('../captcha_info');
    RiotCaptcha::setImageUrl('../captcha.jpg.php');
    RiotCaptcha::initialize();
    
    ?>
    <form method="post">
        <input type="hidden" name="form_submitted" value="1">

        <b>Enter Text</b>
        <br>
        <img src="<?php echo RiotCaptcha::getImageUrl(); ?>" alt="enter text" /><br>
        <input type="text" name="<?php echo RiotCaptcha::getStringVariable(); ?>" value="">
        <?php RiotCaptcha::outputHiddenField(); ?>

        <br><br>
        <div><input type="submit" name="submit" value="submit"></div>

    </form>

</body>

</html>