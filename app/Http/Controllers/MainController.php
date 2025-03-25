<?php

namespace App\Http\Controllers;

use App\Models\Bus;
use App\Models\BusPointsHistory;
use App\Models\BusStop;
use App\Models\RoadBusstop;
use App\Models\RoadPoint;
use App\Models\Road;
use App\Services\RouteService;
use App\Services\WialonService;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class MainController extends Controller
{
    public function main(){
        return view('main');
    }

    public function save_road(Request $request){
        $data = $request->all()['buses'];

        foreach ($data as $value) {
            Bus::create([
                'bus_wialon_id' => $value['bus_wialon_id'],
                'bus_number' => $value['name'],
            ]);
        }
        return response()->json($data);
    }


    public function index()
    {


        $busstops = BusStop::all();
        $roads = Road::with('points')->with('busStops')->get();



//        $routeCoords = [];
//
//
//        foreach ($roads[0]->points as $points) {
//            $routeCoords[] = ['latitude' => $points->latitude, 'longitude' => $points->longitude];
//        }
//
//        $station = ['latitude' => 41.55301720860653, 'longitude' => 60.630931954526886];
//        $busPosition = ['latitude' => 41.5525, 'longitude' => 60.63083];
//
//        $stationOnRoute = RouteService::findClosestPointOnRoute($routeCoords, $station);
//        $busPositionOnRoute = RouteService::findClosestPointOnRoute($routeCoords, $busPosition);
//
//        $busDistanceToStationClosest = RouteService::getDistanceBetweenPoints($routeCoords, $routeCoords[0], $routeCoords[count($routeCoords)-1]);
//
//        $data = [
//            'station_on_route' => $stationOnRoute,
//            'bus_position_on_route' => $busPositionOnRoute,
//            'bus_distance_to_station' => round($busDistanceToStationClosest, 2)
//        ];
//
//        dd($data, $roads[0]->points[0]);

        return view('admin.index', ["roads" => $roads, 'busstops' => $busstops]);
    }

    public function saveRoute(Request $request)
    {

//        $road_id = 2;
        $points = $request->all()['points'];
//
//        foreach ($points as $point) {
//            RoadPoint::create([
//                'road_id' => $road_id,
//                'latitude' => $point['0'],
//                'longitude' => $point['1']
//            ]);
//        }

        return response()->json(['message' => $points]);
    }
    public function roadView(){
        $roads = Road::with('points')->get();

        return view('admin.roadView', ["roads" => $roads]);
    }
    public function busstop()
    {
        $busstops = BusStop::all();
        $roads = Road::with('points')->get();
        return view('admin.busstop', ["roads" => $roads, 'busstops' => $busstops]);
    }

    public function saveBusStop(Request $request)
    {
        $data = $request->validate([
            'busStops' => 'required|array',
            'busStops.*' => 'array|min:2|max:2'
        ]);


        foreach ($data['busStops'] as $busStop) {
            BusStop::create([
                'latitude' => $busStop['latitude'],
                'longitude' => $busStop['longitude'],
            ]);
        }
        return response()->json(['message' => "success"]);
    }
    public function updateBusStop(Request $request)
    {
        $busStops = $request->input('busStops');

        foreach ($busStops as $stop) {
            BusStop::where('id', $stop['id'])->update([
                'latitude' => $stop['latitude'],
                'longitude' => $stop['longitude']
            ]);
        }

        return response()->json(['message' => 'Bekatlar yangilandi']);
    }


    public function rbs(){
        $busstops = BusStop::all();
        $roads = Road::with('points')->get();
        return view('admin.rbs',["roads" => $roads, 'busstops' => $busstops]);
    }

    public function saveRBS(Request $request)
    {
        $data = $request->all()['data'];

        foreach ($data as $value) {
            RoadBusstop::create([
                'road_id' => $value['road_id'],
                'bus_stop_id' => $value['bus_stop_id'],
            ]);
        }
        return response()->json(['message' => $data]);
    }

    public function updateRBS(Request $request){
        //
    }

}
