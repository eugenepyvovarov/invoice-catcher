<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function gmails(): HasMany
    {
        return $this->hasMany(Gmail::class);
    }

    public static function makeDefault(GmailFilter $filter): void
    {
        $filter->update(['is_default' => true]);
        self::where('user_id', $filter->user_id)
            ->where('id', '!=', $filter->id)
            ->update(['is_default' => false]);
    }
}
