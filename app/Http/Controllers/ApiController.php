<?php

namespace App\Http\Controllers;

use App\Models\Bus;
use App\Models\BusPointsHistory;
use App\Models\BusStop;
use App\Models\Road;
use App\Models\RoadBusstop;
use App\Services\WialonService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Services\RouteService;
use Illuminate\Support\Facades\Http;

class ApiController extends Controller
{
    public function index()
    {
        return response()->json();
    }

    private $wialonToken = "a48df18e04335d64cb11bbb98e0d26267FDCD4A5F48768E45BE07DD5FCF0D2FBB1DF3F9F"; // Wialon API token

    public function getBusLocation(Request $request)
    {
        $busId = $request->query('busId');

        if (!$busId) {
            return response()->json(['error' => 'busId required'], 400);
        }

        $busLocation = WialonService::getBusLocation($busId);

        return response()->json($busLocation);
    }
    public function getAllBusesLocation()
    {
        $wBuses = WialonService::getAllBusesLocation();
        $buses = Bus::with('road')->get();

        $data = [];
        foreach ($buses as $bus) {

            if ($bus->road === null){
                continue;
            }

            foreach ($wBuses as $wBus) {
                if ($wBus['busId'] == $bus->bus_wialon_id) {
                    $data[] = [
                        'busId' => $wBus['busId'],
                        'name' => $wBus['name'],
                        'latitude' => $wBus['latitude'],
                        'longitude' => $wBus['longitude'],
                        'timestamp' => $wBus['timestamp'],
                    ];
                }
            }
        }

        return response()->json($data);
    }

    public function wait(Request $request)
    {
        $busStopId = $request->query('id');

        if($busStopId === null){
            return response()->json(['error' => 'Your id not found in data'], 400);
        }

        $busStop = BusStop::with('roads.buses')->with('roads.busStops')->find($busStopId);

        if($busStop === null){
            return response()->json(['error' => 'Your id not found in data'], 400);
        }


        $cBusStop = [
            'latitude' => $busStop->latitude,
            'longitude' => $busStop->longitude,
        ];

        $wBuses = WialonService::getAllBusesLocation();

        $result = [];

        $status = true;



        foreach ($busStop->roads as $road) {

            $status = RoadBusstop::where('road_id', $road->pivot->road_id)->where('bus_stop_id', $road->pivot->bus_stop_id)->first()->status;
            $data=[];
            foreach ($road->buses as $bus) {
                $cBus = null;

                foreach ($wBuses as $wBus) {
                    if($wBus['busId'] == $bus->bus_wialon_id){

                        $roadPoints = $road->points->map(function ($stop) {
                            return [
                                'id' => $stop->id,
                                'latitude' => $stop->latitude,
                                'longitude' => $stop->longitude,
                                'status' => $stop->status,
                            ];
                        })->toArray();

                        $nd =[];
//                        $nd2 = [];
                        foreach ($roadPoints as $roadPoint) {
                            if ($roadPoint['status'] === $status) {
                                $nd []= $roadPoint;
                            } else {
//                                $nd2[]= $roadPoint;
                            }
                        }
                        $roadPoints = $nd;


                        $newRoadPoint = RouteService::getRoadUntilBusStop($roadPoints, $cBusStop);


                        $history = BusPointsHistory::where('bus_id', $bus->id)->orderBy('id', 'desc')->take(2)->get();

                        if($history->count() < 2){
                            continue;
                        }

                        $last1 = $history[0];
                        $last2 = $history[1];


                        $dc1 = RouteService::getDistanceBetweenPoints($newRoadPoint, ['latitude'=>$last1->latitude, "longitude" => $last1->longitude], $cBusStop);
                        $dc2 = RouteService::getDistanceBetweenPoints($newRoadPoint, ['latitude'=>$last2->latitude, "longitude" => $last2->longitude], $cBusStop);


//                        $newRoadPoint2 = RouteService::getRoadUntilBusStop($nd2, $roadPoints[0]);
//                        $md = 0;
//                        if ($dc1<0 || $dc2<0) {
//                            $mdc1 = RouteService::getDistanceBetweenPoints($newRoadPoint2, ['latitude'=>$last1->latitude, "longitude" => $last1->longitude], $roadPoints[0]);
//                            $mdc2 = RouteService::getDistanceBetweenPoints($newRoadPoint2, ['latitude'=>$last2->latitude, "longitude" => $last2->longitude], $roadPoints[0]);
//
//                            if($dc1 < $dc2){
//                                $md = $dc = RouteService::getDistanceBetweenPoints($newRoadPoint2, $wBus, $roadPoints[0]);
//                            }
//                        }

                        if ($dc1 > $dc2) {
                            continue;
                        }

                        $dc = RouteService::getDistanceBetweenPoints($newRoadPoint, $wBus, $cBusStop);

                        if ($dc < 0) {
                            continue;
                        }

                        $cBus = [
                            'id' => $bus->id,
                            'number' => $bus->bus_number,
                            'latitude' => $wBus['latitude'],
                            'longitude' => $wBus['longitude'],
                            'distance' => $dc,
                        ];
                        break;
                    }
                }
                if($cBus == null) {
                    continue;
                }
                $data[] = $cBus;
            }

            if(count($data) < 1){
                continue;
            }
            $obj = $data[0];
            foreach ($data as $item) {
                if ($item['distance'] > 0 && $item['distance'] < $obj['distance']) {
                    $obj = $item;
                }
            }

            $bus = Bus::with('road')->find($obj['id']);
            $time = $obj['distance'] / 8.0;

            $result[] = [
                'route' => $bus->road->road_number,
                'bus' => $bus->bus_number,
                'time' => round($time),
                'distance' => $obj['distance'],
            ];

        }

        RouteService::loop();

        return response()->json($result);
    }
}
