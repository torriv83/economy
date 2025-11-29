<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * @property int $id
 * @property string $key
 * @property string|null $value
 * @property string $type
 * @property string $group
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property mixed $typed_value
 */
class Setting extends Model
{
    /** @use HasFactory<\Database\Factories\SettingFactory> */
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
    ];

    /**
     * Get the value cast to its proper type.
     */
    protected function typedValue(): Attribute
    {
        return Attribute::make(
            get: function (): mixed {
                if ($this->value === null) {
                    return null;
                }

                return match ($this->type) {
                    'integer' => (int) $this->value,
                    'float' => (float) $this->value,
                    'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
                    'encrypted' => Crypt::decryptString($this->value),
                    default => $this->value,
                };
            },
            set: function (mixed $value): array {
                if ($value === null) {
                    return ['value' => null];
                }

                $storedValue = match ($this->type) {
                    'encrypted' => Crypt::encryptString((string) $value),
                    'boolean' => $value ? '1' : '0',
                    default => (string) $value,
                };

                return ['value' => $storedValue];
            },
        );
    }
}
