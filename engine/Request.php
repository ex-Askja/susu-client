<?php

namespace engine;

use Campo\UserAgent;
use CurlHandle;
use Exception;
use JetBrains\PhpStorm\ArrayShape;

class Request
{
    const defaultCookieFile = 'cookie.txt';
    const defaultDataDir = '__data/';

    /**
     * @param string $url
     * @param array|string $data
     * @param array $headers
     * @param string $dir
     * @return array
     * @throws Exception
     */
    #[ArrayShape(['response' => "\bool|string", 'info' => "mixed", 'error' => "string"])]
    static function post(string $url, array|string $data = [], array $headers = [], string $dir = ''): array
    {
        $connect = self::getConnect($url, $headers, $dir);

        curl_setopt_array($connect, [
            CURLOPT_POSTFIELDS => (is_array($data) ? http_build_query($data) : $data),
            CURLOPT_POST => 1,
        ]);

        $responseData = self::createResponse($connect);

        // print_r($responseData);

        curl_close($connect);

        return $responseData;
    }

    /**
     * @param string $uri
     * @param array $headers
     * @param string $dir
     * @return CurlHandle|bool
     * @throws Exception
     */
    static function getConnect(string $uri, array $headers = [], string $dir = ''): CurlHandle|bool
    {
        $ch = curl_init($uri);

        if (!is_dir(self::defaultDataDir)) {
            mkdir(self::defaultDataDir);
        }

        if (!is_dir(self::defaultDataDir . $dir)) {
            mkdir(self::defaultDataDir . $dir);
        }

        curl_setopt_array($ch, [
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_USERAGENT => UserAgent::random(),
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_COOKIEJAR => self::defaultDataDir . $dir . self::defaultCookieFile,
            CURLOPT_COOKIEFILE => self::defaultDataDir . $dir . self::defaultCookieFile,
            CURLOPT_HTTPHEADER => $headers,
        ]);

        return $ch;
    }

    /**
     * @param $connect
     * @return array
     */
    #[ArrayShape(['response' => "bool|string", 'info' => "mixed", 'error' => "string"])]
    static function createResponse($connect): array
    {
        return [
            'response' => curl_exec($connect),
            'info' => curl_getinfo($connect),
            'error' => curl_error($connect),
        ];
    }

    /**
     * @param string $uri
     * @param array $headers
     * @param string $dir
     * @return array
     * @throws Exception
     */
    #[ArrayShape(['response' => "\bool|string", 'info' => "mixed", 'error' => "string"])]
    static function get(string $uri, array $headers = [], string $dir = ''): array
    {
        $connect = self::getConnect($uri, $headers, $dir);

        $responseData = self::createResponse($connect);

        curl_close($connect);

        return $responseData;
    }

    /**
     * Void
     */
    static function flushCookie(string $dir): void
    {
        if (is_file(self::defaultDataDir . $dir . self::defaultCookieFile) && file_exists(self::defaultDataDir . $dir . self::defaultCookieFile)) {
            unlink(self::defaultDataDir . $dir . self::defaultCookieFile);
        }
    }
}