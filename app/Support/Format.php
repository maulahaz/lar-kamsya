<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class Format
{
    public const MONTHS = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    public const MONTHS_SHORT = [
        1 => 'Jan',
        2 => 'Feb',
        3 => 'Mar',
        4 => 'Apr',
        5 => 'Mei',
        6 => 'Jun',
        7 => 'Jul',
        8 => 'Agu',
        9 => 'Sep',
        10 => 'Okt',
        11 => 'Nov',
        12 => 'Des',
    ];

    public static function rupiah(float|int|string|null $value): string
    {
        $number = (float) ($value ?? 0);
        $sign = $number < 0 ? '-' : '';

        return $sign.'Rp'.number_format(abs($number), 0, ',', '.');
    }

    public static function rupiahShort(float|int|string|null $value): string
    {
        $number = (float) ($value ?? 0);
        $sign = $number < 0 ? '-' : '';
        $abs = abs($number);

        if ($abs >= 1_000_000_000) {
            return $sign.'Rp'.number_format($abs / 1_000_000_000, 2, ',', '.').' M';
        }

        if ($abs >= 1_000_000) {
            return $sign.'Rp'.number_format($abs / 1_000_000, 1, ',', '.').' jt';
        }

        if ($abs >= 1_000) {
            return $sign.'Rp'.number_format($abs / 1_000, 0, ',', '.').' rb';
        }

        return $sign.'Rp'.number_format($abs, 0, ',', '.');
    }

    public static function number(float|int|string|null $value, int $decimals = 0): string
    {
        return number_format((float) ($value ?? 0), $decimals, ',', '.');
    }

    public static function weight(float|int|string|null $value): string
    {
        $number = (float) ($value ?? 0);
        $decimals = floor($number) === $number ? 0 : 1;

        return self::number($number, $decimals).' kg';
    }

    public static function date(CarbonInterface|string|null $value, bool $short = false): string
    {
        if (empty($value)) {
            return '-';
        }

        $date = $value instanceof CarbonInterface ? $value : Carbon::parse($value);

        return $date->day.' '.($short ? self::MONTHS_SHORT[$date->month] : self::MONTHS[$date->month]).' '.$date->year;
    }

    public static function dateTime(CarbonInterface|string|null $value): string
    {
        if (empty($value)) {
            return '-';
        }

        $date = $value instanceof CarbonInterface ? $value : Carbon::parse($value);

        return self::date($date).', '.$date->format('H:i');
    }

    public static function monthLabel(CarbonInterface|string $value): string
    {
        $date = $value instanceof CarbonInterface ? $value : Carbon::parse($value);

        return self::MONTHS_SHORT[$date->month].' '.substr((string) $date->year, -2);
    }

    public static function todayLong(): string
    {
        $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $now = Carbon::now();

        return $days[$now->dayOfWeek].', '.self::date($now);
    }

    public static function greeting(): string
    {
        $hour = (int) Carbon::now()->format('H');

        if ($hour < 11) {
            return 'Selamat pagi';
        }

        if ($hour < 15) {
            return 'Selamat siang';
        }

        if ($hour < 18) {
            return 'Selamat sore';
        }

        return 'Selamat malam';
    }

    public static function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];

        return collect($parts)
            ->take(2)
            ->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
    }

    public static function typeTone(?string $type): string
    {
        return match (mb_strtolower((string) $type)) {
            'boer' => 'amber',
            'boer cross' => 'rose',
            'etawa' => 'blue',
            'jawa' => 'purple',
            'kacang' => 'green',
            default => 'slate',
        };
    }
}
