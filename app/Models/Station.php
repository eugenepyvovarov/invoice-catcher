<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Station extends Model
{
    use HasFactory;

    protected $fillable = [
        'signature',
        'name',
        'short_name',
        'synonyms',
    ];

    protected $casts = [
        'synonyms' => 'json'
    ];

    public function scopeNameOrSynonym($query, $str)
    {
        return $query->where('name', $str)
            ->orWhere('short_name', $str)
            ->orWhereJsonContains('synonyms', $str);
    }
}
