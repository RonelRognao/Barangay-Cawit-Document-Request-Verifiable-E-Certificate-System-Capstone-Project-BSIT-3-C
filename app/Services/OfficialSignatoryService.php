<?php

namespace App\Services;

use App\Models\BarangayOfficial;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class OfficialSignatoryService
{
    public function getActivePrimarySignatory(): ?BarangayOfficial
    {
        return BarangayOfficial::where('is_active', true)
            ->where('is_primary_signatory', true)
            ->orderByDesc('updated_at')
            ->first();
    }

    public function getActiveAttestingSignatory(): ?BarangayOfficial
    {
        return BarangayOfficial::where('is_active', true)
            ->where('is_attest_signatory', true)
            ->orderByDesc('updated_at')
            ->first();
    }

    public function storeSignature(UploadedFile $file, ?string $oldPath = null): string
    {
        $newPath = $file->store('signatures', 'local');

        if ($oldPath && Storage::disk('local')->exists($oldPath)) {
            Storage::disk('local')->delete($oldPath);
        }

        return $newPath;
    }

    public function deleteSignature(?string $path): void
    {
        if ($path && Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }
    }

    public function signatureDataUri(?BarangayOfficial $official): ?string
    {
        if (!$official || empty($official->signature_path)) {
            return null;
        }

    /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('local');
        if (!$disk->exists($official->signature_path)) {
            return null;
        }

        $contents = $disk->get($official->signature_path);
        if ($contents === null) {
            return null;
        }

        $mime = $disk->mimeType($official->signature_path) ?: 'image/png';

        return 'data:' . $mime . ';base64,' . base64_encode($contents);
    }
}