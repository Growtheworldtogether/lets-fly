<?php

namespace App\Support;

/** Hex colour maths: contrast ratios (WCAG) and the shades the site derives from the brand colour. */
class Color
{
    public static function valid(string $hex): bool
    {
        return (bool) preg_match('/^#[0-9a-fA-F]{6}$/', $hex);
    }

    /** @return array{0:int,1:int,2:int} */
    public static function rgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }

    public static function luminance(string $hex): float
    {
        $c = array_map(function ($v) {
            $v /= 255;
            return $v <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;
        }, self::rgb($hex));
        return 0.2126 * $c[0] + 0.7152 * $c[1] + 0.0722 * $c[2];
    }

    public static function contrast(string $a, string $b): float
    {
        [$l1, $l2] = [self::luminance($a), self::luminance($b)];
        if ($l1 < $l2) { [$l1, $l2] = [$l2, $l1]; }
        return ($l1 + 0.05) / ($l2 + 0.05);
    }

    /** @return array{0:float,1:float,2:float} hue 0-360, saturation and lightness 0-1 */
    public static function hsl(string $hex): array
    {
        [$r, $g, $b] = array_map(fn ($v) => $v / 255, self::rgb($hex));
        $max = max($r, $g, $b); $min = min($r, $g, $b); $l = ($max + $min) / 2;
        if ($max === $min) { return [0.0, 0.0, $l]; }
        $d = $max - $min;
        $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
        $h = match ($max) { $r => ($g - $b) / $d + ($g < $b ? 6 : 0), $g => ($b - $r) / $d + 2, default => ($r - $g) / $d + 4 };
        return [$h * 60, $s, $l];
    }

    public static function fromHsl(float $h, float $s, float $l): string
    {
        $c = (1 - abs(2 * $l - 1)) * $s; $x = $c * (1 - abs(fmod($h / 60, 2) - 1)); $m = $l - $c / 2;
        [$r, $g, $b] = match (true) { $h < 60 => [$c, $x, 0], $h < 120 => [$x, $c, 0], $h < 180 => [0, $c, $x], $h < 240 => [0, $x, $c], $h < 300 => [$x, 0, $c], default => [$c, 0, $x] };
        return sprintf('#%02x%02x%02x', round(($r + $m) * 255), round(($g + $m) * 255), round(($b + $m) * 255));
    }

    public static function shade(string $hex, float $lightnessDelta): string
    {
        [$h, $s, $l] = self::hsl($hex);
        return self::fromHsl($h, $s, max(0.02, min(0.95, $l + $lightnessDelta)));
    }

    /** A lighter variant of the brand colour for links, darkened until it reads on white. */
    public static function link(string $brand): string
    {
        $c = self::shade($brand, 0.09);
        for ($i = 0; $i < 12 && self::contrast($c, '#ffffff') < 4.5; $i++) { $c = self::shade($c, -0.03); }
        return $c;
    }
}
