<?php

namespace App\Models;

use App\Enums\GoatGender;
use App\Enums\GoatOrigin;
use App\Enums\GoatStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Goat extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'barcode',
        'name',
        'type',
        'gender',
        'birth_date',
        'weight',
        'color',
        'origin',
        'mother_id',
        'entry_date',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'entry_date' => 'date',
            'weight' => 'decimal:2',
            'gender' => GoatGender::class,
            'origin' => GoatOrigin::class,
            'status' => GoatStatus::class,
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    public function purchase()
    {
        return $this->hasOne(Purchase::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function activeSale()
    {
        return $this->hasOne(Sale::class)->whereNull('voided_at');
    }

    public function mother()
    {
        return $this->belongsTo(self::class, 'mother_id')->withTrashed();
    }

    public function children()
    {
        return $this->hasMany(self::class, 'mother_id');
    }

    public function scanLogs()
    {
        return $this->hasMany(ScanLog::class);
    }

    public function acquisitionCost(): float
    {
        return (float) ($this->purchase?->total ?? 0);
    }

    public function supplierName(): string
    {
        return $this->purchase?->supplier?->name ?? '-';
    }

    public function ageText(?CarbonInterface $reference = null): string
    {
        if (! $this->birth_date) {
            return '-';
        }

        $end = $reference ?? now();
        if ($this->birth_date->greaterThan($end)) {
            return '-';
        }

        $months = $this->birth_date->diffInMonths($end);
        $years = intdiv($months, 12);
        $rest = $months % 12;

        if ($years > 0) {
            return $years.' tahun'.($rest ? ' '.$rest.' bulan' : '');
        }

        if ($rest > 0) {
            return $rest.' bulan';
        }

        return $this->birth_date->diffInDays($end).' hari';
    }

    public function isAvailable(): bool
    {
        return $this->status === GoatStatus::Tersedia;
    }

    public function isSold(): bool
    {
        return $this->status === GoatStatus::Terjual;
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', GoatStatus::Tersedia->value);
    }

    public function scopeSearch($query, ?string $term)
    {
        if (! $term) {
            return $query;
        }

        $like = '%'.$term.'%';

        return $query->where(function ($query) use ($like) {
            $query->where('name', 'like', $like)
                ->orWhere('code', 'like', $like)
                ->orWhere('barcode', 'like', $like)
                ->orWhere('type', 'like', $like)
                ->orWhere('color', 'like', $like);
        });
    }
}
