<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bus extends Model
{
    protected $fillable = [
        'bus_wialon_id',
        'bus_number',
        'status',
    ];

    public function road()
    {
        return $this->hasOneThrough(Road::class, BusesRoads::class, 'bus_id', 'id', 'id', 'road_id');
    }
    public function pointsHistory()
    {
        return $this->hasMany(BusPointsHistory::class);
    }
}
