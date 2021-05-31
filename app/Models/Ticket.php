<?php

namespace App\Models;

use App\Services\TicketService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'gmail_id',
        'person',
        'from_station',
        'to_station',
        'train_number',
        'departure_at',
        'departed_at',
        'arrival_at',
        'arrived_at',
        'departure_checked_at',
        'arrival_checked_at',
        'data',
    ];

    protected $casts = [
        'data' => 'json',
        'departure_at' => 'datetime',
        'departed_at' => 'datetime',
        'arrival_at' => 'datetime',
        'arrived_at' => 'datetime',
        'departure_checked_at' => 'datetime',
        'arrival_checked_at' => 'datetime',
    ];


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function gmail()
    {
        return $this->belongsTo(Gmail::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function stationFrom()
    {
        return $this->belongsTo(Station::class, 'from_station', 'name');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function stationTo()
    {
        return $this->belongsTo(Station::class, 'to_station', 'name');
    }

    /**
     *
     */
    public function getDepartureStatusAttribute()
    {
        return TicketService::getStatus($this->departure_at, $this->departed_at);
    }

    /**
     *
     */
    public function getArrivalStatusAttribute()
    {
        return TicketService::getStatus($this->arrival_at, $this->arrived_at);
    }
}
