<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\Validated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UserController
{

    public function index(Request $request)
    {
        $logger = Log::channel('user');
        $fields = $request->all();
        $validator = Validator::make($request->all(), 
        [
            'limit'  =>  'required|numeric',
            'keyword'  =>  'nullable',
        ],
        [
            'limit.required'  =>  'Limit is required.',
        ]);

        if($validator->fails()){
            $logger->error('Validation failed: ' . json_encode($validator->errors()->all()));
            $logger->error('Submitted data: ' . json_encode($fields));

            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        try{
            
            $users = User::withCount('posts')->withCount('comments') 
            ->when($fields['keyword'], function ($query) use ($request) { 
                $query->where(function ($subQuery) use ($request) {
                    $subQuery->where('name', 'LIKE', '%' . $request->keyword . '%')
                        ->orWhere('email', 'LIKE', '%' . $request->keyword . '%');
                });
            })
            ->paginate($fields['limit']); 

            return response()->json([
                'success' => true,
                'message' => 'Users fetched successfully.',
                'data' => $users
            ], 200);            

        } catch ( \Exception | \PDOException | \Throwable $e) {

            $logger->error('Exception during registration: ' . $e->getMessage());
            $logger->error('Submitted data: ' . json_encode($fields));

            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
                'message' => 'Failed to fetch users.',
            ], 500);

        }
    }

    public function register(Request $request)
    {

        $logger = Log::channel('user');
        $fields = $request->input();

        $validator = Validator::make($request->all(),
        [
            'name'  =>  'required|string|min:3|max:100',
            'email' =>  'required|email|unique:users',
            'password' => 'required|min:6|max:16|regex:/[a-z]/|regex:/[A-Z]/|regex:/[0-9]/|regex:/[@$!%*?&#]/',
        ],
        [
            'name.required' =>  'Name is required.',
            'email.unique'  =>  'Email already exists.',
            'password.regex'    =>  'Password must include at least one lowercase letter, one uppercase letter, one number, and one special character.',
        ]);

        if($validator->fails()){
            $logger->error('Validation failed: ' . json_encode($validator->errors()->all()));
            $logger->error('Submitted data: ' . json_encode($fields));

            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        try {

            $user = User::create([
                'name'  =>  $fields['name'],
                'email' =>  $fields['email'],
                'password'  =>  bcrypt($fields['password'])
            ]);


            $token = $user->createToken('api_token')->plainTextToken;

            return response()->json([
                'success'   =>  true,
                'token'  =>  $token,
                'message'   =>  'User created successfully.'
            ], 201);

                
        } catch (\Exception | \PDOException | \Throwable $e) {

            $logger->error('Exception during registration: ' . $e->getMessage());
            $logger->error('Submitted data: ' . json_encode($fields));

            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
                'message' => 'Registration failed.',
            ], 500);
            
        }

    }

    public function login(Request $request)
    {
        $fields = $request->all();
        $validator = Validator::make($request->all(),
        [
            'email' =>  'required|email',
            'password'  =>  'required'
        ],
        [
            'email.required'    =>  'Email is required.',
            'password'  =>  'Password is required.'
        ]);

        if($validator->fails()){
            $errors = $validator->errors();
            return response()->json([
                'success' => false,
                'message' => $errors
            ], 422);
        } else {
            
            $credentials = $request->only('email', 'password');

            if (!Auth::attempt($credentials)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials'
                ], 401);
            }

            $user = Auth::user();
            $token = $user->createToken('api_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'token' => $token,
                'message' => 'Login successful'
            ]);
        }
    }

     public function update(Request $request)
    {

        $logger = Log::channel('user');
        $fields = $request->input();

        $validator = Validator::make($request->all(),
        [
            'name'  =>  'required|string|min:3|max:100',
            'email' =>  'required|email|unique:users',
            'password' => 'required|min:6|max:16|regex:/[a-z]/|regex:/[A-Z]/|regex:/[0-9]/|regex:/[@$!%*?&#]/',
        ],
        [
            'name.required' =>  'Name is required.',
            'email.unique'  =>  'Email already exists.',
            'password.regex'    =>  'Password must include at least one lowercase letter, one uppercase letter, one number, and one special character.',
        ]);

        if($validator->fails()){
            $logger->error('Validation failed: ' . json_encode($validator->errors()->all()));
            $logger->error('Submitted data: ' . json_encode($fields));

            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        try {

            $user = User::find(auth()->id());
            $user->name = $fields['name'];
            $user->email = $fields['email'];
            $user->password = bcrypt($fields['email']);
            $user->save();

            if (!$user->editable) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to update this user.'
                ], 403);
            }

            $token = $user->createToken('api_token')->plainTextToken;

            return response()->json([
                'success'   =>  true,
                'token'  =>  $token,
                'message'   =>  'User created successfully.'
            ], 201);

                
        } catch (\Exception | \PDOException | \Throwable $e) {

            $logger->error('Exception during registration: ' . $e->getMessage());
            $logger->error('Submitted data: ' . json_encode($fields));

            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
                'message' => 'Registration failed.',
            ], 500);
            
        }

    }

    public function details(Request $request)
    {
        $logger = Log::channel('user');
        $fields = $request->all();
        $validator = Validator::make($request->all(), 
        [
            'user_id'  =>  'required|exists:users,id',
        ],
        [
            'user_id.exists'  =>  "User Id doesn't exists.",
        ]);

        if($validator->fails()){
            $logger->error('Validation failed: ' . json_encode($validator->errors()->all()));
            $logger->error('Submitted data: ' . json_encode($fields));

            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        try{
            
            $user = User::with('posts')->find($fields['user_id']);

            if($user){
                return response()->json([
                    'success' => true,
                    'message' => 'User fetched successfully.',
                    'data'  =>  $user
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch user.',
                ], 400);
            }
            

        } catch ( \Exception | \PDOException | \Throwable $e) {

            $logger->error('Exception during registration: ' . $e->getMessage());
            $logger->error('Submitted data: ' . json_encode($fields));

            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
                'message' => 'Failed to create post.',
            ], 500);

        }
    }

    public function destroy(Request $request)
    {
        $logger = Log::channel('user');
        $fields = $request->all();
        
        try{
            
            $user = User::find(auth()->id);

            if (!$user->editable) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to delete this user.'
                ], 403);
            }

            if($user){

                if($user->delete()){
                    return response()->json([
                        'success' => true,
                        'message' => 'User deleted successfully.',
                    ], 200);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to delete user.',
                    ], 400);
                }
                
            } else {
                return response()->json([
                    'success' => false,
                    'message' => "User doesn't exits.",
                ], 400);
            }
            

        } catch ( \Exception | \PDOException | \Throwable $e) {

            $logger->error('Exception during registration: ' . $e->getMessage());
            $logger->error('Submitted data: ' . json_encode($fields));

            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
                'message' => 'Failed to create post.',
            ], 500);

        }
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }
    
}
