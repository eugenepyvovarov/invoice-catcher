<?php

namespace App\Services\Gmail;

use Google\Service\Gmail\Message;

/**
 * Lightweight wrapper around a fully loaded Gmail API message.
 */
class GmailMessage
{
    protected array $headers = [];

    protected ?string $htmlBody = null;

    protected ?string $textBody = null;

    /** @var array<int, array{id: string, filename: string, mimeType: string, size: int, data: string}> */
    protected array $attachments = [];

    public function __construct(
        protected Message $message
    ) {
        $this->parsePayload($message->getPayload());
        foreach ($message->getPayload()?->getHeaders() ?? [] as $header) {
            $this->headers[strtolower($header->getName())] = $header->getValue();
        }
    }

    public function getId(): string
    {
        return $this->message->getId();
    }

    public function getInternalDate(): ?int
    {
        $value = $this->message->getInternalDate();

        return $value !== null ? (int) $value : null;
    }

    public function getDate(): ?\Carbon\Carbon
    {
        if ($date = $this->header('date')) {
            try {
                return \Carbon\Carbon::parse($date);
            } catch (\Throwable) {
                // fall through
            }
        }

        if ($internal = $this->getInternalDate()) {
            return \Carbon\Carbon::createFromTimestampMs($internal);
        }

        return null;
    }

    public function getLabels(): array
    {
        return $this->message->getLabelIds() ?? [];
    }

    public function getSubject(): ?string
    {
        return $this->header('subject');
    }

    public function getFromName(): ?string
    {
        $from = $this->header('from');
        if (! $from) {
            return null;
        }

        if (preg_match('/^(.+?)\s*<.+>$/', $from, $m)) {
            return trim($m[1], " \t\"'");
        }

        return $from;
    }

    public function getFromEmail(): ?string
    {
        $from = $this->header('from');
        if (! $from) {
            return null;
        }

        if (preg_match('/<([^>]+)>/', $from, $m)) {
            return $m[1];
        }

        if (filter_var($from, FILTER_VALIDATE_EMAIL)) {
            return $from;
        }

        return $from;
    }

    public function getTo(): array
    {
        $to = $this->header('to');
        if (! $to) {
            return [];
        }

        return array_values(array_filter(array_map('trim', preg_split('/,/', $to) ?: [])));
    }

    public function getDeliveredTo(): ?string
    {
        return $this->header('delivered-to');
    }

    public function getHtmlBody(): ?string
    {
        return $this->htmlBody ?: $this->textBody;
    }

    public function hasAttachments(): bool
    {
        return count($this->attachments) > 0;
    }

    /**
     * @return array<int, array{id: string, filename: string, mimeType: string, size: int, data: string}>
     */
    public function getAttachments(): array
    {
        return $this->attachments;
    }

    protected function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    protected function parsePayload($payload, string $fallbackFilename = 'attachment'): void
    {
        if (! $payload) {
            return;
        }

        $mimeType = $payload->getMimeType() ?? '';
        $filename = $payload->getFilename() ?: $fallbackFilename;
        $body = $payload->getBody();
        $data = $body?->getData();
        $attachmentId = $body?->getAttachmentId();

        if ($filename && $filename !== 'attachment' && ($data || $attachmentId)) {
            $decoded = $data ? $this->decodeBody($data) : '';
            $this->attachments[] = [
                'id' => $attachmentId ?: (string) count($this->attachments),
                'filename' => $filename,
                'mimeType' => $mimeType,
                'size' => (int) ($body?->getSize() ?? strlen($decoded)),
                'data' => $decoded,
                'attachmentId' => $attachmentId,
            ];
        } elseif ($data && str_contains($mimeType, 'text/html') && $this->htmlBody === null) {
            $this->htmlBody = $this->decodeBody($data);
        } elseif ($data && str_contains($mimeType, 'text/plain') && $this->textBody === null) {
            $this->textBody = nl2br(e($this->decodeBody($data)));
        }

        foreach ($payload->getParts() ?? [] as $index => $part) {
            $this->parsePayload($part, 'attachment-'.$index);
        }
    }

    protected function decodeBody(string $data): string
    {
        $raw = strtr($data, '-_', '+/');

        return base64_decode($raw) ?: '';
    }
}
