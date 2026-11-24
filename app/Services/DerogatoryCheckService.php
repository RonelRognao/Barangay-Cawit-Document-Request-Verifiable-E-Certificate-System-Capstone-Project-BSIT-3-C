<?php

namespace App\Services;

use App\Models\BlotterRecord;
use App\Models\User;
use Illuminate\Support\Collection;

class DerogatoryCheckService
{
    /**
     * @return array{has_derogatory: bool, active_cases: Collection<int, BlotterRecord>, settled_cases: Collection<int, BlotterRecord>}
     */
    public function checkResident(User $resident): array
    {
        $normalizedName = strtolower(trim($resident->full_name));

        $matches = BlotterRecord::query()
            ->where(function ($q) use ($resident, $normalizedName) {
                $q->where('respondent_user_id', $resident->id)
                    ->orWhereRaw('LOWER(TRIM(respondent_name)) = ?', [$normalizedName]);
            })
            ->orderByDesc('incident_date')
            ->get();

        $activeCases = $matches->filter(fn (BlotterRecord $record) => $record->isDerogatory())->values();
        $settledCases = $matches->reject(fn (BlotterRecord $record) => $record->isDerogatory())->values();

        return [
            'has_derogatory' => $activeCases->isNotEmpty(),
            'active_cases' => $activeCases,
            'settled_cases' => $settledCases,
        ];
    }
}