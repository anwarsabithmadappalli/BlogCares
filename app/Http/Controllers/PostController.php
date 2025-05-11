<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PostController
{

    public function index(Request $request)
    {
        $logger = Log::channel('user');
        $fields = $request->all();
        $validator = Validator::make(
            $request->all(),
            [
                'limit'  =>  'required|numeric',
                'keyword'  =>  'nullable',
            ],
            [
                'limit.required'  =>  'Limit is required.',
            ]
        );

        if ($validator->fails()) {
            $logger->error('Validation failed: ' . json_encode($validator->errors()->all()));
            $logger->error('Submitted data: ' . json_encode($fields));

            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        try {

            $posts = Post::with('user')->withCount('comments', 'tags')
                ->when($fields['keyword'], function ($query) use ($request) {
                    $query->where(function ($subQuery) use ($request) {
                        $subQuery->where('title', 'LIKE', '%' . $request->keyword . '%')
                            ->orWhere('body', 'LIKE', '%' . $request->keyword . '%')
                            ->orWhereHas('user', function ($q) use ($request) {
                                $q->where('name', 'LIKE', '%' . $request->keyword . '%')
                                    ->orWhere('email', 'LIKE', '%' . $request->keyword . '%');
                            })
                            ->orWhereHas('tags', function ($q) use ($request) {
                                $q->where('name', 'LIKE', '%' . $request->keyword . '%');
                            });
                    });
                })
                ->orderBy('posts.created_at', 'desc')
                ->paginate($fields['limit']);

            if ($posts) {
                return response()->json([
                    'success' => true,
                    'message' => 'Posts fetched successfully.',
                    'data'  =>  $posts
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch posts.',
                ], 400);
            }
        } catch (\Exception | \PDOException | \Throwable $e) {

            $logger->error('Exception during registration: ' . $e->getMessage());
            $logger->error('Submitted data: ' . json_encode($fields));

            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
                'message' => 'Failed to create post.',
            ], 500);
        }
    }

    public function store(Request $request)
    {

        $logger = Log::channel('user');
        $fields = $request->all();
        $validator = Validator::make(
            $request->all(),
            [
                'title' =>  'required|string|max:255',
                'body'  =>  'required|string',
                'tag_ids'  =>  'required',
                'tags'  =>  'required'
            ],
            [
                'title.required'    =>  'Title is required.',
                'body.required' =>  'Body is required.',
                'tag_ids.required' =>  "Tag Id's is required.",
                'tags.required' =>  'Tags is required.'
            ]
        );

        if ($validator->fails()) {
            $logger->error('Validation failed: ' . json_encode($validator->errors()->all()));
            $logger->error('Submitted data: ' . json_encode($fields));

            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
            ], 422);
        }


        try {

            DB::beginTransaction();

            $validatedData = $validator->validated();
            $post = Auth::user()->posts()->create($validatedData);
            $new_tags = json_decode($request->tags, true);

            $tag_array = [];
            foreach ($new_tags as $new_tag) {

                $tag_exist = Tag::where('name', $new_tag)->first();;
                if ($tag_exist) {
                    $tag_array[] = $tag_exist->id;
                } else {
                    $tag = new Tag;
                    $tag->name = $new_tag;
                    $tag->save();

                    $tag_array[] = $tag->id;
                }
            }

            $new_tag_ids = array_merge($tag_array, json_decode($request->tag_ids, true));
            $merged = array_unique($new_tag_ids);

            $post->tags()->attach($new_tag_ids);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Post created successfully.',
                'data' => $post
            ], 201);
        } catch (\Exception | \PDOException | \Throwable $e) {

            DB::rollBack();

            $logger->error('Exception during registration: ' . $e->getMessage());
            $logger->error('Submitted data: ' . json_encode($fields));

            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
                'message' => 'Failed to create post.',
            ], 500);
        }
    }

    public function update(Request $request)
    {

        $logger = Log::channel('user');
        $fields = $request->all();
        $validator = Validator::make(
            $request->all(),
            [
                'post_id'   =>  'required|exists:posts,id',
                'title' =>  'required|string|max:255',
                'body'  =>  'required|string',
                'tag_ids'  =>  'required',
                'tags'  =>  'required'
            ],
            [
                'post_id.exists'    =>  "Post Id doesn't exists.",
                'title.required'    =>  'Title is required.',
                'body.required' =>  'Body is required.',
                'tag_ids.required' =>  "Tag Id's is required.",
                'tags.required' =>  'Tags is required.'
            ]
        );

        if ($validator->fails()) {
            $logger->error('Validation failed: ' . json_encode($validator->errors()->all()));
            $logger->error('Submitted data: ' . json_encode($fields));

            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
            ], 422);
        }


        try {

            DB::beginTransaction();

            $validatedData = $validator->validated();
            $post = Post::find($fields['post_id']);

            if (!$post->editable) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to update this post.'
                ], 403);
            }

            $post->update($validatedData);
            $new_tags = json_decode($request->tags, true);

            $tag_array = [];
            foreach ($new_tags as $new_tag) {

                $tag_exist = Tag::where('name', $new_tag)->first();;
                if ($tag_exist) {
                    $tag_array[] = $tag_exist->id;
                } else {
                    $tag = new Tag;
                    $tag->name = $new_tag;
                    $tag->save();

                    $tag_array[] = $tag->id;
                }
            }

            $new_tag_ids = array_merge($tag_array, json_decode($request->tag_ids, true));
            $merged = array_unique($new_tag_ids);

            $post->tags()->sync($merged);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Post updated successfully.',
                'data' => $post
            ], 200);
        } catch (\Exception | \PDOException | \Throwable $e) {

            DB::rollBack();

            $logger->error('Exception during registration: ' . $e->getMessage());
            $logger->error('Submitted data: ' . json_encode($fields));

            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
                'message' => 'Failed to create post.',
            ], 500);
        }
    }

    public function details(Request $request)
    {

        $logger = Log::channel('user');
        $fields = $request->all();
        $validator = Validator::make(
            $request->all(),
            [
                'post_id'   =>  'required|exists:posts,id'
            ],
            [
                'post_id.exists'    =>  "Post Id doesn't exists."
            ]
        );

        if ($validator->fails()) {
            $logger->error('Validation failed: ' . json_encode($validator->errors()->all()));
            $logger->error('Submitted data: ' . json_encode($fields));

            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        try {

            $post = Post::with(['user', 'tags', 'comments' => function ($query) {
                $query->with('user')->orderBy('is_pinned', 'desc')->orderBy('created_at', 'desc');
            }])->find($fields['post_id']);

            return response()->json([
                'success' => true,
                'message' => 'Post fetched successfully.',
                'data' => $post
            ], 200);
        } catch (\Exception | \PDOException | \Throwable $e) {

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
        $validator = Validator::make(
            $request->all(),
            [
                'post_id'   =>  'required|exists:posts,id'
            ],
            [
                'post_id.exists'    =>  "Post Id doesn't exists."
            ]
        );

        if ($validator->fails()) {
            $logger->error('Validation failed: ' . json_encode($validator->errors()->all()));
            $logger->error('Submitted data: ' . json_encode($fields));

            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
            ], 422);
        }


        try {

            $post = Post::find($fields['post_id']);

            if (!$post->editable) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to delete this post.'
                ], 403);
            }

            $post->delete();

            return response()->json([
                'success' => true,
                'message' => 'Post deleted successfully.',
            ], 200);
        
            
        } catch (\Exception | \PDOException | \Throwable $e) {

            $logger->error('Exception during registration: ' . $e->getMessage());
            $logger->error('Submitted data: ' . json_encode($fields));

            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
                'message' => 'Failed to create post.',
            ], 500);
        }
    }
}
