<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Organisation;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:15',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors()->toArray();
            $formattedErrors = [];

            foreach ($errors as $field => $messages) {
                foreach($messages as $message) {
                    $formattedErrors[] = [
                        'field' => $field,
                        'message' => $message
                    ];
                }
            }

            return response()->json([
                'errors' => $formattedErrors
            ], 422);
        }

        try {
            $user = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => $request->password,
                'phone' => $request->phone,
            ]);
    
            Log::info('User registered with hashed password: ' . $user->password);
    
            // Automatically create an organisation and attach it to the user
            $organisation = Organisation::create([
                'name' => $request->first_name . ' ' . $request->last_name . "'s Organisation",
                'description' => 'Automatically created organisation for {$user->first_name} {$user->last_name}',
            ]);
    
            $user->organisations()->attach($organisation);
    
            $token = JWTAuth::fromUser($user);
    
            return response()->json([
                'status' => 'success',
                'message' => 'Registration successful',
                'data' => [
                    'accessToken' => $token,
                    'user' => [
                        'userId' => $user->id,
                        'firstName' => $user->first_name,
                        'lastName' => $user->last_name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                    ]
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'Bad request',
                'message' => 'Registration unsuccessful',
                'statusCode' => 400,
            ], 400);
        }
    }

    public function login(Request $request)
    {
        try {
            $credentials = $request->only(['email', 'password']);
            Log::info('Login attempt with credentials: ', $credentials);

            // Manual password check for debugging
            $user = User::where('email', $request->email)->first();
            if ($user && Hash::check($request->password, $user->password)) {
                Log::info('Password is correct for user: ' . $user->email);
            } else {
                Log::error('Password is incorrect for user: ' . $request->email);
            }

            if (!$token = Auth::guard('api')->attempt($credentials)) {
                Log::error('Invalid credentials: ', $credentials);
                return response()->json([
                    'errors' => ['The provided credentials are incorrect.']
                ], 401);
            }

            $user = Auth::guard('api')->user();

            return response()->json([
                'status' => 'success',
                'message' => 'Login successful',
                'data' => [
                    'accessToken' => $token,
                    'user' => [
                        'userId' => $user->id,
                        'firstName' => $user->first_name,
                        'lastName' => $user->last_name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                    ]
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'Bad request',
                'message' => 'Authentication failed',
                'statusCode' => 401,
            ], 401);
        }
    }

    public function show($id)
    {
        $user = User::findOrFail($id);

        return response()->json([
            'status' => 'success',
            'message' => 'User retrieved successfully',
            'data' => [
                'user' => [
                    'userId' => $user->id,
                    'firstName' => $user->first_name,
                    'lastName' => $user->last_name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                ]
            ]
        ], 200);
    }
}
