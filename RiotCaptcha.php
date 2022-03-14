<?php

/**
 * Class RiotCaptcha - class to create captcha image and validate
 */

class RiotCaptcha
{
    private static $captchaTextFilePath = '';

    private static $imageUrl = '';

    private static $keyVarialble = 'capkey';

    private static $stringVarialble = 'capstr';

    private static $key = '';

    private static $string = '';

    private static $secondsAgo = null;

    private static $validCharacters = 'ACDEFGHJKLMNPRTVWXYZ234679';

    private static $stringLength = 4;
    private static $keyLength = 12;

    private static $imageWidth = 250;
    private static $imageHeight = 80;

    private static $isSuccess = false;

    private static $error = '';

    private static $errorMessageRequired = 'Please enter Match Text';
    private static $errorMessageMismatch = 'Match Text is incorrect';
    private static $errorMessageTimeout = 'Match Text timeout';

    private static $captchaTimoutSeconds = 600; // 10 minutes

    /**
     * DESCRIPTION HERE
     */
    public static function getIsSuccess()
    {
        return self::$isSuccess;
    }

    /**
     * DESCRIPTION HERE
     */
    public static function setCaptchaTextFilePath($value)
    {
        $value = strval($value);
        if (empty($value)) {
            self::$captchaTextFilePath = '';
        } else {
            self::$captchaTextFilePath = $value;
        }
    }

    /**
     * DESCRIPTION HERE
     */
    public static function setImageUrl($value)
    {
        $value = strval($value);
        if (empty($value)) {
            self::$imageUrl = '';
        } else {
            self::$imageUrl = $value;
        }
    }

    /**
     * DESCRIPTION HERE
     */
    public static function initialize($captchaTextFilePath = null)
    {
        if (!empty($captchaTextFilePath)) {
            self::setCaptchaTextFilePath($captchaTextFilePath);
        }
        self::createRandomString();
        self::createRandomKey();
        self::saveToFile();
    }

    /**
     * DESCRIPTION HERE
     */
    private static function currentDateString()
    {
        $dateTime = new DateTime();
        return $dateTime->format('YmdHis');
    }

    private static function getSecondsAgo($ymd)
    {
        $dateFormated = substr($ymd, 0, 4) . '-' .
            substr($ymd, 4, 2) . '-' .
            substr($ymd, 6, 2) . ' ' .
            substr($ymd, 8, 2) . ':' .
            substr($ymd, 10, 2) . ':' .
            substr($ymd, 12, 2);

        try {
            $date = new DateTime($dateFormated);
            $now = new DateTime();
        } catch (Exception $e) {
            return null;
        }

        $diff = $date->diff($now);

        $seconds = $diff->s;
        $seconds += $diff->format('%r%a') * 24 * 60 * 60;
        $seconds += $diff->h * 60 * 60;
        $seconds += $diff->i * 60;

        return $seconds;
    }



    /**
     * DESCRIPTION HERE
     */
    private static function saveToFile()
    {
        if (empty(self::$captchaTextFilePath)) {
            // captcha save file is not setup
            return false;
        }


        $fileHandle = fopen(self::$captchaTextFilePath, 'a+');
        if (!$fileHandle) {
            // failed to create a file handler
            return false;
        }

        $newLineString = self::$string . ' ' . self::$key . ' ' . self::currentDateString();

        if (!is_file(self::$captchaTextFilePath)) {
            // new file, write to it
            fwrite($fileHandle, $newLineString);
            fclose($fileHandle);
            return true;
        }

        $fileSize = filesize(self::$captchaTextFilePath);
        if (empty($fileSize)) {
            // empty file, write to it
            fwrite($fileHandle, $newLineString);
            fclose($fileHandle);
            return true;
        }

        $contents = trim(fread($fileHandle, $fileSize));
        $lines = explode("\n", $contents);
        foreach ($lines as $line) {
            $data = explode(' ', $line);
            if (count($data) == 3) {
                $string = trim($data[0]);
                $key = trim($data[1]);
                if (strcasecmp(self::$string,  $string) === 0 || strcasecmp(self::$key,  $key) === 0) {
                    // fail - string or key already set
                    fclose($fileHandle);
                    return false;
                }
            }
        }

        // write to existing non empty file
        fwrite($fileHandle, "\n" . $newLineString);
        fclose($fileHandle);
        return true;
    }



