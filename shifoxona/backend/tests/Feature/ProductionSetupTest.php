<?php

namespace Tests\Feature;

use App\Models\Disease;
use App\Models\District;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProductionSetupTest extends TestCase
{
    use DatabaseMigrations;

    public function test_production_seeding_does_not_create_demo_accounts_or_patients(): void
    {
        $this->app->instance('env', 'production');
        try {
            $this->artisan('db:seed', ['--force' => true])->assertSuccessful();
            $this->artisan('db:seed', ['--force' => true])->assertSuccessful();
        } finally {
            $this->app->instance('env', 'testing');
        }

        $this->assertSame(14, Region::count());
        $this->assertGreaterThan(200, District::count());
        $this->assertSame(12, Disease::count());
        $this->assertSame(0, User::count());
        $this->assertSame(0, Hospital::count());
        $this->assertSame(0, Patient::count());
    }

    public function test_admin_command_creates_an_admin_with_a_hashed_password(): void
    {
        $this->artisan('shifo:create-admin')
            ->expectsQuestion('Ism va familiya', 'Server Admin')
            ->expectsQuestion('Email', 'ADMIN@example.com')
            ->expectsQuestion('Parol (kamida 12 belgi)', 'ProductionPassword42!')
            ->expectsQuestion('Parolni takrorlang', 'ProductionPassword42!')
            ->assertSuccessful();

        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->assertSame('admin', $admin->role);
        $this->assertNull($admin->hospital_id);
        $this->assertTrue(Hash::check('ProductionPassword42!', $admin->password));
    }

    public function test_admin_command_rejects_short_or_mismatching_passwords(): void
    {
        $this->artisan('shifo:create-admin')
            ->expectsQuestion('Ism va familiya', 'Server Admin')
            ->expectsQuestion('Email', 'admin@example.com')
            ->expectsQuestion('Parol (kamida 12 belgi)', 'short')
            ->expectsQuestion('Parolni takrorlang', 'different')
            ->assertFailed();

        $this->assertSame(0, User::count());
    }

    public function test_admin_command_preserves_an_existing_account(): void
    {
        $user = User::factory()->create(['email' => 'existing@example.com', 'role' => 'doctor']);

        $this->artisan('shifo:create-admin')
            ->expectsQuestion('Ism va familiya', 'Server Admin')
            ->expectsQuestion('Email', 'existing@example.com')
            ->expectsQuestion('Parol (kamida 12 belgi)', 'ProductionPassword42!')
            ->expectsQuestion('Parolni takrorlang', 'ProductionPassword42!')
            ->assertFailed();

        $this->assertSame(1, User::count());
        $this->assertSame('doctor', $user->fresh()->role);
    }
}
