<?php


namespace Tests\Feature;

use App\Models\User;
use App\Models\Parking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParkingOwnerControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_creator_can_add_co_owner_to_parking()
    {
        // Create user and login
        $creator = User::factory()->create();
        $this->actingAs($creator);

        // Create a parking linked to creator
        $parking = Parking::factory()->create(['user_id' => $creator->id]);

        // Create co-owner user
        $coOwner = User::factory()->create(['email' => 'co@example.com']);

        // Call the API
        $response = $this->postJson("/api/parkings/{$parking->id}/co-owners", [
            'emails' => ['co@example.com']
        ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Co-owners added successfully.']);

        // Assert relation exists in DB
        $this->assertDatabaseHas('parking_owner', [
            'parking_id' => $parking->id,
            'user_id' => $coOwner->id,
            'role' => 'co_owner',
        ]);
    }
}