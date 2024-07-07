<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Organisation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class OrganisationController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $organisations = $user->organisations;

        $orgDetails = $organisations->map(function ($organisation) {
            return [
                'orgId' => $organisation->id,
                'name' => $organisation->name,
                'description' => $organisation->description,
            ];
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Organisations retrieved successfully',
            'data' => [
                'organisations' => $orgDetails
            ]
        ], 200);
    }

    public function show($orgId)
    {
        $user = Auth::user();
        $organisation = $user->organisations()->where('id', $orgId)->firstOrFail();

        return response()->json([
            'status' => 'success',
            'message' => 'Organisation retrieved successfully',
            'data' => [
                'orgId' => $organisation->id,
                'name' => $organisation->name,
                'description' => $organisation->description,
            ]
        ], 200);
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
            ]);
    
            $user = Auth::user();
    
            $organisation = Organisation::create([
                'name' => $request->name,
                'description' => $request->description,
            ]);
    
            $user->organisations()->attach($organisation->id);
    
            return response()->json([
                'status' => 'success',
                'message' => 'Organisation created successfully',
                'data' => [
                    'orgId' => $organisation->id,
                    'name' => $organisation->name,
                    'description' => $organisation->description,
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'Bad request',
                'message' => 'Client error',
                'statusCode' => 400,
            ], 400);
        }
    }

    public function addUser($orgId, Request $request)
    {
        $request->validate([
            'userId' => 'required|uuid|exists:users,id',
        ]);

        $organisation = Organisation::findOrFail($orgId);
        $user = User::findOrFail($request->userId);

        if ($organisation->users()->where('user_id', $user->id)->exists()) {
            return response()->json([
                'status' => 'fail',
                'message' => 'User is already part of the organisation',
            ], 400);
        }

        // Attach the user to the organisation
        $organisation->users()->attach($user);

        // Confirm the user was added
        if ($organisation->users()->where('user_id', $user->id)->exists()) {
            return response()->json([
                'status' => 'success',
                'message' => 'User added to organisation successfully',
            ], 200);
        } else {
            return response()->json([
                'status' => 'fail',
                'message' => 'Failed to add user to organisation',
            ], 500);
        }
    }

}
