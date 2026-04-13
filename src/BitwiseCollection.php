<?php

namespace DeGecko\Bitwise;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class BitwiseCollection extends Collection
{
    protected ?Model $model = null;

    public function model(Model $model): self
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Check if ANY of the given flags are present.
     */
    public function either(...$flags): bool
    {
        foreach ($flags as $flag) {
            if ($this->contains($flag)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Alias for either(). Check if any of the given flags are present.
     */
    public function is(...$flags): bool
    {
        return $this->either(...$flags);
    }

    /**
     * Check if NONE of the given flags are present.
     */
    public function neither(...$flags): bool
    {
        return ! $this->either(...$flags);
    }

    /**
     * Check that ALL of the given flags are absent.
     */
    public function not(...$flags): bool
    {
        foreach ($flags as $flag) {
            if ($this->contains($flag)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Remove a flag from the collection.
     */
    public function remove(string $flag): self
    {
        if (($found = array_search($flag, $this->items)) !== false) {
            unset($this->items[$found]);
        }

        return $this;
    }

    /**
     * Add one or more flags to the collection.
     */
    public function set(...$flags): self
    {
        foreach ($flags as $flag) {
            if (! $this->contains($flag)) {
                $this->items[] = $flag;
            }
        }

        return $this;
    }

    /**
     * Toggle a flag on or off. If $state is null, flips the current state.
     */
    public function toggle(string $flag, ?bool $state = null): self
    {
        if ($state === null) {
            return $this->not($flag)
                ? $this->set($flag)
                : $this->remove($flag);
        }

        if ($state && $this->not($flag)) {
            $this->set($flag);
        } elseif (! $state && $this->is($flag)) {
            $this->remove($flag);
        }

        return $this;
    }

    /**
     * Persist the parent model to the database.
     */
    public function save(): bool
    {
        return $this->model?->save() ?? false;
    }
}
