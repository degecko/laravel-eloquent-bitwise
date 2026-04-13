<?php

namespace DeGecko\Bitwise;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class BitwiseCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): BitwiseCollection
    {
        return static::cast($model, $key, $value ?: 0);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): int
    {
        $casts = $model->bitwiseCasts[$key];

        return BitwiseCollection::make($value)
            ->map(fn ($flag) => $casts[$flag])
            ->reduce(fn ($carry, $item) => $carry | $item, 0);
    }

    public static function cast(Model $model, string $key, int|string $value): BitwiseCollection
    {
        $value = (int) $value;

        return BitwiseCollection::make($model->bitwiseCasts[$key])
            ->filter(fn ($bit) => $value & $bit)
            ->keys()
            ->model($model);
    }
}
