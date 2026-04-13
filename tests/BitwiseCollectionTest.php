<?php

namespace DeGecko\Bitwise\Tests;

use DeGecko\Bitwise\BitwiseCollection;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;

class BitwiseCollectionTest extends TestCase
{
    private function collection(array $items = []): BitwiseCollection
    {
        return new BitwiseCollection($items);
    }

    // --- is / either ---

    public function test_is_returns_true_when_flag_is_present(): void
    {
        $c = $this->collection(['active', 'verified']);

        $this->assertTrue($c->is('active'));
        $this->assertTrue($c->is('verified'));
    }

    public function test_is_returns_false_when_flag_is_absent(): void
    {
        $c = $this->collection(['active']);

        $this->assertFalse($c->is('premium'));
    }

    public function test_is_returns_true_when_any_flag_matches(): void
    {
        $c = $this->collection(['active']);

        $this->assertTrue($c->is('premium', 'active'));
    }

    public function test_either_is_alias_for_is(): void
    {
        $c = $this->collection(['active']);

        $this->assertTrue($c->either('active'));
        $this->assertFalse($c->either('premium'));
    }

    public function test_is_returns_false_on_empty_collection(): void
    {
        $c = $this->collection();

        $this->assertFalse($c->is('anything'));
    }

    // --- neither ---

    public function test_neither_returns_true_when_no_flags_present(): void
    {
        $c = $this->collection(['active']);

        $this->assertTrue($c->neither('premium', 'suspended'));
    }

    public function test_neither_returns_false_when_any_flag_present(): void
    {
        $c = $this->collection(['active', 'premium']);

        $this->assertFalse($c->neither('premium', 'suspended'));
    }

    // --- not ---

    public function test_not_returns_true_when_all_flags_absent(): void
    {
        $c = $this->collection(['active']);

        $this->assertTrue($c->not('premium', 'suspended'));
    }

    public function test_not_returns_false_when_any_flag_present(): void
    {
        $c = $this->collection(['active', 'premium']);

        $this->assertFalse($c->not('premium', 'suspended'));
    }

    public function test_not_with_single_flag(): void
    {
        $c = $this->collection(['active']);

        $this->assertTrue($c->not('premium'));
        $this->assertFalse($c->not('active'));
    }

    // --- set ---

    public function test_set_adds_flag(): void
    {
        $c = $this->collection();
        $c->set('active');

        $this->assertTrue($c->is('active'));
    }

    public function test_set_multiple_flags(): void
    {
        $c = $this->collection();
        $c->set('active', 'premium');

        $this->assertTrue($c->is('active'));
        $this->assertTrue($c->is('premium'));
    }

    public function test_set_is_idempotent(): void
    {
        $c = $this->collection(['active']);
        $c->set('active');

        $this->assertCount(1, $c);
    }

    public function test_set_returns_self_for_chaining(): void
    {
        $c = $this->collection();

        $this->assertSame($c, $c->set('active'));
    }

    // --- remove ---

    public function test_remove_removes_flag(): void
    {
        $c = $this->collection(['active', 'premium']);
        $c->remove('active');

        $this->assertFalse($c->is('active'));
        $this->assertTrue($c->is('premium'));
    }

    public function test_remove_nonexistent_flag_is_noop(): void
    {
        $c = $this->collection(['active']);
        $c->remove('premium');

        $this->assertCount(1, $c);
        $this->assertTrue($c->is('active'));
    }

    public function test_remove_returns_self_for_chaining(): void
    {
        $c = $this->collection(['active']);

        $this->assertSame($c, $c->remove('active'));
    }

    // --- toggle ---

    public function test_toggle_adds_absent_flag(): void
    {
        $c = $this->collection();
        $c->toggle('active');

        $this->assertTrue($c->is('active'));
    }

    public function test_toggle_removes_present_flag(): void
    {
        $c = $this->collection(['active']);
        $c->toggle('active');

        $this->assertFalse($c->is('active'));
    }

    public function test_toggle_with_true_state_adds_flag(): void
    {
        $c = $this->collection();
        $c->toggle('active', true);

        $this->assertTrue($c->is('active'));
    }

    public function test_toggle_with_true_state_keeps_existing_flag(): void
    {
        $c = $this->collection(['active']);
        $c->toggle('active', true);

        $this->assertTrue($c->is('active'));
        $this->assertCount(1, $c);
    }

    public function test_toggle_with_false_state_removes_flag(): void
    {
        $c = $this->collection(['active']);
        $c->toggle('active', false);

        $this->assertFalse($c->is('active'));
    }

    public function test_toggle_with_false_state_is_noop_when_absent(): void
    {
        $c = $this->collection();
        $c->toggle('active', false);

        $this->assertFalse($c->is('active'));
        $this->assertCount(0, $c);
    }

    public function test_toggle_returns_self_for_chaining(): void
    {
        $c = $this->collection();

        $this->assertSame($c, $c->toggle('active'));
    }

    // --- model / save ---

    public function test_model_sets_parent_and_returns_self(): void
    {
        $c = $this->collection();
        $model = $this->createMock(Model::class);

        $this->assertSame($c, $c->model($model));
    }

    public function test_save_calls_model_save(): void
    {
        $model = $this->createMock(Model::class);
        $model->expects($this->once())
            ->method('save')
            ->willReturn(true);

        $c = $this->collection(['active']);
        $c->model($model);

        $this->assertTrue($c->save());
    }

    public function test_save_returns_false_without_model(): void
    {
        $c = $this->collection(['active']);

        $this->assertFalse($c->save());
    }

    // --- chaining ---

    public function test_fluent_chaining(): void
    {
        $c = $this->collection(['pending']);

        $c->set('active', 'verified')
          ->remove('pending')
          ->toggle('premium');

        $this->assertTrue($c->is('active'));
        $this->assertTrue($c->is('verified'));
        $this->assertTrue($c->is('premium'));
        $this->assertFalse($c->is('pending'));
    }
}
