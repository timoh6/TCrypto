<?php
namespace TCrypto\Tools;

/**
 * Str.
 *
 * @author timoh <timoh6@gmail.com>
 * @license Public Domain
 */

class StringUtil
{
    /**
     * @param string
     * @return int
     */
    public static function byteStrlen($str)
    {
        if (function_exists('mb_strlen'))
        {
            return mb_strlen($str, '8bit');
        }

        return strlen($str);
    }


    /**
     * @param string $str
     * @param int $start
     * @param int $length
     * @return string
     */
    public static function byteSubstr($str, $start, $length)
    {
        if (function_exists('mb_substr'))
        {
            return mb_substr($str, $start, $length, '8bit');
        }

        return substr($str, $start, $length);
    }


    /**
     * @param string $haystack
     * @param string $needle
     * @param int $offset
     * @return bool|int
     */
    public static function byteStrpos($haystack, $needle, $offset = 0)
    {
        if (function_exists('mb_strpos'))
        {
            return mb_strpos($haystack, $needle, $offset, '8bit');
        }

        return strpos($haystack, $needle, $offset);
    }

}