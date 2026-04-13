<?php

namespace DeGecko\Bitwise\Tests;

use DeGecko\Bitwise\BitwiseCast;
use DeGecko\Bitwise\BitwiseCollection;
use DeGecko\Bitwise\HasBitwiseFlags;
use Illuminate\Database\Eloquent\Model;
use Orchestra\Testbench\TestCase;

class HasBitwiseFlagsTest extends TestCase
{
    // --- auto-registration ---

    public function test_casts_are_auto_registered(): void
    {
        $model = new AutoRegisteredModel;

        $this->assertInstanceOf(BitwiseCollection::class, $model->status);
        $this->assertInstanceOf(BitwiseCollection::class, $model->permissions);
    }

    public function test_no_manual_casts_needed(): void
    {
        $model = new AutoRegisteredModel;
        $casts = $model->getCasts();

        $this->assertSame(BitwiseCast::class, $casts['status']);
        $this->assertSame(BitwiseCast::class, $casts['permissions']);
    }

    // --- simple array auto-calculation ---

    public function test_simple_array_resolves_to_powers_of_two(): void
    {
        $result = SimpleArrayFlagModel::bitwiseCasts('status');

        $this->assertSame([
            'active' => 1,
            'verified' => 2,
            'suspended' => 4,
        ], $result);
    }

    public function test_simple_array_single_flag_lookup(): void
    {
        $this->assertSame(1, SimpleArrayFlagModel::bitwiseCasts('status', 'active'));
        $this->assertSame(4, SimpleArrayFlagModel::bitwiseCasts('status', 'suspended'));
    }

    // --- explicit values ---

    public function test_explicit_values_are_preserved(): void
    {
        $result = ExplicitFlagModel::bitwiseCasts('status');

        $this->assertSame([
            'active' => 1,
            'verified' => 2,
            'suspended' => 8,
        ], $result);
    }

    // --- bitwiseCasts static method ---

    public function test_bitwise_casts_returns_null_for_unknown_flag(): void
    {
        $this->assertNull(SimpleArrayFlagModel::bitwiseCasts('status', 'nonexistent'));
    }

    public function test_bitwise_casts_returns_empty_array_for_unknown_column(): void
    {
        $this->assertSame([], SimpleArrayFlagModel::bitwiseCasts('nonexistent'));
    }

    public function test_multiple_bitwise_columns(): void
    {
        $result = SimpleArrayFlagModel::bitwiseCasts('permissions');

        $this->assertSame(['read' => 1, 'write' => 2], $result);
    }

    // --- scope SQL generation ---

    public function test_bitwise_scope_generates_correct_sql(): void
    {
        $model = new SimpleArrayFlagModel;
        $query = $model->newQuery();

        $model->scopeBitwise($query, 'status', 'active', 'verified');

        $sql = $query->toRawSql();
        $this->assertStringContainsString('status & 1', $sql);
        $this->assertStringContainsString('status & 2', $sql);
    }

    public function test_bitwise_not_scope_generates_correct_sql(): void
    {
        $model = new SimpleArrayFlagModel;
        $query = $model->newQuery();

        $model->scopeBitwiseNot($query, 'status', 'suspended');

        $sql = $query->toRawSql();
        $this->assertStringContainsString('status & 4 = 0', $sql);
    }
}

class AutoRegisteredModel extends Model
{
    use HasBitwiseFlags;

    public array $bitwiseCasts = [
        'status' => ['active', 'verified'],
        'permissions' => ['read', 'write'],
    ];
}

class SimpleArrayFlagModel extends Model
{
    use HasBitwiseFlags;

    protected $table = 'test_models';

    public array $bitwiseCasts = [
        'status' => ['active', 'verified', 'suspended'],
        'permissions' => ['read', 'write'],
    ];
}

class ExplicitFlagModel extends Model
{
    use HasBitwiseFlags;

    protected $table = 'test_models';

    public array $bitwiseCasts = [
        'status' => [
            'active' => 1,
            'verified' => 2,
            'suspended' => 8,
        ],
    ];
}
