<?php

namespace Tests\Unit;

// use PHPUnit\Framework\TestCase;
use Tests\TestCase;
use Illuminate\Support\Facades\JWTAuthenticate;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\User;
use App\Models\Organisation;
use Carbon\Carbon;

class auth extends TestCase 
{
    use DatabaseTransactions;
    use RefreshDatabase;

    public function testTokenExpiration()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        // Decode token to check expiry
        $decoded = JWTAuth::decode($token);
        $exp = Carbon::createFromTimestamp($decoded['exp']);

        // Assert token expires in 60 minutes 
        $this->assertTrue($exp->greaterThan(Carbon::now()));
        $this->assertTrue($exp->lessThan(Carbon::now()->addMinutes((60))));
    }

    public function testTokenContainsCorrectUserDetails()
    {
        $user = User::factory()->create();

        $token = JWTAuth::fromUsr($user);
        $decoded = jWTAuth::decode($token);

        // Assert user details in the token
        $this->assertEquals($user->id, $decoded['sub']);
        $this->assertEquals($user->first_name, $decoded['first_name']);
        $this->assertEquals($user->last_name, $decoded['last_name']);
        $this->assertEquals($user->password, $decoded['password']);
        $this->assertEquals($user->phone, $decoded['phone']);
    }

    // Test case: Organisation's access control
    public function testUserCanAccessOwnOrganisation()
    {
        $user = User::factory()->create();
        $organisation = Organisation::factory()->create();

        $user->organisations()->attach($organisation);

        // Simulate authentication and access attempt
        // Assert user can access their own organisation
        $this->actingAs($user)->get('/organisations/' . $organisation->id)->assertStatus(200)->assertJson([
            'data' => [
                'orgId' => $organisation->id,
                'name' => $organisation->name,
                'description' => $organisation->description
            ]
        ]);
    }

    public function testUserCannotAccessOtherOrganisation()
    {
        $user = User::factory()->create();
        $organisation = Organisation::factory()->create();

        // Simulate authentication but do not attach organisation to user
        // Assert user cannot access an organisation they don't belong to
        $this->actingAs($user)->get('/organisations/' . $organisation->id)
             ->assertStatus(400); 
    }

    public function testSuccessfulRegistrationWithDefaultOrganisation()
    {
        $response = $this->postJson('/auth/register', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(201)->assertJson([
            'status' => 'success',
            'message' => 'Registration successful',
            'data' => [
                'user' => [
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                    'email' => 'john.doe@example.com',
                ],
            ]
        ]);
    }

    public function testLoginWithValidCredentials()
    {
        $user = User::factory()->create([
            'email' => 'jane.doe@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/auth/login', [
            'email' => 'jane.doe@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)->assertJson([
            'status' => 'success',
            'message' => 'Login successful',
            'data' => [
                'user' => [
                    'userId' => $user->id,
                    'firstName' => $user->first_name,
                    'lastName' => $user->last_name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                ],
            ]
        ]);
    }
}