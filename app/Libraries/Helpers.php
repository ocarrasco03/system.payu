<?php

use Carbon\Carbon;

class Helpers
{
    public static function logResponse($response, $type, $params = null)
    {

        $log['response'] = $response;
        isset($params) ? $log['params'] = $params : null;

        $fp = fopen($type . Carbon::now()->format('Y-m-d') . '.json', 'a+');
        fwrite($fp, json_encode($log) . "\r\n");
        fclose($fp);
    }

    public static function getUserIP()
    {
        if (isset($_SERVER["HTTP_CLIENT_IP"])) {
            return $_SERVER["HTTP_CLIENT_IP"];
        } elseif (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
            return $_SERVER["HTTP_X_FORWARDED_FOR"];
        } elseif (isset($_SERVER["HTTP_X_FORWARDED"])) {
            return $_SERVER["HTTP_X_FORWARDED"];
        } elseif (isset($_SERVER["HTTP_FORWARDED_FOR"])) {
            return $_SERVER["HTTP_FORWARDED_FOR"];
        } elseif (isset($_SERVER["HTTP_FORWARDED"])) {
            return $_SERVER["HTTP_FORWARDED"];
        } else {
            return $_SERVER["REMOTE_ADDR"];
        }
    }
}