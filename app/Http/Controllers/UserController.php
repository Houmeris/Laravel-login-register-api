<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\userService;
use App\Http\Resources\UserResource;
use App\Http\Resources\TokenResource;

class UserController extends Controller
{
    private $userService;

    public function __construct(userService $userService)
    {
        $this->userService = $userService;
    }

    public function register(Request $request)
    {
        // validate the request data
        $validateData = $request->validate([
            'name' => 'required|string|max:191',
            'phone' => 'required|string|min:7|max:7',
            'email' => 'nullable|string|email|max:191|unique:users',
            'password' => 'required|string|min:8',
            'terms_and_privacy_policy_agree' => 'required|boolean',
        ]);

        // register the new user
        $user = $this->userService->register($validateData);

        // return a response
        return response()->json([
            'message' => 'User registered successfully',
            'user' => new UserResource($user),
        ], 201);

    }

    public function login(Request $request)
    {
        // validate the request data
        $validatedData = $request->validate([
            'email' => 'required_if:phone,""|string|email',
            'phone' => 'required_if:email,""|string',
            'password' => 'required_if:phone,""|string',
        ]);

        // attempt to log in the user
        $token = $this->userService->login($validatedData);

        // return a response
        if ($token)
        {
            return response()->json([
                'message' => 'Login successful',
                //'access_token' => new TokenResource($token),
                'access_token' => $token,
                'type' => 'Bearer'
            ]);
        } else
        {
            return response()->json([
                'message' => 'Invalid login credentials',
            ], 401);
        }
    }
    
    public function me(Request $request)
    {
        return $request->user();
    }
}
