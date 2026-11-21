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

    /** Placeholder bodies written by earlier versions: these get replaced by the detailed defaults. */
    private static function isPlaceholder(?string $body): bool
    {
        return $body === null || trim($body) === '' || str_starts_with(trim($body), '[Describe this section');
    }

    /** SHA-1 of a stored text with line endings normalised and trimmed, as listed in config/vehicle_history.php. */
    private static function fingerprint(?string $body): string
    {
        return sha1(trim(str_replace(["\r\n", "\r"], "\n", (string) $body)));
    }

    /** True when the stored text is exactly one of the earlier defaults, so nobody has rewritten it. */
    private static function isEarlierDefault(string $slug, ?string $body): bool
    {
        return in_array(static::fingerprint($body), config('vehicle_history.'.$slug, []), true);
    }

    /** Creates missing sections and fills placeholder text from config/vehicle.php. Text an admin has written is never touched. */
    public static function ensureDefaults(): void
    {
        foreach (config('vehicle.sections') as $slug => $d) {
            $row = static::firstOrCreate(['slug' => $slug], [
                'title' => $d['title'], 'summary' => $d['summary'], 'sort' => $d['sort'], 'body' => $d['body'],
            ]);
            if (! $row->wasRecentlyCreated && (static::isPlaceholder($row->body) || static::isEarlierDefault($slug, $row->body))) {
                $row->update(['body' => $d['body'], 'summary' => $d['summary']]);
            }
        }
    }

    /**
     * The body split into what the page shows: an optional one-line headline, paragraphs,
     * "- Label: value" facts, and "## Group" tables (the specifications).
     *
     * @return array{headline: ?string, paras: list<string>, facts: list<array{0:string,1:string}>, groups: list<array{0:string,1:list<array{0:string,1:string}>}>}
     */
    public function parts(): array
    {
        $vehicle = brand('vehicle');
        $text = str_replace(['{vehicle}', '{agent}', '{days}'], [$vehicle, \App\Services\Escrow::agent(), (string) \App\Services\Pricing::balanceDays()], (string) $this->body);
        $out = ['headline' => null, 'paras' => [], 'facts' => [], 'groups' => []];
        $group = null;
        foreach (preg_split('/\R\s*\R/', trim($text)) as $i => $block) {
            $lines = array_values(array_filter(array_map('trim', preg_split('/\R/', $block)), 'strlen'));
            $paras = [];
            foreach ($lines as $line) {
                if (str_starts_with($line, '## ')) {
                    $group = count($out['groups']);
                    $out['groups'][] = [trim(substr($line, 3)), []];
                } elseif (preg_match('/^-\s+([^:]+):\s*(.+)$/', $line, $m)) {
                    $row = [trim($m[1]), trim($m[2])];
                    if ($group === null) {
                        $out['facts'][] = $row;
                    } else {
                        $out['groups'][$group][1][] = $row;
                    }
                } else {
                    $paras[] = $line;
                }
            }
            if ($paras) {
                $joined = implode(' ', $paras);
                if ($i === 0 && count($paras) === 1 && ! str_ends_with($joined, '.') && mb_strlen($joined) <= 70) {
                    $out['headline'] = $joined;
                } else {
                    $out['paras'][] = $joined;
                }
            }
        }

        return $out;
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

    /** @return array{issues: list<string>, summary: ?string} what is in an uploaded MP4 and why it may not play on phones */
    public function videoCheck(): array
    {
        $path = $this->video_path ? \Illuminate\Support\Facades\Storage::disk('local')->path($this->video_path) : null;

        return $path && str_ends_with($path, '.mp4') && is_file($path) ? \App\Services\Mp4Check::inspect($path) : ['issues' => [], 'summary' => null];
    }

    /** @return list<string> */
    public function videoIssues(): array
    {
        return $this->videoCheck()['issues'];
    }

    public function hasVideo(): bool
    {
        $k = $this->videoKind();

        return $k === 'upload' || $k === 'file' || $this->embedUrl() !== null;
    }
}
