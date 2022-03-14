<?php

/**
 * Class RiotCaptcha - class to create captcha image and validate
 */

class RiotCaptcha
{
    // path of the text file that contains captcha information
    // *** this file MUST NOT be web accessible. it should be outside of
    //      the web root or blocked via htaccess
    private static $captchaTextFilePath = '';

    // the url of the captcha image
    private static $imageUrl = '';

    // max number of seconds between when the captcha is created and 
    //  the captcha is validated
    private static $captchaTimoutSeconds = 900; // 15 minutes

    // variables used to pass captcha info through post and get
    private static $keyVarialble = 'capkey';
    private static $stringVarialble = 'capstr';

    // the captcha text (text in the image)
    private static $string = '';
    // unique code of the captcha
    private static $key = '';
    // number of seconds since the captcha was created
    private static $secondsAgo = null;

    // characters allowed in the captcha.
    // simlar looking letters are removed. for example 8 and B.
    private static $validCharacters = 'ACDEFGHJKLMNPRTVWXYZ234679';

    // the text lenght of the captch
    private static $stringLength = 4;
    // the length of the key
    private static $keyLength = 8;

    // dimensions of the captcha image
    private static $imageWidth = 180;
    private static $imageHeight = 50;

    // if the captcha was successfully validated
    private static $isSuccess = false;

    // the generated error message if validation was not successful
    private static $error = '';

    // specific error messages
    private static $errorMessageRequired = 'Please enter Match Text';
    private static $errorMessageMismatch = 'Match Text is incorrect';
    private static $errorMessageTimeout = 'Match Text timeout';

    public static function getIsSuccess()
    {
        return self::$isSuccess;
    }

    public static function setCaptchaTextFilePath($value)
    {
        $value = strval($value);
        if (empty($value)) {
            self::$captchaTextFilePath = '';
        } else {
            self::$captchaTextFilePath = $value;
        }
    }

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
     * Create captcha text and key. Save them to a file.
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
     * Get the formatted date string
     */
    private static function currentDateString()
    {
        $dateTime = new DateTime();
        return $dateTime->format('YmdHis');
    }

    // get the time difference in seconds between now and the passed formatted date
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

        if ($diff === false) {
            return null;
        }

        // convert the difference into seconds
        $seconds = $diff->s;
        $seconds += $diff->format('%r%a') * 24 * 60 * 60;
        $seconds += $diff->h * 60 * 60;
        $seconds += $diff->i * 60;

