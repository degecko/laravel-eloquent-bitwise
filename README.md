# Laravel Eloquent Bitwise

Store multiple boolean flags in a single integer column using bitwise operations. Provides a fluent API for reading, modifying, and querying flags on Eloquent models.

## Installation

```bash
composer require degecko/laravel-eloquent-bitwise
```

## Setup

Add an integer column to your migration:

```php
$table->integer('status')->default(0);
$table->smallInteger('permissions')->default(0);
```

Use the trait in your model and list your flags:

```php
use DeGecko\Bitwise\HasBitwiseFlags;

class User extends Model
{
    use HasBitwiseFlags;

    public array $bitwiseCasts = [
        'status' => ['active', 'verified', 'premium', 'suspended'],
        'permissions' => ['read', 'write', 'execute'],
    ];
}
```

That's it. The trait automatically registers the casts and assigns each flag a bit value based on its position (1, 2, 4, 8, ...).

### Important: Flag Ordering

**Do not reorder, rename, or remove flags from the middle of the array.** The bit values are determined by position. Changing the order will corrupt existing data in the database.

Always **append new flags to the end** of the array:

```php
// Before
'status' => ['active', 'verified', 'premium'],

// Correct — append to the end
'status' => ['active', 'verified', 'premium', 'suspended'],

// WRONG — do NOT insert or reorder
'status' => ['suspended', 'active', 'verified', 'premium'],
```

If you need to deprecate a flag, leave it in place and stop using it in your code, or switch to explicit values:

```php
public array $bitwiseCasts = [
    'status' => [
        'active'    => 1,
        'verified'  => 2,
        // 'removed' was 4 — gap is intentional
        'premium'   => 8,
        'suspended' => 16,
    ],
];
```

## Usage

### Checking flags

```php
$user->status->is('verified');             // true if 'verified' is set
$user->status->is('admin', 'moderator');   // true if either is set
$user->status->either('vip', 'premium');   // alias for is()

$user->status->not('suspended');           // true if 'suspended' is not set
$user->status->not('suspended', 'banned'); // true if ALL are absent
$user->status->neither('suspended', 'banned'); // true if NONE are set
```

### Modifying flags

```php
$user->status->set('verified');              // add a flag
$user->status->set('premium', 'active');     // add multiple flags
$user->status->remove('suspended');          // remove a flag
$user->status->toggle('active');             // flip on/off
$user->status->toggle('active', true);       // explicitly set on
$user->status->toggle('active', false);      // explicitly set off
```

### Persisting

```php
// Chain modifications and save in one go
$user->status->set('verified')->remove('pending')->save();

// Or save the model directly
$user->status->set('verified');
$user->save();
```

### Querying

```php
// Find users where specific flags are set
User::bitwise('status', 'active', 'verified')->get();

// Find users where specific flags are NOT set
User::bitwiseNot('status', 'suspended', 'banned')->get();

// Combine with other query conditions
User::bitwise('status', 'active')
    ->bitwiseNot('status', 'suspended')
    ->where('created_at', '>', now()->subDays(30))
    ->get();
```

## How it works

Flags are stored as a single integer using bitwise operations. Each flag is assigned a power of 2 (1, 2, 4, 8, ...), allowing up to 32 or 64 flags per column depending on your integer size.

For example, a user with `active` (1) and `premium` (4) flags has a stored value of `5` (`1 | 4`). Checking for a flag uses bitwise AND: `5 & 4` is truthy, so `premium` is set.

This is far more storage-efficient than JSON arrays or separate boolean columns, and queries use simple integer math that databases handle natively.

## API Reference

| Method | Returns | Description |
|--------|---------|-------------|
| `is(...$flags)` | `bool` | True if any flag is set |
| `either(...$flags)` | `bool` | Alias for `is()` |
| `not(...$flags)` | `bool` | True if all flags are absent |
| `neither(...$flags)` | `bool` | True if none of the flags are set |
| `set(...$flags)` | `self` | Add flags (idempotent) |
| `remove($flag)` | `self` | Remove a flag |
| `toggle($flag, ?bool)` | `self` | Flip or explicitly set a flag |
| `save()` | `bool` | Persist the parent model |

### Query Scopes

| Scope | Description |
|-------|-------------|
| `bitwise($column, ...$flags)` | Where all given flags are set |
| `bitwiseNot($column, ...$flags)` | Where all given flags are not set |

## License

MIT
