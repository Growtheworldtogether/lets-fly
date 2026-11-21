<?php

namespace App\Services;

/** Looks inside an MP4 for the two things that stop a video playing on phones: HEVC (H.265) video, and the index at the end of the file. */
final class Mp4Check
{
    /** @return list<string> plain-language problems; empty when the file looks fine (or is not an MP4) */
    public static function issues(string $path): array
    {
        return self::inspect($path)['issues'];
    }

    /** @return array{issues: list<string>, summary: ?string} summary = what is in the file, in one line */
    public static function inspect(string $path): array
    {
        $none = ['issues' => [], 'summary' => null];
        $f = @fopen($path, 'rb');
        if (! $f) {
            return $none;
        }
        try {
            $size = filesize($path);
            $pos = 0;
            $moovAt = $mdatAt = null;
            $moov = '';
            for ($n = 0; $n < 200 && $pos + 8 <= $size; $n++) {
                fseek($f, $pos);
                $head = (string) fread($f, 16);
                if (strlen($head) < 8) {
                    break;
                }
                $len = unpack('N', substr($head, 0, 4))[1];
                $type = substr($head, 4, 4);
                if ($len === 1 && strlen($head) === 16) {
                    $len = unpack('J', substr($head, 8, 8))[1];
                } elseif ($len === 0) {
                    $len = $size - $pos;
                }
                if ($len < 8 || ! preg_match('/^[\x20-\x7e]{4}$/', $type)) {
                    break;
                }
                if ($type === 'moov' && $moovAt === null) {
                    $moovAt = $pos;
                    fseek($f, $pos);
                    $moov = (string) fread($f, min($len, 8 * 1024 * 1024));
                } elseif ($type === 'mdat' && $mdatAt === null) {
                    $mdatAt = $pos;
                }
                $pos += $len;
            }
        } finally {
            fclose($f);
        }
        if ($moovAt === null) {
            return $none;
        }

        $out = [];
        $hevc = str_contains($moov, 'hvc1') || str_contains($moov, 'hev1');
        $h264 = str_contains($moov, 'avc1');
        $video = $hevc && ! $h264 ? 'H.265 (HEVC)' : ($h264 ? 'H.264' : (str_contains($moov, 'av01') ? 'AV1' : (str_contains($moov, 'vp09') ? 'VP9' : 'unknown video')));
        if ($hevc && ! $h264) {
            $out[] = 'This video uses H.265 (HEVC). Many phones and browsers cannot play it. Re-export it as H.264 (in HandBrake: Video Encoder "H.264").';
        } elseif ((str_contains($moov, 'av01') || str_contains($moov, 'vp09')) && ! $h264) {
            $out[] = 'This video uses '.$video.', which many phones cannot play. Re-export it as H.264.';
        }

        // H.264 profile and level, from the avcC box: iPhones play Baseline, Main and High, not the 10-bit or 4:4:4 kinds.
        $size = '';
        if ($h264 && ($a = strpos($moov, 'avc1')) !== false) {
            if (strlen($moov) >= $a + 32) {
                $size = unpack('n', substr($moov, $a + 28, 2))[1].'×'.unpack('n', substr($moov, $a + 30, 2))[1];
            }
            if (($c = strpos($moov, 'avcC', $a)) !== false && strlen($moov) >= $c + 8) {
                $profile = ord($moov[$c + 5]);
                $level = ord($moov[$c + 7]);
                $names = [66 => 'Baseline', 77 => 'Main', 88 => 'Extended', 100 => 'High', 110 => 'High 10-bit', 122 => 'High 4:2:2', 244 => 'High 4:4:4'];
                $video .= ' '.($names[$profile] ?? 'profile '.$profile).', level '.number_format($level / 10, 1);
                if ($profile >= 110) {
                    $out[] = 'This H.264 video is the "'.($names[$profile] ?? $profile).'" kind, which iPhones cannot play (desktop browsers often can). Re-export it as ordinary H.264 (8-bit, High profile) in HandBrake.';
                }
            }
        }

        $audio = str_contains($moov, 'mp4a') ? 'AAC audio' : (str_contains($moov, 'ac-3') || str_contains($moov, 'ec-3') ? 'Dolby audio' : (str_contains($moov, 'Opus') ? 'Opus audio' : (str_contains($moov, 'alac') ? 'ALAC audio' : (str_contains($moov, 'smhd') ? 'unknown audio' : 'no audio'))));
        if (in_array($audio, ['Dolby audio', 'Opus audio'], true)) {
            $out[] = 'The sound is '.$audio.', which iPhones may refuse to play. Re-export it with AAC audio.';
        }

        $faststart = ! ($mdatAt !== null && $moovAt > $mdatAt);
        if (! $faststart) {
            $out[] = 'This video is not set up for streaming: a phone has to download the whole file before it can start playing. Re-export it with "Web optimized" ticked (HandBrake) or "fast start" (other tools).';
        }

        return ['issues' => $out, 'summary' => trim($video.($size ? ', '.$size : '').', '.$audio.', '.($faststart ? 'ready for streaming' : 'not ready for streaming'))];
    }
}
