<?php

namespace App\Services;

use App\Models\Bus;
use App\Models\BusPointsHistory;
use App\Models\Road;
use Carbon\Carbon;
use Faker\Core\Number;
use Illuminate\Support\Facades\Log;

class RouteService
{

    public static function findClosestPointOnRoute(array $routeCoords, array $station)
    {
        $minDistance = PHP_FLOAT_MAX;
        $closestPoint = null;

        for ($i = 0; $i < count($routeCoords) - 1; $i++) {
            $projected = self::getProjectedPoint($routeCoords[$i], $routeCoords[$i + 1], $station);
            $distance = self::getDistance([$station['latitude'], $station['longitude']], [$projected['latitude'], $projected['longitude']]);

            if ($distance < $minDistance) {
                $minDistance = $distance;
                $closestPoint = ['latitude' => $projected['latitude'], 'longitude' => $projected['longitude']];
            }
        }

        return $closestPoint;
    }

    public static function getProjectedPoint(array $A, array $B, array $P)
    {
        $Ax = $A['latitude'];
        $Ay = $A['longitude'];
        $Bx = $B['latitude'];
        $By = $B['longitude'];
        $Px = $P['latitude'];
        $Py = $P['longitude'];

        $ABx = $Bx - $Ax;
        $ABy = $By - $Ay;
        $APx = $Px - $Ax;
        $APy = $Py - $Ay;

        $AB_AB = $ABx * $ABx + $ABy * $ABy;
        $AB_AP = $ABx * $APx + $ABy * $APy;
        $t = $AB_AP / $AB_AB;

        if ($t < 0) $t = 0;
        if ($t > 1) $t = 1;

        return ['latitude' => $Ax + $t * $ABx, 'longitude' => $Ay + $t * $ABy];
    }

    public static function getDistanceBetweenPoints(array $routeCoords, array $startPoint, array $endPoint)
    {
        $inRadius = 45;
        $startPointOnRoute = self::findClosestPointOnRoute($routeCoords, $startPoint);
        $endPointOnRoute = self::findClosestPointOnRoute($routeCoords, $endPoint);


        if(self::getDistance([$startPoint['latitude'], $startPoint['longitude']], [$startPointOnRoute['latitude'], $startPointOnRoute['longitude']]) > $inRadius &&  self::getDistance([$endPoint['latitude'], $endPoint['longitude']], [$endPointOnRoute['latitude'], $endPointOnRoute['longitude']])) {
            return -1;
        }

        $startPoint = $startPointOnRoute;
        $endPoint = $endPointOnRoute;

        $totalDistance = 0;
        $counting = false;

        for ($i = 0; $i < count($routeCoords) - 1; $i++) {
            $segment = [$routeCoords[$i], $routeCoords[$i + 1]];
            $projectedStart = self::findClosestPointOnRoute($segment, $startPoint);
            $projectedEnd = self::findClosestPointOnRoute($segment, $endPoint);

            if (!$counting && $projectedStart == $startPoint) {
                $counting = true;
                $totalDistance += self::getDistance([$routeCoords[$i]['latitude'], $routeCoords[$i]['longitude']], [$startPoint['latitude'], $startPoint['longitude']]);
            }

            if ($counting) {
                if ($projectedEnd == $endPoint) {
                    $totalDistance += self::getDistance([$routeCoords[$i]['latitude'], $routeCoords[$i]['longitude']], [$endPoint['latitude'], $endPoint['longitude']]);
                    break;
                } else {
                    $totalDistance += self::getDistance([$routeCoords[$i]['latitude'], $routeCoords[$i]['longitude']], [$routeCoords[$i + 1]['latitude'], $routeCoords[$i + 1]['longitude']]);
                }
            }
        }

        return $totalDistance;
    }

    public static function getDistance(array $pointA, array $pointB)
    {
        $earthRadius = 6375171;

        $latFrom = deg2rad($pointA[0]);
        $lonFrom = deg2rad($pointA[1]);
        $latTo = deg2rad($pointB[0]);
        $lonTo = deg2rad($pointB[1]);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) + cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
        return $angle * $earthRadius;
    }

    public static function loop()
    {
        $roads = Road::with('points', 'busStops', 'buses')->get();

        foreach ($roads as $road) {
            $points = $road->points;
            $buses = $road->buses;

            $forward = [];
            $backward = [];
            foreach ($points as $point) {
                if($point->status == 1){
                    $forward[] = $point;
                } else if($point->status == 0){
                    $backward[] = $point;
                }
            }

            $allBuses = WialonService::getAllBusesLocation();

            foreach ($buses as $bus) {
                foreach ($allBuses as $bus_location) {
                    if($bus_location['busId'] == $bus->bus_wialon_id){
                        $wBus = $bus_location;
                        break;
                    }
                }
                if(self::getDistance([$forward[0]->latitude, $forward[0]->longitude], [$wBus['latitude'], $wBus['longitude']])<100){
                    $bus->update(['status' => 1]);
                }
                if(self::getDistance([$backward[0]->latitude, $backward[0]->longitude], [$wBus['latitude'], $wBus['longitude']])<100){
                    $bus->update(['status' => 2]);
                }
            }
        }

        $buses = Bus::with('road')->with('road.busStops')->with('road.points')->get();

        $wBuses = WialonService::getAllBusesLocation();
        foreach ($buses as $bus) {

            foreach ($wBuses as $wBus) {
                if($wBus['busId'] === $bus->bus_wialon_id) {
                    if ($bus->road){

                        $latestPoint = BusPointsHistory::where('bus_id', $bus->id)->latest()->first();

                        if (!$latestPoint) {
                            BusPointsHistory::create([
                                'bus_id' => $bus->id,
                                'latitude' => $wBus['latitude'],
                                'longitude' => $wBus['longitude'],
                            ]);
                            continue;
                        }

                        $lastC = [$latestPoint->latitude,$latestPoint->longitude];
                        $wLastC = [$wBus['latitude'], $wBus['longitude']];


                        if(self::getDistance($lastC, $wLastC) > 50) {
                            BusPointsHistory::create([
                                'bus_id' => $bus->id,
                                'latitude' => $wBus['latitude'],
                                'longitude' => $wBus['longitude'],
                            ]);

                            //

                        }

                    }
                }
            }


        }
        return true;
    }
    public static function getRoadUntilBusStop($road, $busStop) {

        $projection = self::findClosestPointOnRoute($road, $busStop);

        $result = [];
        foreach ($road as $i => $point) {
            $result[] = $point;

            if ($point == $projection) break;

            if ($i < count($road) - 1 && self::isPointBetween($point, $road[$i + 1], $projection)) {
                $result[] = $projection;
                break;
            }
        }

        return $result;
    }

    private static function isPointBetween($A, $B, $P) {
        return (
            min($A['latitude'], $B['latitude']) <= $P['latitude'] &&
            max($A['latitude'], $B['latitude']) >= $P['latitude'] &&
            min($A['longitude'], $B['longitude']) <= $P['longitude'] &&
            max($A['longitude'], $B['longitude']) >= $P['longitude']
        );
    }

}

