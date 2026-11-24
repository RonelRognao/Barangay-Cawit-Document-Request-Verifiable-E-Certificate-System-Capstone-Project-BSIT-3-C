<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class KycStorageService
{
    public function storeValidId(UploadedFile $file, ?string $oldPath = null): string
    {
        return $this->replaceFile($file, 'kyc_ids', $oldPath);
    }

    public function storeProofOfResidency(UploadedFile $file, ?string $oldPath = null): string
    {
        return $this->replaceFile($file, 'kyc_proofs', $oldPath);
    }

    public function getPrivateFilePath(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('local');

        return $disk->exists($path) ? $disk->path($path) : null;
    }

    private function replaceFile(UploadedFile $file, string $directory, ?string $oldPath): string
    {
        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('local');
        $newPath = $file->store($directory, 'local');

        if ($oldPath && $disk->exists($oldPath)) {
            $disk->delete($oldPath);
        }

        return $newPath;
    }
}