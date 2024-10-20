<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;

use App\Models\User;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_login_page(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_dashboard(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect(route('login'));
        $response->assertStatus(302);
    }

    public function test_melihat_halaman_barang_setelah_login(): void
    {
        $user = User::factory()->create([
            'name' => 'Ardi',
            'roles_id' => '1',
            'email' => 'ardi@gmail.com',
            'password' => '12345678',
        ]);
        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(403);
    }
}
