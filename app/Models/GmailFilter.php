<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GmailFilter extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'user_id',
        'gmail_profile_id',
        'filter',
        'regex',
        'state',
    ];

    public function gmails()
    {
        return $this->hasMany(Gmail::class);
    }
}