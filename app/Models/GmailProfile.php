<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class GmailProfile extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'email',
        'state',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function gmailFilters()
    {
        return $this->hasMany(GmailFilter::class);
    }

    protected static function boot() {
        parent::boot();

        static::deleting(function($profile) {
            Storage::delete('gmail/tokens/'.config('gmail.credentials_file_name').'-'.$profile->id.'.json');
        });
    }

}
