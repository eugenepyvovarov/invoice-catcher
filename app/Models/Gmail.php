<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gmail extends Model
{
    protected $fillable = [
        'user_id',
        'mail_id',
        'gmail_profile_id',
        'gmail_filter_id',
        'labels',
        'from_name',
        'from_email',
        'to',
        'delivered_to',
        'subject',
        'html_body',
        'pdf_body_path',
        'attachments',
        'date'
    ];

    protected $casts = [
        'attachments' => 'array',
        'labels' => 'array',
        'to' => 'array',
        'date' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function gmailFilter()
    {
        return $this->belongsTo(GmailFilter::class);
    }

    public function getAttachmentById($id)
    {
        return collect($this->attachments)->firstWhere('id', $id);
    }

    public function getPdfBodyFullPath()
    {
        return storage_path('app/'.$this->pdf_body_path);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}
