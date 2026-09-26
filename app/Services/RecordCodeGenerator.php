<?php
// app/Services/RecordCodeGenerator.php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class RecordCodeGenerator
{
    /** Generate kode human-readable: RSV-0001, BRW-0001, ISS-0001 */
    public function next(string $prefix, string $table): string
    {
        $next = (int) DB::table($table)->max('id') + 1;

        do {
            $code = sprintf('%s-%04d', $prefix, $next);
            $next++;
        } while (DB::table($table)->where('code', $code)->exists());

        return $code;
    }
}
