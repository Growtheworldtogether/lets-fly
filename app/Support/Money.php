<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;

/**
 * Immutable money value in integer cents. Never uses floats for amounts.
 * Single currency (USD) for the whole credit union.
 */
final class Money
{
    private function __construct(public readonly int $cents)
    {
    }

    public static function cents(int $cents): self
    {
        return new self($cents);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    /**
     * Parse a decimal string such as "1234.5", "$1,234.50" or "-0.07" without floats.
     */
    public static function parse(string $input): self
    {
        $clean = str_replace([',', '$', ' '], '', trim($input));
        if (!preg_match('/^(-?)(\d+)(?:\.(\d{1,2}))?$/', $clean, $m)) {
            throw new InvalidArgumentException("Invalid money amount: {$input}");
        }
        $fraction = str_pad($m[3] ?? '', 2, '0');
        $cents = ((int) $m[2]) * 100 + (int) $fraction;

        return new self($m[1] === '-' ? -$cents : $cents);
    }

    public function add(self $other): self
    {
        return new self($this->cents + $other->cents);
    }

    public function subtract(self $other): self
    {
        return new self($this->cents - $other->cents);
    }

    public function negate(): self
    {
        return new self(-$this->cents);
    }

    public function isZero(): bool
    {
        return $this->cents === 0;
    }

    public function isNegative(): bool
    {
        return $this->cents < 0;
    }

    public function isPositive(): bool
    {
        return $this->cents > 0;
    }

    public function equals(self $other): bool
    {
        return $this->cents === $other->cents;
    }

    public function compare(self $other): int
    {
        return $this->cents <=> $other->cents;
    }

    public function greaterThan(self $other): bool
    {
        return $this->cents > $other->cents;
    }

    public function lessThan(self $other): bool
    {
        return $this->cents < $other->cents;
    }

    /**
     * Split into parts by integer ratios. The remainder is handed out one cent at a time
     * from the first part, so the parts always add up to the original amount.
     *
     * @param  list<int>  $ratios
     * @return list<self>
     */
    public function allocate(array $ratios): array
    {
        $total = array_sum($ratios);
        if ($total <= 0 || $this->cents < 0) {
            throw new InvalidArgumentException('Ratios must sum to a positive number and amount must not be negative.');
        }

        $parts = [];
        $given = 0;
        foreach ($ratios as $ratio) {
            $share = intdiv($this->cents * $ratio, $total);
            $parts[] = $share;
            $given += $share;
        }

        $remainder = $this->cents - $given;
        for ($i = 0; $remainder > 0; $i = ($i + 1) % count($parts), $remainder--) {
            $parts[$i]++;
        }

        return array_map(fn (int $c) => new self($c), $parts);
    }

    /** Format as "$1,234.56" or "-$1,234.56". */
    public function format(): string
    {
        $abs = abs($this->cents);
        $text = '$' . number_format(intdiv($abs, 100)) . '.' . str_pad((string) ($abs % 100), 2, '0', STR_PAD_LEFT);

        return $this->cents < 0 ? '-' . $text : $text;
    }

    public function __toString(): string
    {
        return $this->format();
    }
}
