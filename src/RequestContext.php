<?php

namespace SoftHouse\MonitoringService;

use SoftHouse\MonitoringService\Contracts\RequestContextRepository;
use Illuminate\Support\Facades\Request;
use GuzzleHttp\Client;
use hisorange\BrowserDetect\Parser as Browser;


class RequestContext implements RequestContextRepository
{

    public static function getIP(): string
    {
        $ip = Request::ip();

        if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
            $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
            $ip = $_SERVER["HTTP_CF_CONNECTING_IP"];
        }

        return $ip;
    }

    public static function getInfoIP($ip = null): array
    {
        try {
            if ($ip === null) {
                $ip = self::getIP();
            }

            $client = new Client();

            $promise = $client->requestAsync("GET", "http://ip-api.com/json/${ip}?fields=25")->then(function ($response) {
                return $response->getBody();
            }, function ($exception) {
                return $exception->getMessage();
            });

            $response = $promise->wait();
            $_response = json_decode($response, true);
            $_response['ip'] = $ip;
            $_response['device'] = self::getDevice();

            if(!array_key_exists('city', $_response)){$_response['city'] = null;}
            if(!array_key_exists('regionName', $_response)){$_response['regionName'] = null;}
            if(!array_key_exists('country', $_response)){$_response['country'] = null;}

            return $_response;
        } catch (\Exception $exception) {
            return [];
        }
    }

    public static function getDevice(): array
    {
        return [
            'type' => Browser::deviceType(),
            'name' => Browser::browserName(),
            'platform' => Browser::deviceFamily() . ' - ' . Browser::deviceModel(),
            'system' => Browser::platformName(),
        ];
    }
}
