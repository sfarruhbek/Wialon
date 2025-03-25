<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusStop extends Model
{
    use HasFactory;
    protected $fillable = ['latitude', 'longitude'];


    public function roads()
    {
        return $this->belongsToMany(Road::class, 'road_busstops');
    }
}
