<?php

namespace App\Services;

use App\Enums\GoatOrigin;
use App\Enums\GoatStatus;
use App\Models\Goat;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GoatService
{
    public function __construct(
        private readonly SequenceService $sequences,
        private readonly ActivityLogger $logger,
    ) {
    }

    public function create(array $data, User $user): Goat
    {
        return DB::transaction(function () use ($data, $user) {
            $identity = $this->sequences->goatIdentity();

            $goat = Goat::query()->create([
                'code' => $identity['code'],
                'barcode' => $identity['barcode'],
                'name' => $data['name'],
                'type' => $data['type'],
                'gender' => $data['gender'],
                'birth_date' => $data['birth_date'] ?? null,
                'weight' => $data['weight'] ?? 0,
                'color' => $data['color'] ?? null,
                'origin' => $data['origin'] ?? GoatOrigin::Peranakan->value,
                'mother_id' => $data['mother_id'] ?? null,
                'entry_date' => $data['entry_date'] ?? now()->toDateString(),
                'status' => GoatStatus::Tersedia->value,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->logger->log('create', $goat, 'Menambahkan kambing '.$goat->code, [
                'origin' => $goat->origin->value,
            ]);

            return $goat;
        });
    }

    public function update(Goat $goat, array $data, User $user): Goat
    {
        if ($goat->isSold()) {
            $allowed = ['weight', 'color', 'notes'];
            $data = array_intersect_key($data, array_flip($allowed));
        }

        $goat->fill([
            'name' => $data['name'] ?? $goat->name,
            'type' => $data['type'] ?? $goat->type,
            'gender' => $data['gender'] ?? $goat->gender,
            'birth_date' => $data['birth_date'] ?? $goat->birth_date,
            'weight' => $data['weight'] ?? $goat->weight,
            'color' => $data['color'] ?? $goat->color,
            'origin' => $data['origin'] ?? $goat->origin,
            'mother_id' => $data['mother_id'] ?? $goat->mother_id,
            'entry_date' => $data['entry_date'] ?? $goat->entry_date,
            'notes' => $data['notes'] ?? $goat->notes,
        ]);

        $goat->save();

        $this->logger->log('update', $goat, 'Memperbarui data kambing '.$goat->code);

        return $goat;
    }

    public function archive(Goat $goat, User $user, ?string $reason = null): void
    {
        if ($goat->sales()->whereNull('voided_at')->exists()) {
            throw ValidationException::withMessages([
                'goat' => 'Kambing masih memiliki penjualan aktif. Void penjualan terlebih dahulu.',
            ]);
        }

        if ($goat->children()->exists()) {
            throw ValidationException::withMessages([
                'goat' => 'Kambing masih tercatat sebagai induk. Ubah induk anak terlebih dahulu.',
            ]);
        }

        DB::transaction(function () use ($goat, $user, $reason) {
            $this->logger->log('archive', $goat, 'Mengarsipkan kambing '.$goat->code, [
                'reason' => $reason,
            ]);

            $goat->delete();
        });
    }
}
