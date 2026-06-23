<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Gmail extends Model
{
    protected $fillable = [
        'user_id',
        'mail_id',
        'internal_date',
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
        'date',
        'fwd_date',
    ];

    protected function casts(): array
    {
        return [
            'attachments' => 'array',
            'labels' => 'array',
            'to' => 'array',
            'date' => 'datetime',
            'fwd_date' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function gmailFilter(): BelongsTo
    {
        return $this->belongsTo(GmailFilter::class);
    }

    public function getAttachmentById($id)
    {
        return collect($this->attachments)->firstWhere('id', $id);
    }

    public function getCleanSubjectAttribute(): string
    {
        $subject = str_replace('Fwd: ', '', (string) $this->subject);
        $subject = str_replace('/', '_', $subject);

        return $subject;
    }

    public function getPdfBodyFileNameAttribute(): string
    {
        return $this->clean_date_str.'__'.$this->clean_subject.'__['.$this->id.'].pdf';
    }

    public function getCleanDateAttribute()
    {
        return $this->fwd_date ?: $this->date;
    }

    public function getCleanDateStrAttribute(): string
    {
        return $this->clean_date?->format('d.m.Y') ?? '';
    }

    public function getPdfBodyFullPath($storagePath = true): ?string
    {
        if ($this->pdf_body_path) {
            return $storagePath ? storage_path('app/private/'.$this->pdf_body_path) : $this->pdf_body_path;
        }

        return null;
    }

    protected static function boot(): void
    {
        parent::boot();

        static::deleting(function ($gmail) {
            if ($gmail->pdf_body_path) {
                Storage::disk('local')->delete($gmail->pdf_body_path);
            }
            if ($gmail->attachments) {
                foreach ($gmail->attachments as $attachment) {
                    if (! empty($attachment['file_path'])) {
                        Storage::disk('local')->delete($attachment['file_path']);
                    }
                }
            }
        });
    }
}
