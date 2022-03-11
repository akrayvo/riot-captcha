<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
//error_reporting(0);
//ini_set('display_errors', 0);

require_once('./RiotCaptcha.php');

RiotCaptcha::setCaptchaTextFilePath('./captcha_info');

RiotCaptcha::outputImage();