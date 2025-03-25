<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Exception;


class WialonService
{
    private static $apiUrl;
    private static $token;

    public static function init()
    {
        self::$apiUrl = config('app.wialon_api_url', env('WIALON_API_URL'));
        self::$token = config('app.wialon_api_token', env('WIALON_API_TOKEN'));
    }

    // Bitta avtobus joylashuvini olish
    public static function getBusLocation($busId)
    {
        self::init();

        try {
            $loginResponse = Http::get(self::$apiUrl, [
                'svc' => 'token/login',
                'params' => json_encode(['token' => self::$token])
            ]);

            if (!$loginResponse->successful()) {
                return [];
            }

            $loginData = $loginResponse->json();
            if (!isset($loginData['eid'])) {
                return [];
            }

            $sessionId = $loginData['eid'];

            $response = Http::get(self::$apiUrl, [
                'svc' => 'core/search_item',
                'params' => json_encode([
                    'id' => $busId,
                    'flags' => 1025
                ]),
                'sid' => $sessionId
            ]);

            if (!$response->successful()) {
                return [];
            }

            $data = $response->json()['item'] ?? [];

            Http::get(self::$apiUrl, [
                'svc' => 'core/logout',
                'sid' => $sessionId
            ]);

            if (!isset($data['pos'])) {
                return [];
            }

            return [
                'busId' => $busId,
                'name' => $data['nm'] ?? '',
                'latitude' => $data['pos']['y'] ?? 0,
                'longitude' => $data['pos']['x'] ?? 0,
                'timestamp' => $data['pos']['t'] ?? 0
            ];
        } catch (Exception $e) {
            return [];
        }
    }

    public static function getAllBusesLocation()
    {
        self::init();

        try {
            $loginResponse = Http::get(self::$apiUrl, [
                'svc' => 'token/login',
                'params' => json_encode(['token' => self::$token])
            ]);

            if (!$loginResponse->successful()) {
                return [];
            }

            $loginData = $loginResponse->json();
            if (!isset($loginData['eid'])) {
                return [];
            }

            $sessionId = $loginData['eid'];

            $response = Http::get(self::$apiUrl, [
                'svc' => 'core/search_items',
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
                ]),
                'sid' => $sessionId
            ]);

            if (!$response->successful()) {
                return [];
            }

            $data = $response->json();

            Http::get(self::$apiUrl, [
                'svc' => 'core/logout',
                'sid' => $sessionId
            ]);

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
        } catch (Exception $e) {
            return [];
        }
    }
}
