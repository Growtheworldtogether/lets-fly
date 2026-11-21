<?php

namespace App\Services;

/** Looks inside an MP4 for the two things that stop a video playing on phones: HEVC (H.265) video, and the index at the end of the file. */
final class Mp4Check
{
    /** @return list<string> plain-language problems; empty when the file looks fine (or is not an MP4) */
    public static function issues(string $path): array
    {
        $f = @fopen($path, 'rb');
        if (! $f) {
            return [];
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
            return [];
        }

        $out = [];
        $hevc = str_contains($moov, 'hvc1') || str_contains($moov, 'hev1');
        if ($hevc && ! str_contains($moov, 'avc1')) {
            $out[] = 'This video uses H.265 (HEVC). Many phones and browsers cannot play it. Re-export it as H.264 (in HandBrake: Video Encoder "H.264").';
        } elseif (str_contains($moov, 'av01') && ! str_contains($moov, 'avc1')) {
            $out[] = 'This video uses AV1, which older phones cannot play. Re-export it as H.264.';
        }
        if ($mdatAt !== null && $moovAt > $mdatAt) {
            $out[] = 'This video is not set up for streaming: a phone has to download the whole file before it can start playing. Re-export it with "Web optimized" ticked (HandBrake) or "fast start" (other tools).';
        }

        return $out;
    }
}
