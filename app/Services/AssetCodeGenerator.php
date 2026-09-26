<?php
// app/Services/AssetCodeGenerator.php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetType;
use Illuminate\Support\Str;

class AssetCodeGenerator
{
    public function generate(AssetType $assetType): string
    {
        $prefix = 'LND-' . Str::upper(Str::limit(Str::slug($assetType->category->name, ''), 3, ''));

        $next = Asset::withTrashed() // include soft-deleted agar kode tidak pernah dipakai ulang
            ->where('asset_code', 'like', "{$prefix}-%")
            ->count() + 1;

        do {
            $code = $prefix . '-' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
            $next++;
        } while (Asset::withTrashed()->where('asset_code', $code)->exists());

        return $code;
    }
}