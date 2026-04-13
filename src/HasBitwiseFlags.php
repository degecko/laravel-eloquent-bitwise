<?php

namespace DeGecko\Bitwise;

use Illuminate\Database\Eloquent\Builder;

/**
 * Trait for Eloquent models that store flags as bitwise integers.
 *
 * Usage:
 *   1. Add an integer column to your migration: $table->integer('flags')->default(0);
 *   2. Use this trait in your model.
 *   3. Define bitwise casts:
 *      public array $bitwiseCasts = [
 *          'flags' => ['active', 'verified', 'premium'],
 *      ];
 *
 * Then use:
 *   $model->flags->is('verified');
 *   $model->flags->set('premium', 'active');
 *   $model->flags->toggle('active');
 *   $model->flags->remove('verified');
 *   $model->flags->save();
 *
 * Query scopes:
 *   Model::bitwise('flags', 'active', 'verified')->get();
 *   Model::bitwiseNot('flags', 'suspended')->get();
 */
trait HasBitwiseFlags
{
    public function initializeHasBitwiseFlags(): void
    {
        foreach ($this->bitwiseCasts as $column => $flags) {
            $this->mergeCasts([$column => BitwiseCast::class]);

            if (array_is_list($flags)) {
                $this->bitwiseCasts[$column] = array_combine(
                    $flags,
                    array_map(fn ($i) => 1 << $i, array_keys($flags))
                );
            }
        }
    }

    public static function bitwiseCasts(string $column, ?string $flag = null): array|int|null
    {
        $casts = (new static)->bitwiseCasts;

        if ($flag) {
            return $casts[$column][$flag] ?? null;
        }

        return $casts[$column] ?? [];
    }

    public function scopeBitwise(Builder $query, string $column, string ...$flags): void
    {
        $query->where(function (Builder $query) use ($column, $flags) {
            foreach ($flags as $flag) {
                $query->whereRaw(
                    "{$this->getTable()}.{$column} & ?",
                    [static::bitwiseCasts($column, $flag)]
                );
            }
        });
    }

    public function scopeBitwiseNot(Builder $query, string $column, string ...$flags): void
    {
        $query->where(function (Builder $query) use ($column, $flags) {
            foreach ($flags as $flag) {
                $query->whereRaw(
                    "{$this->getTable()}.{$column} & ? = 0",
                    [static::bitwiseCasts($column, $flag)]
                );
            }
        });
    }
}
