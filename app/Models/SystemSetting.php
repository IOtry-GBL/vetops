<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class SystemSetting extends Model
{
    use HasFactory;

    protected $primaryKey = 'setting_id';

    protected $fillable = [
        'setting_key',
        'setting_value',
        'value_type',
        'updated_by',
    ];

    protected $casts = [
        'setting_value' => 'string',
    ];

    public static function getValue(string $key, mixed $default = null): mixed
    {
        if (!Schema::hasTable((new static())->getTable())) {
            return $default;
        }

        $record = static::query()->where('setting_key', $key)->first();

        if (!$record) {
            return $default;
        }

        return match ($record->value_type) {
            'boolean' => filter_var($record->setting_value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $record->setting_value,
            'json' => json_decode($record->setting_value, true),
            default => $record->setting_value,
        };
    }

    public static function setValue(string $key, mixed $value, string $type = 'string', ?int $updatedBy = null): self
    {
        if (!Schema::hasTable((new static())->getTable())) {
            return new static([
                'setting_key' => $key,
                'setting_value' => (string) $value,
                'value_type' => $type,
                'updated_by' => $updatedBy,
            ]);
        }

        $payloadValue = match ($type) {
            'boolean' => $value ? '1' : '0',
            'integer' => (string) ((int) $value),
            'json' => json_encode($value, JSON_UNESCAPED_UNICODE),
            default => (string) $value,
        };

        return static::query()->updateOrCreate(
            ['setting_key' => $key],
            [
                'setting_value' => $payloadValue,
                'value_type' => $type,
                'updated_by' => $updatedBy,
            ]
        );
    }
}
