<?php

use App\Http\Controllers\CommentController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\IsAdmin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/users', [UserController::class, 'index']);
    Route::post('/user/update', [UserController::class, 'update']);
    Route::get('/user/details', [UserController::class, 'details']);
    Route::post('/user/destroy', [UserController::class, 'destroy']);

    Route::middleware([IsAdmin::class])->group(function () {
        Route::post('/tag/create', [TagController::class, 'store']);
        Route::post('/tag/destroy', [TagController::class, 'destroy']);
    });

    Route::post('/post/create', [PostController::class, 'store']);
    Route::get('/post/details', [PostController::class, 'details']);
    Route::get('/posts', [PostController::class, 'index']);
    Route::post('/post/update', [PostController::class, 'update']);
    Route::post('/post/destroy', [PostController::class, 'destroy']);

    Route::post('/comment/create', [CommentController::class, 'store']);
    Route::get('user/comments', [CommentController::class, 'index']);
    Route::post('/comment/update', [CommentController::class, 'update']);
    Route::get('/comment/details', [CommentController::class, 'details']);
    Route::post('/comment/destroy', [CommentController::class, 'destroy']);
    Route::post('/comment/changePinStatus', [CommentController::class, 'changePinStatus']);

    Route::get('/tags', [TagController::class, 'index']);


    

});
