<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayoffSetting extends Model
{
    /** @use HasFactory<\Database\Factories\PayoffSettingFactory> */
    use HasFactory;

    protected $fillable = [
        'extra_payment',
        'strategy',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'extra_payment' => 'float',
        ];
    }
}
