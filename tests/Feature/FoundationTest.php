<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class FoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_serves_the_existing_storefront(): void
    {
        $response = $this->get('/')->assertOk();
        $this->assertSame(public_path('index.html'), $response->baseResponse->getFile()->getPathname());
    }

    public function test_public_registration_cannot_assign_an_admin_role(): void
    {
        $this->postJson('/api/signup', [
            'name' => 'Customer', 'email' => 'admin@greenfood.vn',
            'password' => 'a-long-test-password', 'password_confirmation' => 'a-long-test-password',
            'Role' => 1,
        ])->assertCreated()->assertJsonPath('user.Role', 0);
        $this->assertDatabaseHas('users', ['email' => 'admin@greenfood.vn', 'Role' => 0]);
    }

    public function test_demo_seed_is_repeatable_and_preserves_existing_changes(): void
    {
        Storage::fake('public');
        config(['demo.admin_password' => 'a-local-admin-password']);
        $this->seed(DatabaseSeeder::class);
        $product = Product::first();
        $product->update(['Quantity' => 7]);
        $admin = User::where('email', 'admin@greenfood.test')->firstOrFail();
        $hash = $admin->password;
        config(['demo.admin_password' => 'a-different-password']);
        $this->seed(DatabaseSeeder::class);
        $this->assertDatabaseCount('products', 8);
        $this->assertDatabaseCount('users', 2);
        $this->assertSame(4, Product::distinct()->count('Category'));
        $this->assertSame(7, $product->fresh()->Quantity);
        $this->assertSame($hash, $admin->fresh()->password);
        $this->assertTrue(Hash::check('a-local-admin-password', $hash));
        Storage::disk('public')->assertExists($product->Image);
        $this->getJson('/api/products')->assertOk()->assertJsonPath('data.total', 8);
    }

    public function test_demo_seed_requires_an_explicit_admin_password(): void
    {
        config(['demo.admin_password' => null]);
        try {
            $this->seed(DatabaseSeeder::class);
            $this->fail('Seeding should refuse a missing admin password.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('DEMO_ADMIN_PASSWORD', $exception->getMessage());
        }
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('products', 0);
    }

    public function test_demo_seed_refuses_production(): void
    {
        $this->app->instance('env', 'production');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('restricted to local/testing');
        (new DatabaseSeeder)->run();
    }

    public function test_admin_command_refuses_to_promote_an_existing_customer(): void
    {
        $user = User::factory()->create(['Role' => 0]);
        $this->artisan('greenfood:create-admin', ['email' => $user->email])->assertExitCode(1);
        $this->assertEquals(0, $user->fresh()->Role);
    }

    public function test_demo_seed_refuses_to_promote_an_existing_customer(): void
    {
        $user = User::factory()->create(['email' => 'admin@greenfood.test', 'Role' => 0]);
        config(['demo.admin_password' => 'a-private-admin-password']);
        try {
            (new DatabaseSeeder)->run();
            $this->fail('Seeding must refuse an existing customer at the demo admin email.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('No account will be promoted', $exception->getMessage());
        }
        $this->assertEquals(0, $user->fresh()->Role);
        $this->assertDatabaseCount('products', 0);
    }

    public function test_admin_command_rejects_mismatched_passwords(): void
    {
        $this->artisan('greenfood:create-admin', ['email' => 'owner@greenfood.test'])
            ->expectsQuestion('Password (at least 12 characters)', 'a-private-admin-password')
            ->expectsQuestion('Confirm password', 'a-different-password')
            ->assertExitCode(1);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_admin_command_creates_an_admin_with_a_hashed_password(): void
    {
        $this->artisan('greenfood:create-admin', ['email' => 'owner@greenfood.test'])
            ->expectsQuestion('Password (at least 12 characters)', 'a-private-admin-password')
            ->expectsQuestion('Confirm password', 'a-private-admin-password')
            ->assertSuccessful();
        $admin = User::where('email', 'owner@greenfood.test')->firstOrFail();
        $this->assertEquals(1, $admin->Role);
        $this->assertTrue(Hash::check('a-private-admin-password', $admin->password));
    }
}
