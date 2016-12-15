<?php
namespace   TKMF\LDAP;

/**
 * LDAPUtil
 * ========
 * @package TKMF\LDAP
 * @author Gaëtan Simon <gaetan.simon@thyssen.fr>
 */
abstract class LDAPUtil {

    private static $_escapeChars = [
        '*', '(', ')', '\\', null
    ];

    public static function timestamp($filetime) {
        $timestamp = (new \DateTime())->setTimestamp(intval($filetime / 10000000 - 11644473600, 10));
        return $timestamp;
    }

    /**
     * @param string $DNPart
     * @param char $delimiter
     * @param string $haystack
     * @return string
     */
    public static function str2dnpart($DNPart, $delimiter, $haystack) {
        $imp = [];
        $parts = explode($delimiter, $haystack);
        if($delimiter !== '.') $parts = array_reverse($parts);
        foreach($parts as $v) {
            if($DNPart !== '')
                $imp[] = "{$DNPart}={$v}";
            else $imp[] = $v;
        }
        return implode(',', $imp);
    }

    public static function unicode($string) {
        $unicode = iconv("UTF-8", "UTF-16LE", "\"{$string}\"");
        return $unicode;
    }

    /**
     * @param string $value
     * @return string
     */
    public static function escape($value) {
        $str = "";
        for($i = 0; $i < strlen($value); $i++) {
            $chr = $value{$i};
            if(in_array($chr, self::$_escapeChars))
                $str .= '\\' . dechex(ord($chr));
            else
                $str .= $chr;
        }
        return $str;
    }

}