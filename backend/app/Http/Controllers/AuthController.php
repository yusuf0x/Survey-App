<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\SignupRequest;
use App\Http\Requests\LoginRequest;
class AuthController extends Controller
{
    public function signup(SignupRequest $request)
    {
        $userDetails = $request->validated();
        $user = User::create([
            "name" => $userDetails["name"],
            "email" => $userDetails["email"],
            "password" => bcrypt($userDetails["password"]),
        ]);
        $token = $user->createToken("main")->plainTextToken;
        return response()->json([
            "user" => $user,
            "token" =>$token
        ]);
    }
    public function login(LoginRequest $request)
    {
        $userDetails = $request->validated();
        $remember = $userDetails['remember'] ?? false;
        unset($userDetails['remember']);

        if (!Auth::attempt($userDetails, $remember)) {
            return response([
                'error' => 'The Provided user Details are not correct'
            ], 422);
        }
      
        $user = Auth::user();
        $token = $user->createToken('main')->plainTextToken;

        return response([
            'user' => $user,
            'token' => $token
        ]);
    }

    public function logout(Request $request){
        $user = Auth::user();
        $user->currentAccessToken()->delete();
        return response()-json([
            "success" => true
        ]);
    }
    public function me(Request $request)
    {
        return $request->user();
    }


}
