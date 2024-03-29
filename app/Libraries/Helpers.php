<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\File;

class Helpers
{
    public static function logResponse($response, $path, $fileName, $params = null)
    {
        if (!File::isDirectory('log')) {
            mkdir('log', 0755);
        }

        if (!File::isDirectory('log\\' . $path)) {
            mkdir('log\\' . $path, 0755);
        }

        $log['response'] = $response;
        isset($params) ? $log['params'] = $params : null;

        $fp = fopen('log\\' . $path . '\\' . $fileName . '_' . Carbon::now()->format('Y-m-d') . '.json', 'a+');
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