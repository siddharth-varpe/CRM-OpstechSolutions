<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BusinessSequence extends Model
{
    protected $primaryKey = 'name';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'prefix',
        'current_val',
        'padding',
        'year_reset',
        'current_year',
    ];

    /**
     * Atomically generate next sequence number.
     * E.g. generate('INVOICE') -> "INV-2026-0001"
     */
    public static function nextNumber(string $name, ?string $customPrefix = null, int $padding = 4): string
    {
        return DB::transaction(function () use ($name, $customPrefix, $padding) {
            $aliasMap = [
                'JOBCARD' => 'JOB_CARD',
            ];
            $seqName = $aliasMap[strtoupper($name)] ?? strtoupper($name);

            $defaultPrefixes = [
                'CUSTOMER' => 'CUST',
                'INVOICE' => 'INV',
                'SALE' => 'SALE',
                'SERVICE' => 'SRV',
                'JOB_CARD' => 'JC',
                'PAYMENT' => 'PAY',
                'WARRANTY' => 'WAR',
                'ASSET' => 'ASSET',
                'INQUIRY' => 'INQ',
                'RENTAL' => 'RNT',
                'REMINDER' => 'REM',
            ];

            $currentYear = (int) date('Y');

            $seq = self::lockForUpdate()->find($seqName);

            if (!$seq) {
                $prefix = $customPrefix ?? ($defaultPrefixes[$seqName] ?? $seqName);
                $seq = self::create([
                    'name' => $seqName,
                    'prefix' => $prefix,
                    'current_val' => 1,
                    'padding' => $padding,
                    'year_reset' => true,
                    'current_year' => $currentYear,
                ]);
                $counter = 1;
            } else {
                if ($seq->year_reset && $seq->current_year < $currentYear) {
                    $seq->current_val = 1;
                    $seq->current_year = $currentYear;
                } else {
                    $seq->current_val += 1;
                }
                if ($customPrefix !== null) {
                    $seq->prefix = $customPrefix;
                }
                $seq->save();
                $counter = $seq->current_val;
            }

            $padLen = $seq->padding > 0 ? $seq->padding : 4;
            $padded = str_pad((string) $counter, $padLen, '0', STR_PAD_LEFT);
            return "{$seq->prefix}-{$currentYear}-{$padded}";
        });
    }
}
