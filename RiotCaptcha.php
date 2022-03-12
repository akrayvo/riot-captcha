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

    private static $validCharacters = 'ACDEFGHJKLMNPRTVWXYZ234679';

    private static $stringLength = 5;
    private static $keyLength = 12;

    private static $imageWidth = 250;
    private static $imageHeight = 70;

    private static $imageObject = null;

    private static $isSuccess = false;

    private static $error = '';

    private static $errorMessageRequired = 'Please enter Match Text';
    private static $errorMessageMismatch = 'Match Text is incorrect';

    private static $captchaTimoutSeconds = 600; // 10 minutes

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
            substr($ymd, 4, 2) . '-'.
            substr($ymd, 6, 2) . ' '.
            substr($ymd, 8, 2) . ':'.
            substr($ymd, 10, 2) . ':'.
            substr($ymd, 12, 2);
        $date = new DateTime($dateFormated);
        $now = new DateTime();

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
            $string = trim($data[0]);
            $key = trim($data[1]);
            if (strcasecmp(self::$string,  $string) === 0 || strcasecmp(self::$key,  $key) === 0) {
                // fail - string or key already set
                fclose($fileHandle);
                return false;
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
                    // success, match found
                    self::$string = trim($data[0]);
                    fclose($fileHandle);
                    return true;
                }
            }
        }

        fclose($fileHandle);

        return false;
    }

    /**
     * DESCRIPTION HERE
     */
    private static function makeImage()
    {
        $rgbAr1 = self::getRandomRgb('dark');
        self::$imageObject = imagecreate(self::$imageWidth, self::$imageHeight);
        ImageFill(self::$imageObject, 0, 0, self::getGdColor(self::$imageObject, $rgbAr1));

        $length = strlen(self::$string);



        $tempRgbAr = self::getRandomRgb('dark');
        imagefilledrectangle(
            self::$imageObject,
            0,
            0,
            self::$imageWidth,
            rand(self::$imageHeight * .4, self::$imageHeight * .6),
            self::getGdColor(self::$imageObject, $tempRgbAr)
        );

        $widthPer = self::$imageWidth / $length;

        for ($x = 1; $x <= $length; $x++) {
            $char = substr(self::$string, $x - 1, 1);
            //$top=rand(3, (self::$imageHeight-34));

            $temp = imagecreate(10, 14);
            //var_dump($rgbAr)
            ImageFill($temp, 0, 0, self::getGdColor($temp, $rgbAr1));
            //ImageFill($temp, 0, 0, self::getTransparent($temp)); 

            $tempRgbAr = self::getRandomRgb('light');
            imagestring($temp, 5, 0, 0, $char, self::getGdColor($temp, $tempRgbAr));
            //$left = ($x-1)*(self::$imageWidth/$length);
            //$left  = $left + rand(0,8)-4;

            //$temp = imagescale($temp,40);

            //$temp = imagerotate($temp, rand(-5,5), 0);



            $charWidth = imagesx($temp);
            $charHeight = imagesy($temp);

            $avgWidth = $widthPer;
            $avgHeight = $avgWidth / $charWidth * $charHeight;
            if ($avgHeight > self::$imageHeight) {
                $avgHeight = self::$imageHeight;
                $avgWidth = $avgHeight / $charHeight * $charWidth;
            }
            $newWidth = rand($avgWidth * .7, $avgWidth);
            $newHeight = $newWidth / $charWidth * $charHeight;

            $leftStart = ($x - 1) * $widthPer;
            $left = $leftStart + rand(-1, $widthPer - $newWidth + 1);
            $top = rand(-1, self::$imageHeight - $newHeight + 1);

            //imagecopyresampled( self::$imageObject , $temp  ,$left, $top, 0 , 0,  27 ,  39,  12 ,  17 );
            imagecopyresampled(self::$imageObject, $temp, $left, $top, 0, 0,  $newWidth,  $newHeight,  $charWidth,  $charHeight);
        }

        //imagefilter(self::$imageObject, IMG_FILTER_GAUSSIAN_BLUR);

        for ($x = 1; $x <= $length; $x++) {

            $left = rand($widthPer * ($x - 1), ($widthPer * ($x - 1) + ($widthPer * .05)));
            $right = $left + $widthPer;
            $top = rand(0, self::$imageHeight * .2);
            $bottom = rand(self::$imageHeight, self::$imageHeight * .8);
            $tempRgbAr = self::getRandomRgb();
            imagerectangle(self::$imageObject, $left, $top, $right, $bottom, self::getGdColor(self::$imageObject, $tempRgbAr));
        }

        header("Content-Type: image/png");

        imagepng(self::$imageObject);
        imagedestroy(self::$imageObject);
    }

    /**
     * DESCRIPTION HERE
     */
    private static function getRandomRgb($type = '')
    {
        if ($type == "dark") {
            $min = 0;
            $max = 100;
            $min2 = 60;
            $max2 = 200;
        } elseif ($type == "light") {
            $min = 140;
            $max = 210;
            $min2 = 50;
            $max2 = 255;
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
                    $sa = self::getSecondsAgo(trim($data[2]));
                    
                    if (self::getSecondsAgo(trim($data[2])) <= self::$captchaTimoutSeconds) {
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
