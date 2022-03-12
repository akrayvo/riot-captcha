<?php

/*
* DESCRIPTION HERE
*/

// error display on. turn off if not testing
error_reporting(E_ALL);
ini_set('display_errors', 1);
//error_reporting(0);
//ini_set('display_errors', 0);

// include the class file
require_once('../RiotCaptcha.php');

RiotCaptcha::setCaptchaTextFilePath('../captcha_info');
RiotCaptcha::setImageUrl('../captcha.jpg.php');

$isCaptureSuccess = null;
$isCaptchaSubmitted = false;
if (!empty($_POST['form_submitted'])) {
    $isCaptchaSubmitted = true;
    $isCaptureSuccess = RiotCaptcha::validate();
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Captcha Example - Basic</title>
    <link rel="stylesheet" href="./styles.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>

<body>
    <div class="main">
        <h1>Captcha Example - Basic</h1>

        <?php

        if ($isCaptchaSubmitted) {
            if ($isCaptureSuccess) {
                echo '<div class="msg"><b>Captcha Success!</b></div>';
            } else {
                $error = RiotCaptcha::getError();
                echo '<div class="msg msg-error"><b>Captcha Failed!</b><br>' .
                    htmlentities($error) . '</div>';
            }
        }

        
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
    </div>
</body>

</html>