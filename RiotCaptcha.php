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