<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Road extends Model
{
    protected $fillable = [
        'road_number',
    ];

    public function points()
    {
        return $this->hasMany(RoadPoint::class, 'road_id');
    }
    public function busStops()
    {
        return $this->belongsToMany(BusStop::class, 'road_busstops')->withPivot('status')
            ->withTimestamps();
    }
    public function buses()
    {
        return $this->belongsToMany(Bus::class, 'buses_roads');
    }

}
