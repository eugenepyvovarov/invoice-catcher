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
        'is_default',
        'state',
    ];

    public function gmails()
    {
        return $this->hasMany(Gmail::class);
    }

    /**
     * @param GmailFilter $filter
     */
    public static function makeDefault(GmailFilter $filter)
    {
        $filter->update(['is_default' => true]);
        self::where('user_id', $filter->user_id)
            ->where('id', '!=', $filter->id)
            ->update(['is_default' => false]);
    }
}