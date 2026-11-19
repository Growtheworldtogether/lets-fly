<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageSection extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    /** slug => [title, summary]. Facts about the vehicle are bracketed until an admin writes them. */
    public const DEFAULTS = [
        'showcase' => ['[Vehicle Name]', 'Secure your place among the pioneers of personal flight.'],
        'exterior' => ['Exterior and design', 'The shape, the materials and the details you see first.'],
        'flight' => ['Flight and handling', 'How it takes off, cruises and lands.'],
        'performance' => ['Performance', 'Speed, climb and the numbers behind them.'],
        'power' => ['Power and range', 'What drives it, how far it goes and how it recharges.'],
        'interior' => ['Interior and comfort', 'The cabin, the seats and the controls.'],
        'safety' => ['Safety systems', 'The layers of protection built into every flight.'],
        'training' => ['Pilot training', 'What you will learn and how long it takes.'],
        'delivery' => ['Delivery and ownership', 'From your allocation to the day it arrives, and what ownership includes.'],
        'specs' => ['Specifications', 'Dimensions, weights and capabilities in full.'],
    ];

    public static function ensureDefaults(): void
    {
        if (static::count() >= count(self::DEFAULTS)) {
            return;
        }
        $i = 0;
        foreach (self::DEFAULTS as $slug => [$title, $summary]) {
            static::firstOrCreate(['slug' => $slug], [
                'title' => $title, 'summary' => $summary, 'sort' => $i * 10,
                'body' => $slug === 'showcase' ? null : "[Describe this section here: Admin, Vehicle pages.]",
            ]);
            $i++;
        }
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'image_id');
    }

    public function poster(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'poster_id');
    }

    /** upload | youtube | vimeo | file | null */
    public function videoKind(): ?string
    {
        if ($this->video_path) {
            return 'upload';
        }
        $host = $this->video_url ? strtolower((string) parse_url($this->video_url, PHP_URL_HOST)) : '';
        if (! $host) {
            return null;
        }
        if (in_array($host, ['youtube.com', 'www.youtube.com', 'youtu.be', 'm.youtube.com'], true)) {
            return 'youtube';
        }
        if (in_array($host, ['vimeo.com', 'www.vimeo.com', 'player.vimeo.com'], true)) {
            return 'vimeo';
        }

        return 'file';
    }

    public function embedUrl(): ?string
    {
        $u = (string) $this->video_url;
        if ($this->videoKind() === 'youtube' && preg_match('~(?:v=|youtu\.be/|embed/)([\w-]{11})~', $u, $m)) {
            return 'https://www.youtube-nocookie.com/embed/'.$m[1];
        }
        if ($this->videoKind() === 'vimeo' && preg_match('~vimeo\.com/(?:video/)?(\d+)~', $u, $m)) {
            return 'https://player.vimeo.com/video/'.$m[1];
        }

        return null;
    }

    public function hasVideo(): bool
    {
        $k = $this->videoKind();

        return $k === 'upload' || $k === 'file' || $this->embedUrl() !== null;
    }
}
