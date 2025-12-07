<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
    'user' => [
        'name' => $user->name,
        'role' => $user->role,
        'email' => $user->email,
    ],
    'token' => $token,
]);
    }
public function register(Request $request)
{
    try {

        $validated = validator($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role' => 'required|in:admin,dosen,mahasiswa',
        ])->validate();

        $user = User::create([
            'name' => $request->input('name', 'User Tanpa Nama'),
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        return response()->json([
            'message' => 'User berhasil dibuat',
            'user' => $user
        ], 201);

    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Gagal membuat user',
            'error'   => $e->getMessage()
        ], 400);
    }
}


    public function logout(Request $request)
{
    $request->user()->currentAccessToken()->delete();

    return response()->json(['message' => 'Logout berhasil']);
}

}
