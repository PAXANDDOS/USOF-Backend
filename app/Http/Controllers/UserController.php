<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Requests\AvatarRequest;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\Handler;
use App\Models\User;
use App\Models\Posts;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth.admin')->only([
            'index',
            'store',
            'update',
            'destroy',
        ]);
        $this->user = JWTAuth::user(JWTAuth::getToken());
    }

    public function index()
    {
        return User::all();
    }

    public function store(RegisterRequest $request)
    {
        return User::create($request->all());
    }

    public function show($id)
    {
        if (User::find($id) === null)
            return response([
                'message' => 'User does not exist'
            ], 404);

        return User::find($id);
    }

    public function update(UserUpdateRequest $request, $id)
    {
        if (!$data = User::find($id))
            return response([
                'message' => 'User does not exist'
            ], 404);
        $data->update($request->all());

        return $data;
    }

    public function destroy($id)
    {
        if (User::find($id) === null)
            return response([
                'message' => 'User does not exist'
            ], 404);

        return User::destroy($id);
    }

    public function user()
    {
        try {
            $user = JWTAuth::user(JWTAuth::getToken());
            return response([
                'message' => 'Authenticated as ' . $user->role,
                'token_type' => 'Bearer',
                'expires_in' => JWTAuth::factory()->getTTL() * 60,
                'user' => $user
            ]);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response(['error' => $e->getMessage()], 401);
        }
    }

    public function userUpdate(UpdateUserRequest $request)
    {
        try {
            $userID = JWTAuth::user(JWTAuth::getToken())->id;

            /** @var User $user */
            if (!$user = User::find($userID))
                return response([
                    'message' => 'User does not exist!'
                ], 404);

            $user->update($request->only(['username', 'name', 'email', 'password']));
            return response([
                'message' => 'Your profile was updated.',
                'user' =>  $user
            ]);
        } catch (\Exception $e) {
            return response(['error' => $e->getMessage()], 401);
        }
    }

    public function uploadAvatar(AvatarRequest $request)
    {
        if ($request->file('image')) {
            $user = User::find(JWTAuth::user(JWTAuth::getToken())->id);
            if ($user->image != 'avatars/default.jpeg')
                \Illuminate\Support\Facades\Storage::delete('public/' . $user->image);
            $user->update([
                'image' => $image = $request->file('image')->storeAs('avatars', $user->id . $request->file('image')->getClientOriginalName(), 'public')
            ]);

            return response([
                "message" => "Your avatar was uploaded",
                "image" => $image
            ]);
        }
    }

    public function showMyFaves()
    {
        $user = JWTAuth::user(JWTAuth::getToken());
        if ($user->faves == null)
            return response()->json([
                'message' => 'No favorites here'
            ], 404);

        $result = array();
        foreach ($user->faves as $key) {
            $post = Posts::find($key);
            if (!Handler::authenticatedAsAdmin($this->user)) {
                if ($post->status == true) {
                    array_push($result, $post);
                    continue;
                } else if ($post->status == false && $this->user->id == $post->user_id) {
                    array_push($result, $post);
                    continue;
                } else continue;
            }
            array_push($result, $post);
        }

        return response($result);
    }

    public function showUserFaves($id)
    {
        $user = $this->show($id);
        if ($user->faves == null)
            return response()->json([
                'message' => 'No favorites here'
            ], 404);

        $result = array();
        foreach ($user->faves as $key) {
            $post = Posts::find($key);
            if (!Handler::authenticatedAsAdmin($this->user)) {
                if ($post->status == true) {
                    array_push($result, $post);
                    continue;
                } else if ($post->status == false && $this->user->id == $post->user_id) {
                    array_push($result, $post);
                    continue;
                } else continue;
            }
            array_push($result, $post);
        }

        return response($result);
    }
}
