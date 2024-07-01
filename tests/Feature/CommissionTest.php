<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Artisan;
use App\Models\Commission;
use App\Http\Controllers\Service;

class CommissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Optionally, run migrations and seed the database before each test
        // Artisan::call('migrate');
        // Artisan::call('db:seed');
    }

    public function testStoreCommissionSuccessfully()
    {
        // Mock the Service class
        $serviceMock = $this->createMock(Service::class);
        $serviceMock->method('generateUid')->willReturn('test-uid');

        $this->app->instance(Service::class, $serviceMock);

        $response = $this->postJson('http://127.0.0.1:8001/api/commission/store', [
            'name' => 'Test Commission',
            'short' => 'TC'
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Commission saved successffuly!'
                 ]);

        $this->assertDatabaseHas('commissions', [
            'name' => 'Test Commission',
            'short' => 'TC',
            'uid' => 'test-uid'
        ]);
    }

    public function testStoreCommissionValidationFailure()
    {
        $response = $this->postJson('http://127.0.0.1:8001/api/commission/store', [
            'name' => '',
            'short' => ''
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'message',
                     'errors'
                 ]);
    }
}
