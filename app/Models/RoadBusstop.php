<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoadBusstop extends Model
{
    use HasFactory;
    protected $fillable = ['road_id', 'bus_stop_id', 'status'];

    public function road()
    {
        return $this->belongsTo(Road::class);
    }

    public function busStop()
    {
        return $this->belongsTo(BusStop::class);
    }
}
