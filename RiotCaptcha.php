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
    public static function saveToFile()
    {
    }

    /**
     * DESCRIPTION HERE
     */
    private static function getRandomString($characters, $length) {
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
    private static function createRandomString() {
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
        $validCharacters = '0123456789'.
            'abcdefghijklmnopqrstuvwxyz'.
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
}