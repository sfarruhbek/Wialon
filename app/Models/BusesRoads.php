<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusesRoads extends Model
{
    protected $fillable = [
        'bus_id',
        'road_id',
    ];

    protected $table = 'buses_roads';
}
