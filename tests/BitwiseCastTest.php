<?php

namespace DeGecko\Bitwise\Tests;

use DeGecko\Bitwise\BitwiseCast;
use DeGecko\Bitwise\BitwiseCollection;
use DeGecko\Bitwise\HasBitwiseFlags;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;

class BitwiseCastTest extends TestCase
{
    private BitwiseCast $cast;

    protected function setUp(): void
    {
        $this->cast = new BitwiseCast;
    }

    // --- get (simple array format) ---

    public function test_get_decodes_integer_to_collection(): void
    {
        $model = new SimpleArrayModel;

        $result = $this->cast->get($model, 'flags', 3, []);

        $this->assertInstanceOf(BitwiseCollection::class, $result);
        $this->assertTrue($result->is('active'));
        $this->assertTrue($result->is('verified'));
        $this->assertFalse($result->is('premium'));
    }

    public function test_get_decodes_single_flag(): void
    {
        $result = $this->cast->get(new SimpleArrayModel, 'flags', 4, []);

        $this->assertTrue($result->is('premium'));
        $this->assertFalse($result->is('active'));
    }

    public function test_get_decodes_all_flags(): void
    {
        $result = $this->cast->get(new SimpleArrayModel, 'flags', 7, []);

        $this->assertTrue($result->is('active'));
        $this->assertTrue($result->is('verified'));
        $this->assertTrue($result->is('premium'));
    }

    public function test_get_handles_zero(): void
    {
        $result = $this->cast->get(new SimpleArrayModel, 'flags', 0, []);

        $this->assertInstanceOf(BitwiseCollection::class, $result);
        $this->assertFalse($result->is('active'));
    }

    public function test_get_handles_null(): void
    {
        $result = $this->cast->get(new SimpleArrayModel, 'flags', null, []);

        $this->assertInstanceOf(BitwiseCollection::class, $result);
        $this->assertCount(0, $result);
    }

    public function test_get_handles_string_value(): void
    {
        $result = $this->cast->get(new SimpleArrayModel, 'flags', '5', []);

        $this->assertTrue($result->is('active'));
        $this->assertFalse($result->is('verified'));
        $this->assertTrue($result->is('premium'));
    }

    // --- set ---

    public function test_set_encodes_collection_to_integer(): void
    {
        $result = $this->cast->set(new SimpleArrayModel, 'flags', new BitwiseCollection(['active', 'premium']), []);

        $this->assertSame(5, $result);
    }

    public function test_set_encodes_single_flag(): void
    {
        $result = $this->cast->set(new SimpleArrayModel, 'flags', new BitwiseCollection(['verified']), []);

        $this->assertSame(2, $result);
    }

    public function test_set_encodes_all_flags(): void
    {
        $result = $this->cast->set(new SimpleArrayModel, 'flags', new BitwiseCollection(['active', 'verified', 'premium']), []);

        $this->assertSame(7, $result);
    }

    public function test_set_encodes_empty_collection_to_zero(): void
    {
        $result = $this->cast->set(new SimpleArrayModel, 'flags', new BitwiseCollection([]), []);

        $this->assertSame(0, $result);
    }

    // --- roundtrip ---

    public function test_get_and_set_roundtrip(): void
    {
        $model = new SimpleArrayModel;
        $original = 5; // active + premium

        $collection = $this->cast->get($model, 'flags', $original, []);
        $encoded = $this->cast->set($model, 'flags', $collection, []);

        $this->assertSame($original, $encoded);
    }

    public function test_modify_and_roundtrip(): void
    {
        $model = new SimpleArrayModel;

        $collection = $this->cast->get($model, 'flags', 1, []); // active
        $collection->set('premium')->remove('active');
        $encoded = $this->cast->set($model, 'flags', $collection, []);

        $this->assertSame(4, $encoded); // premium only
    }

    // --- explicit values format ---

    public function test_explicit_values_work_the_same(): void
    {
        $model = new ExplicitValuesModel;

        $result = $this->cast->get($model, 'flags', 10, []); // 2 + 8

        $this->assertFalse($result->is('active'));
        $this->assertTrue($result->is('verified'));
        $this->assertFalse($result->is('premium'));
        $this->assertTrue($result->is('suspended'));
    }

    public function test_explicit_values_roundtrip(): void
    {
        $model = new ExplicitValuesModel;

        $collection = $this->cast->get($model, 'flags', 10, []);
        $encoded = $this->cast->set($model, 'flags', $collection, []);

        $this->assertSame(10, $encoded);
    }
}

class SimpleArrayModel extends Model
{
    use HasBitwiseFlags;

    public array $bitwiseCasts = [
        'flags' => ['active', 'verified', 'premium'],
    ];
}

class ExplicitValuesModel extends Model
{
    use HasBitwiseFlags;

    public array $bitwiseCasts = [
        'flags' => [
            'active' => 1,
            'verified' => 2,
            'premium' => 4,
            'suspended' => 8,
        ],
    ];
}
