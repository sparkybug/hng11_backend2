<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Token;
use App\Models\User;
use App\Models\Organisation;
use Carbon\Carbon;

class AuthTest extends TestCase 
{
    use RefreshDatabase;

    public function testTokenExpiration()
    {
        $user = User::factory()->create();
        $tokenString = JWTAuth::fromUser($user);
        $token = new Token($tokenString);

        // Decode token to check expiry
        $decoded = JWTAuth::decode($token);
        $exp = Carbon::createFromTimestamp($decoded['exp']);

        // Assert token expires in 60 minutes 
        $this->assertTrue($exp->greaterThan(Carbon::now()));
        $this->assertTrue($exp->lessThan(Carbon::now()->addMinutes(60)));
    }

    public function testTokenContainsCorrectUserDetails()
    {
        $user = User::factory()->create();
        $tokenString = JWTAuth::fromUser($user);
        $token = new Token($tokenString);

        $decoded = JWTAuth::decode($token);

        // Assert user details in the token
        $this->assertEquals($user->id, $decoded['sub']);
        $this->assertEquals($user->first_name, $decoded['first_name']);
        $this->assertEquals($user->last_name, $decoded['last_name']);
        // Ensure the phone is in the token payload
        $this->assertEquals($user->phone, $decoded['phone']);
    }

    public function testUserCanAccessOwnOrganisation()
    {
        $user = User::factory()->create();
        $organisation = Organisation::factory()->create();
        $user->organisations()->attach($organisation);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->get('api/organisations/' . $organisation->id);

        $response->assertStatus(200)->assertJson([
            'data' => [
                'orgId' => $organisation->id,
                'name' => $organisation->name,
                'description' => $organisation->description,
            ]
        ]);
    }

    public function testUserCannotAccessOtherOrganisation()
    {
        $user = User::factory()->create();
        $organisation = Organisation::factory()->create();

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->get('api/organisations/' . $organisation->id);

        $response->assertStatus(404);
    }

    public function testSuccessfulRegistrationWithDefaultOrganisation()
    {
        $response = $this->postJson('api/auth/register', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'password123',
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
            'email' => 'john.doe@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson('api/auth/login', [
            'email' => 'john.doe@example.com',
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