        return $seconds;
    }

    /**
     * Save the captcha text, key, and date created to file
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
     * return a random string from passed characters
     */
    private static function getRandomString($characters, $length)
    {
        $str = '';
        $maxRand = strlen($characters) - 1;
        for ($x = 1; $x <= $length; $x++) {
            $rand = self::rand(0, $maxRand);
            $str .= substr($characters, $rand, 1);
        }
        return $str;
    }

    /**
     * create random string (captcha text)
     */
    private static function createRandomString()
    {
        self::$string = self::getRandomString(
            self::$validCharacters,
            self::$stringLength
        );
    }

    /**
     * create random key (unique identifier)
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
     * get the captcha image url. includes the key.
     * used in the image tag <img src="<?php echo getImageUrl(); ?>">
     */
    public static function getImageUrl()
    {
        return self::$imageUrl . '?' . self::$keyVarialble . '=' . urlencode(self::$key);
    }

    /**
     * output the captcha key in a hidden field
     */
    public static function outputHiddenField()
    {
        echo '<input type="hidden" name="' . self::$keyVarialble . '" value="' . self::$key . '" />';
    }

    /**
     * get variable name for the string (captcha text)
     * <input type="text" name="<?php echo getStringVariable(); ?>">
     */
    public static function getStringVariable()
    {
        return self::$stringVarialble;
    }

    /**
     * get the passed key, find the string (captcha text), write the captcha image
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
     * set the key that was passed in the url ($_GET)
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
     * set the key that was passed in a form ($_POST)
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
     * searches the file for the key and sets the string and number of seconds
     *      since the captcha was created
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
                    self::$string = trim($data[0]);
                    self::$secondsAgo = $secondsAgo;
                    fclose($fileHandle);
                    return true;
                }
            }
        }

        fclose($fileHandle);

        return false;
    }

    // random number with $min, $max check
    private static function rand($min, $max)
    {
        if ($max == $min) {
            return $max;
        }
        if ($max < $min) {
            return round(($min + $max)  / 2);
        }

        return mt_rand($min, $max);
    }

    // adds a section of dark or light color to the captcha image
    private static function addBackgroundgColor($img, $start, $end, $colorType)
    {
        $left = $start;
        $top = 0;
        $right = self::rand($left + (self::$imageWidth * .1), $end);
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

    // fill an element with transparency
    private static function fillTransparent($img)
    {
        $trans = imagecolorallocate($img, 0, 0, 0);
        imagecolortransparent($img, $trans);
        ImageFill($img, 0, 0, $trans);
        return $img;
    }

    /**
     * make the captcha image and output it
     */
    private static function makeImage()
    {
        // shorter variables
        $w = self::$imageWidth;
        $h = self::$imageHeight;
        $len = strlen(self::$string);

        if ($len < 2) {
            return;
        }

        $widthEach = $w / $len;

        // the first characters are light was a dark background. light background
        //      width dark characters starts at the $lightStart character
        $lightStart = self::rand(2, $len);
        $lightStartPx = ($lightStart - 1) * $widthEach;

        $img = imagecreate($w, $h);

        $dark = self::getRandomRgb('dark');
        ImageFill($img, 0, 0, self::getGdColor($img, $dark));

        // dark backgrounds
        self::addBackgroundgColor($img, self::rand($widthEach * .3, $lightStartPx * .4), $lightStartPx, 'dark');

        // light backgrounds
        self::addBackgroundgColor($img, $lightStartPx, $w, 'light');

        // add characters
        for ($x = 1; $x <= $len; $x++) {
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

            // get random image size that fits and has the correct ratio
            $maxWidth = $widthEach * .9;
            $maxHeight = $maxWidth / $charWidth * $charHeight;
            if ($maxHeight > $h * .9) {
                $maxHeight = $h * .9;
                $maxWidth = $maxHeight / $charHeight * $charWidth;
            }
            $newWidth = self::rand($maxWidth * .6, $maxWidth);
            $newHeight = $newWidth / $charWidth * $charHeight;

            // random image position
            $left = (($x - 1) * $widthEach) + ($widthEach * .05) + self::rand(0, $widthEach - $newWidth);
            $top = self::rand($newHeight * .05, $h - $newHeight + 1);

            // add character to the iamge
            imagecopyresampled($img, $temp, $left, $top, 0, 0,  $newWidth,  $newHeight,  $charWidth,  $charHeight);
        }

        // rotate 2 degress left or right
        if (self::rand(0, 1) > 0) {
            $degrees = 2;
        } else {
            $degrees = -2;
        }
        $img = imagerotate($img, $degrees, 0);

        // resize tot the correct size
        imagescale($img, $w, $h);

        // add rectangles around letters
        for ($x = 1; $x <= $len; $x++) {

            $min = $widthEach * ($x - 1);
            $max = $min + ($widthEach * .1);
            $left = self::rand($min, $max);

            $numLetters = self::rand($x, $len);
            $min = $widthEach * $numLetters;
            $max = $min + ($widthEach * .1);
            $right = self::rand($min, $max);

            $top = self::rand(1, $h * .15);
            $bottom = self::rand($h * .85, $h - 1);
            $rgb = self::getRandomRgb();
            imagerectangle($img, $left, $top, $right, $bottom, self::getGdColor($img, $rgb));
        }

        // blur the iage
        imagefilter($img, IMG_FILTER_GAUSSIAN_BLUR);

        // ouput image
        header("Content-Type: image/jpg");
        imagepng($img);

        // cleanup the image object
        imagedestroy($img);
    }

    /**
     * get a random color in RGB format
     */
    private static function getRandomRgb($type = '')
    {
        if ($type == "dark") {
            $min = 0;
            $max = 60;
            $min2 = 80;
            $max2 = 170;
        } elseif ($type == "light") {
            $min = 210;
            $max = 255;
            $min2 = 130;
            $max2 = 210;
        } else {
            $min = 0;
            $max = 255;
            $min2 = 0;
            $max2 = 255;
        }

        $c = array();
        $c[1] = self::rand($min, $max);
        $c[2] = self::rand($min, $max);
        $c[3] = self::rand($min, $max);
        $c[self::rand(1, 3)] = self::rand($min2, $max2);

        return $c;
    }

    /**
     * get color in the PHP GD image class
     */
    private static function getGdColor($obj, $rgb)
    {
        return imagecolorallocate($obj, $rgb[1], $rgb[2], $rgb[3]);
    }

    /**
     * validate the captcha
     */
    public static function validate()
    {
        self::$isSuccess = false;

        // get the key, make sure it exists
        $key = self::getFromPost(self::$keyVarialble);
        if (empty($key)) {
            self::$error = self::$errorMessageMismatch . ' (1)';
            return false;
        }
        self::$key = $key;

        // get the sting, make sure it exists
        $matchString = self::getFromPost(self::$stringVarialble);
        if (empty($matchString)) {
            self::$error = self::$errorMessageRequired;
            self::fileCleanup();
            return false;
        }

        // get the string from the file that matches the key
        self::setStringFromKey();

        // make sure the string in the file exists
        if (empty(self::$string)) {
            self::$error = self::$errorMessageMismatch . ' (2)';
            self::fileCleanup();
            return false;
        }

        // make sure the string from the file matches the passed string
        if (strcasecmp($matchString,  self::$string) !== 0) {
            self::$error = self::$errorMessageMismatch;
            self::fileCleanup();
            return false;
        }

        // make sure the catpcha did not timeout
        if (self::$secondsAgo === null || self::$secondsAgo >= self::$captchaTimoutSeconds) {
            self::$error = self::$errorMessageTimeout;
            self::fileCleanup();
            return false;
        }

        self::$isSuccess = true;
        self::fileCleanup();
        return true;
    }

    // remove the current captcha after it was tested and all captchas that have timed out
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
                        // active captcha found, rewrite it
                        $newContents .= "\n" . $line;
                    }
                }
            }
        }

        // close read handler
        fclose($fileHandle);

        // write to file
        $fileHandle = fopen(self::$captchaTextFilePath, 'w');
        fwrite($fileHandle, trim($newContents));
        fclose($fileHandle);
    }

    public static function getError()
    {
        return self::$error;
    }
}
