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

    /** Wording corrected to match the vehicle. Applied to stored text too, so an update reaches existing sections without a migration. */
    private const TEXT_FIXES = [
        'You fly it with a single stick and a throttle lever.' => 'You fly it with a yoke and a throttle lever.',
        '- Control: Single stick and throttle' => '- Control: Yoke and throttle',
        '- Controls: Single stick, throttle and touch display' => '- Controls: Yoke, throttle and touch display',
        '- Motors: 9, one per rotor and one for the propeller' => '- Motors: 12, one per lift rotor and one per rear ducted fan',
        '- Propeller: 1, rear' => '- Rear ducted fans: 4',
        '- Length: 6.1 m' => '- Length: 4.9 m',
        '- Height: 2.1 m' => '- Height: 1.2 m',
        '- Folded width: 2.4 m' => '- Folded width: 2.0 m',
    ];

    /** Headings of earlier default texts. A section that still opens with one of them has not been rewritten, so it moves to the current default. */
    private const LEGACY_HEADINGS = [
        'exterior' => ['Shaped by the air, finished by hand'],
        'flight' => ['Vertical takeoff, wing-borne cruise'],
        'avionics' => ['A flight deck that looks after you'],
        'performance' => ['Quick to climb, efficient to cruise'],
        'power' => ['Two batteries, one purpose'],
        'interior' => ['A cabin that feels like a quiet room'],
    ];

    private static function isLegacyDefault(string $slug, ?string $body): bool
    {
        $first = trim((string) strtok(str_replace(["\r\n", "\r"], "\n", trim((string) $body)), "\n"));

        return in_array($first, self::LEGACY_HEADINGS[$slug] ?? [], true);
    }

    /** Creates missing sections and fills placeholder text from config/vehicle.php. Text an admin has written is never touched. */
    public static function ensureDefaults(): void
    {
        foreach (config('vehicle.sections') as $slug => $d) {
            $row = static::firstOrCreate(['slug' => $slug], [
                'title' => $d['title'], 'summary' => $d['summary'], 'sort' => $d['sort'], 'body' => $d['body'],
            ]);
            if (! $row->wasRecentlyCreated && (static::isPlaceholder($row->body) || static::isLegacyDefault($slug, $row->body))) {
                $row->update(['body' => $d['body']]);
            }
            if (! $row->wasRecentlyCreated) {
                $fixed = strtr((string) $row->body, self::TEXT_FIXES);
                if ($fixed !== (string) $row->body) {
                    $row->update(['body' => $fixed]);
                }
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
        $text = str_replace('{vehicle}', $vehicle, (string) $this->body);
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

    public function hasVideo(): bool
    {
        $k = $this->videoKind();

        return $k === 'upload' || $k === 'file' || $this->embedUrl() !== null;
    }
}
