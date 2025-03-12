<?php
namespace app\modules\online\helpers;

class EnvironmentHelper {
    public static function getIP(){
        return getenv("REMOTE_ADDR");
    }

    /**
     *
     * browsers 'Opera', 'MSIE 7.0', 'MSIE 6.0', 'Mozilla/5.0';
    browsers_mobile = 'Windows CE', 'NetFront', 'Palm OS', 'Blazer', 'Elaine', 'Opera mini';
     */
    public static function getBrowser(){
        return (isset($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : '';
    }

    /**
     * check ajax query
     * @return bool
     */
    public static function isXmlHttpRequest() {
        return (
            isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
        );
    }
}