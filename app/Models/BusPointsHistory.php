<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusPointsHistory extends Model
{
    use HasFactory;
    protected $fillable = [
        'bus_id',
        'latitude',
        'longitude',
    ];

    public function bus()
    {
        return $this->belongsTo(Bus::class);
    }
}