    /**
     * DESCRIPTION HERE
     */
    private static function getRandomString($characters, $length)
    {
        $str = '';
        $maxRand = strlen($characters) - 1;
        for ($x = 1; $x <= $length; $x++) {
            $rand = rand(0, $maxRand);
            $str .= substr($characters, $rand, 1);
        }
        return $str;
    }

    /**
     * DESCRIPTION HERE
     */
    private static function createRandomString()
    {
        self::$string = self::getRandomString(
            self::$validCharacters,
            self::$stringLength
        );
    }

    /**
     * DESCRIPTION HERE
     */
    private static function createRandomKey()
    {
        $validCharacters = '0123456789' .
            'abcdefghijklmnopqrstuvwxyz' .
            'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        self::$key = self::getRandomString(
            $validCharacters,
            self::$keyLength
        );
    }

    /**
     * DESCRIPTION HERE
     */
    public static function getImageUrl()
    {
        return self::$imageUrl . '?' . self::$keyVarialble . '=' . urlencode(self::$key);
    }

    /**
     * DESCRIPTION HERE
     */
    public static function outputHiddenField()
    {
        echo '<input type="hidden" name="' . self::$keyVarialble . '" value="' . self::$key . '" />';
    }

    /**
     * DESCRIPTION HERE
     */
    public static function getStringVariable()
    {
        return self::$stringVarialble;
    }

    /**
     * DESCRIPTION HERE
     */
    public static function outputImage()
    {
        self::setKeyFromGet();
        if (empty(self::$key)) {
            return;
        }
        self::setStringFromKey();
        if (empty(self::$string)) {
            return;
        }
        self::makeImage();
    }

    /**
     * DESCRIPTION HERE
     */
    private static function setKeyFromGet()
    {
        if (empty($_GET[self::$keyVarialble])) {
            return;
        }

        $key = strval($_GET[self::$keyVarialble]);
        if (empty($key)) {
            return;
        }

        self::$key = $key;
    }

    /**
     * DESCRIPTION HERE
     */
    private static function getFromPost($name)
    {
        if (empty($name)) {
            return '';
        }

        if (empty($_POST[$name])) {
            return '';
        }

        $value = strval($_POST[$name]);
        if (empty($value)) {
            return '';
        }

        return $value;
    }

    /**
     * DESCRIPTION HERE
     */
    private static function setStringFromKey()
    {
        if (empty(self::$key)) {
            return false;
        }

        if (!is_file(self::$captchaTextFilePath)) {
            return false;
        }

        $fileHandle = fopen(self::$captchaTextFilePath, 'r');

        if (!$fileHandle) {
            // failed to create a file handler
            return false;
        }

        $fileSize = filesize(self::$captchaTextFilePath);
        if (empty($fileSize)) {
            // fail, the file is empty
            fclose($fileHandle);
            return false;
        }

        $contents = trim(fread($fileHandle, $fileSize));

        $lines = explode("\n", $contents);
        foreach ($lines as $line) {
            $data = explode(' ', $line);
            if (count($data) == 3) {
                $key = trim($data[1]);

                if (strcmp(self::$key,  $key) === 0) {
                    // match found
                    $secondsAgo = self::getSecondsAgo(trim($data[2]));
                    if ($secondsAgo !== null) {
                        self::$string = trim($data[0]);
                        self::$secondsAgo = $secondsAgo;
                        fclose($fileHandle);
                        return true;
                    }
                }
            }
        }

        fclose($fileHandle);

        return false;
    }

    private static function addBackgroundgColor($img, $start, $end, $colorType)
    {
        $left = $start;
        $top = 0;
        $right = rand($left + (self::$imageWidth * .1), $end);
        $bottom = self::$imageHeight;

        // make sure block isn't too wide
        if ($right - $left > self::$imageWidth * .2) {
            $right = $left + (self::$imageWidth * .2);
        }

        // if block goes almost to the end, extend to the end
        if ($end - $right < self::$imageWidth * .05) {
            $right = $end;
        }

        $rgb = self::getRandomRgb($colorType);

        imagefilledrectangle(
            $img,
            $left, // left
            $top, // top
            $right, // right
            $bottom, // bottom
            self::getGdColor($img, $rgb)
        );

        if ($right < $end) {
            return self::addBackgroundgColor($img, $right, $end, $colorType);
        }
        return $img;
    }

    private static function fillTransparent($img)
    {
        $trans = imagecolorallocate($img, 0, 0, 0);
        imagecolortransparent($img, $trans);
        ImageFill($img, 0, 0, $trans);
        return $img;
    }

    /**
     * DESCRIPTION HERE
     */
    private static function makeImage()
    {
        $w = self::$imageWidth;
        $h = self::$imageHeight;
        $length = strlen(self::$string);

        if ($length < 2) {
            return;
        }

        $widthEach = $w / $length;
        $lightStart = rand(2, $length);
        $lightStartPx = ($lightStart - 1) * $widthEach;

        $img = imagecreate($w, $h);

        $dark = self::getRandomRgb('dark');
        ImageFill($img, 0, 0, self::getGdColor($img, $dark));

        self::addBackgroundgColor($img, rand($widthEach * .3, $lightStartPx * .4), $lightStartPx, 'dark');

        self::addBackgroundgColor($img, $lightStartPx, $w, 'light');


        for ($x = 1; $x <= $length; $x++) {
            // currect character
            $char = substr(self::$string, $x - 1, 1);

            // get light or dark text color depending on the background
            if ($x >= $lightStart) {
                $letterRgb = self::getRandomRgb('dark');
            } else {
                $letterRgb = self::getRandomRgb('light');
            }

            // add transparent background
            $temp = imagecreatetruecolor(10, 14);

            $temp = self::fillTransparent($temp);

            // add character to transparent background
            imagestring($temp, 5, 0, 0, $char, self::getGdColor($temp, $letterRgb));

            // actual character size
            $charWidth = imagesx($temp);
            $charHeight = imagesy($temp);

            $maxWidth = $widthEach * .9;
            $maxHeight = $maxWidth / $charWidth * $charHeight;
            if ($maxHeight > $h * .9) {
                $maxHeight = $h * .9;
                $maxWidth = $maxHeight / $charHeight * $charWidth;
            }
            $newWidth = rand($maxWidth * .6, $maxWidth);
            $newHeight = $newWidth / $charWidth * $charHeight;

            $left = (($x - 1) * $widthEach) + ($widthEach * .05) + rand(0, $widthEach - $newWidth);
            $top = rand($newHeight * .05, $h - $newHeight + 1);
            imagecopyresampled($img, $temp, $left, $top, 0, 0,  $newWidth,  $newHeight,  $charWidth,  $charHeight);
        }


        // roate 3 degress left or right
        if (rand(0, 1) > 0) {
            $degrees = 2;
        } else {
            $degrees = -2;
        }
        $img = imagerotate($img, $degrees, 0);


        // crop to correct size
        //$left = (imagesx($img) - $w) / 2;
        //$top = (imagesy($img) - $h) / 2;
        //$img = imagecrop($img, ['x' => $left, 'y' => $top, 'width' => $w, 'height' => $h]);
        imagescale($img, $w, $h);


        // add rectangles around letters
        for ($x = 1; $x <= $length; $x++) {

            $min = $widthEach * ($x - 1);
            $max = $min + ($widthEach * .1);
            $left = rand($min, $max);

            $numLetters = rand($x, $length);
            $min = $widthEach * $numLetters;
            $max = $min + ($widthEach * .1);
            $right = rand($min, $max);

            $top = rand(1, $h * .15);
            $bottom = rand($h * .85, $h);
            $rgb = self::getRandomRgb();
            imagerectangle($img, $left, $top, $right, $bottom, self::getGdColor($img, $rgb));
        }

        imagefilter($img, IMG_FILTER_GAUSSIAN_BLUR);

        header("Content-Type: image/jpg");

        imagepng($img);
        imagedestroy($img);
    }

    /**
     * DESCRIPTION HERE
     */
    private static function getRandomRgb($type = '')
    {
        if ($type == "dark") {
            $min = 0;
            $max = 60;
            $min2 = 80;
            $max2 = 150;
        } elseif ($type == "light") {
            $min = 210;
            $max = 255;
            $min2 = 150;
            $max2 = 210;
        } else {
            $min = 0;
            $max = 0;
            $min2 = 255;
            $max2 = 255;
        }

        $c = array();
        $c[1] = rand($min, $max);
        $c[2] = rand($min, $max);
        $c[3] = rand($min, $max);
        $c[mt_rand(1, 3)] = rand($min2, $max2);

        return $c;
    }

    /**
     * DESCRIPTION HERE
     */
    private static function getGdColor($obj, $rgb)
    {
        return imagecolorallocate($obj, $rgb[1], $rgb[2], $rgb[3]);
    }

    /**
     * DESCRIPTION HERE
     */
    public static function validate()
    {
        self::$isSuccess = false;

        $key = self::getFromPost(self::$keyVarialble);
        if (empty($key)) {
            self::$error = self::$errorMessageMismatch . ' (1)';
            return false;
        }
        self::$key = $key;

        $matchString = self::getFromPost(self::$stringVarialble);
        if (empty($matchString)) {
            self::$error = self::$errorMessageRequired;
            self::fileCleanup();
            return false;
        }

        self::setStringFromKey();

        if (empty(self::$string)) {
            self::$error = self::$errorMessageMismatch . ' (2)';
            self::fileCleanup();
            return false;
        }

        if (strcasecmp($matchString,  self::$string) !== 0) {
            self::$error = self::$errorMessageMismatch;
            self::fileCleanup();
            return false;
        }

        if (self::$secondsAgo === null || self::$secondsAgo >= self::$captchaTimoutSeconds) {
            self::$error = self::$errorMessageTimeout;
            self::fileCleanup();
            return false;
        }

        self::$isSuccess = true;
        self::fileCleanup();
        return true;
    }

    public static function fileCleanup()
    {
        if (empty(self::$key)) {
            return;
        }

        if (!is_file(self::$captchaTextFilePath)) {
            return;
        }

        $fileHandle = fopen(self::$captchaTextFilePath, 'r');

        if (!$fileHandle) {
            // failed to create a file handler
            return;
        }

        $fileSize = filesize(self::$captchaTextFilePath);
        if (empty($fileSize)) {
            // the file is empty, nothing to cleanup
            fclose($fileHandle);
            return;
        }

        $contents = trim(fread($fileHandle, $fileSize));

        $lines = explode("\n", $contents);
        $newContents = '';
        foreach ($lines as $line) {
            $data = explode(' ', $line);
            if (count($data) == 3) {
                $key = trim($data[1]);

                if (strcasecmp(self::$key,  $key) === 0) {
                    // match found - skip
                } else {
                    $secondsAgo = self::getSecondsAgo(trim($data[2]));

                    if ($secondsAgo !== null && $secondsAgo <= self::$captchaTimoutSeconds) {
                        $newContents .= "\n" . $line;
                    }
                }
            }
        }

        fclose($fileHandle);

        $newContents = trim($newContents);

        $fileHandle = fopen(self::$captchaTextFilePath, 'w');
        fwrite($fileHandle, $newContents);
        fclose($fileHandle);
    }

    public static function getError()
    {
        return self::$error;
    }
}
