<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\WialonSession;
use Exception;

class WialonService
{
    private static $apiUrl;
    private static $token;
    private static $sessionId;

    public static function init()
    {
        self::$apiUrl = config('app.wialon_api_url', env('WIALON_API_URL'));
        self::$token = config('app.wialon_api_token', env('WIALON_API_TOKEN'));

        $session = WialonSession::latest()->first();

        self::$sessionId = $session ? $session->session_data : null;
    }

    public static function getBusLocation($busId)
    {
        self::init();
        self::ensureSession();

        try {
            $response = self::fetchData('core/search_item', [
                'params' => json_encode(['id' => $busId, 'flags' => 1025])
            ]);

            return self::formatBusData($response, $busId);
        } catch (Exception $e) {
            return [];
        }
    }

    public static function getAllBusesLocation()
    {
        self::init();
        self::ensureSession();

        try {
            $response = self::fetchData('core/search_items', [
                'params' => json_encode([
                    'spec' => [
                        'itemsType' => 'avl_unit',
                        'propName' => 'sys_name',
                        'propValueMask' => '*',
                        'sortType' => 'sys_name'
                    ],
                    'force' => 1,
                    'flags' => 1025,
                    'from' => 0,
                    'to' => 0
                ])
            ]);

            return self::formatAllBusesData($response);
        } catch (Exception $e) {
            return [];
        }
    }

    private static function ensureSession()
    {
        if (!self::isSessionValid()) {
            self::createSession();
        }
    }

    private static function createSession()
    {
        $loginResponse = Http::get(self::$apiUrl, [
            'svc' => 'token/login',
            'params' => json_encode([
                "token" => self::$token,
                "operateAs" => "",
                "appName" => "",
                "checkService" => ""
            ])
        ]);
        if ($loginResponse->successful()) {
            $loginData = $loginResponse->json();

            // Check if 'eid' is set in the response
            if (isset($loginData['eid'])) {
                self::$sessionId = $loginData['eid'];

                WialonSession::create([
                    'user_id' => 1, // Foydalanuvchi ID ni qo'shing
                    'session_data' => self::$sessionId
                ]);
            } else {
                // Handle the case where 'eid' is not present
                self::$sessionId = null;
                // Optional: Log the error or throw an exception
            }
        } else {
            // Handle the unsuccessful response, e.g., log the error
            self::$sessionId = null;
            // Optional: Check the response body for error messages
            $errorData = $loginResponse->json();
            // Log the error or handle accordingly
        }
    }

    private static function isSessionValid()
    {
        return isset(self::$sessionId);
    }


    private static function fetchData($service, $params)
    {
        $params['sid'] = self::$sessionId;
        $response = Http::get(self::$apiUrl, array_merge(['svc' => $service], $params));


        if ($response->successful()) {

            if(!isset($response->json()['items'])){
                createSession();
            }

            return $response->json();
        }

        return false; // Xato holati
    }

    private static function formatBusData($data, $busId)
    {
        if (isset($data['item']['pos'])) {
            return [
                'busId' => $busId,
                'name' => $data['item']['nm'] ?? '',
                'latitude' => $data['item']['pos']['y'] ?? 0,
                'longitude' => $data['item']['pos']['x'] ?? 0,
                'timestamp' => $data['item']['pos']['t'] ?? 0
            ];
        }
        return [];
    }

    private static function formatAllBusesData($data)
    {
        if (!isset($data['items'])) {
            return [];
        }

        $buses = [];
        foreach ($data['items'] as $bus) {
            if (isset($bus['pos'])) {
                $buses[] = [
                    'busId' => $bus['id'],
                    'name' => $bus['nm'],
                    'latitude' => $bus['pos']['y'],
                    'longitude' => $bus['pos']['x'],
                    'timestamp' => $bus['pos']['t']
                ];
            }
        }

        return $buses;
    }
}
