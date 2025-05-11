<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CommentController
{

    public function index(Request $request){

        $logger = Log::channel('user');
        $fields = $request->all();
        $validator = Validator::make($request->all(), 
        [
            'limit'  =>  'required|numeric'
        ],
        [
            'limit.required'  =>  'Limit is required.'
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
            
            $comments = Comment::with('user', 'post')->where('comments.user_id', auth()->id())
            ->orderBy('comments.is_pinned', 'desc')->orderBy('comments.created_at', 'desc')->paginate($fields['limit']);

            if($comments){
                return response()->json([
                    'success' => true,
                    'message' => 'Comments fetched successfully.',
                    'data'  =>  $comments
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch comments.',
                ], 400);
            }
            

        } catch ( \Exception | \PDOException | \Throwable $e) {

            $logger->error('Exception during registration: ' . $e->getMessage());
            $logger->error('Submitted data: ' . json_encode($fields));

            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
                'message' => 'Failed to fetch comments.',
            ], 500);

        }

    }

    public function store(Request $request)
    {
        $logger = Log::channel('user');
        $fields = $request->all();
        $validator = Validator::make($request->all(), 
        [
            'comment'  =>  'required|string',
            'post_id'  =>  'required|exists:posts,id',
        ],
        [
            'comment.required'  =>  'Comment is required.',
            'post_id.exists'    =>  "Post Id doesn't exists."
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
            
             $comment = Comment::create([
                'body' => $fields['comment'],
                'post_id' => $fields['post_id'],
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Comment created successfully.',
                'data' => $comment
            ], 201);

        } catch ( \Exception | \PDOException | \Throwable $e) {

            $logger->error('Exception during registration: ' . $e->getMessage());
            $logger->error('Submitted data: ' . json_encode($fields));

            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
                'message' => 'Failed to create comment.',
            ], 500);

        }

    }

    public function update(Request $request)
    {
        $logger = Log::channel('user');
        $fields = $request->all();
        $validator = Validator::make($request->all(), 
        [
            'comment_id'  =>  'required|exists:comments,id',
            'comment'  =>  'required|string',
        ],
        [
            'comment_id.exists'    =>  "Comment Id doesn't exists.",
            'comment.required'  =>  'Comment is required.',
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
            
            $comment = Comment::find($fields['comment_id']);

            if (!$comment->editable) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to update this comment.'
                ], 403);
            }

            $comment->body = $fields['comment'];
            $result = $comment->save();
  
            if($result){
                return response()->json([
                    'success' => true,
                    'message' => 'Comment updated successfully.',
                ], 200);
            } else {
                return response()->json([
                    'success' => true,
                    'message' => 'Failed to update comment.',
                ], 400);
            }
            

        } catch ( \Exception | \PDOException | \Throwable $e) {

            $logger->error('Exception during registration: ' . $e->getMessage());
            $logger->error('Submitted data: ' . json_encode($fields));

            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
                'message' => 'Failed to update comment.',
            ], 500);

        }

    }

    public function details(Request $request)
    {
        $logger = Log::channel('user');
        $fields = $request->all();
        $validator = Validator::make($request->all(), 
        [
            'comment_id'  =>  'required|exists:comments,id',
        ],
        [
            'comment_id.exists'    =>  "Comment Id doesn't exists.",
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
            
            $comment = Comment::with('user','post.user')->find($fields['comment_id']);
             
            if($comment){
                return response()->json([
                    'success' => true,
                    'message' => 'Comment fetched successfully.',
                    'data' => $comment
                ], 200);
            } else {
                return response()->json([
                    'success' => true,
                    'message' => 'Failed to fetch comment.',
                ], 400);
            }
            

        } catch ( \Exception | \PDOException | \Throwable $e) {

            $logger->error('Exception during registration: ' . $e->getMessage());
            $logger->error('Submitted data: ' . json_encode($fields));

            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
                'message' => 'Failed to fetch comment.',
            ], 500);

        }

    }

    public function destroy(Request $request)
    {
        $logger = Log::channel('user');
        $fields = $request->all();
        $validator = Validator::make($request->all(), 
        [
            'comment_id'  =>  'required|exists:comments,id',
        ],
        [
            'comment_id.exists'    =>  "Comment Id doesn't exists.",
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
            
            $comment = Comment::find($fields['comment_id']);
             
            if (!$comment->editable) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to delete this comment.'
                ], 403);
            }

            if($comment){

                $comment->delete();
                
                return response()->json([
                    'success' => true,
                    'message' => 'Comment deleted successfully.',
                ], 200);
            } else {
                return response()->json([
                    'success' => true,
                    'message' => 'Failed to delete comment.',
                ], 400);
            }
            

        } catch ( \Exception | \PDOException | \Throwable $e) {

            $logger->error('Exception during registration: ' . $e->getMessage());
            $logger->error('Submitted data: ' . json_encode($fields));

            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
                'message' => 'Failed to delete comment.',
            ], 500);

        }

    }

    public function changePinStatus(Request $request)
    {
        $logger = Log::channel('user');
        $fields = $request->all();
        $validator = Validator::make($request->all(), 
        [
            'comment_id'  =>  'required|exists:comments,id',
            'pin_status'    =>  'required|in:0,1'
        ],
        [
            'comment_id.exists'    =>  "Comment Id doesn't exists.",
            'pin_status'    =>  "Pin status must be in 1,2."
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

            DB::beginTransaction();
                      
            $new_comment = Comment::find($fields['comment_id']);

            if($fields['pin_status'] == 1){

                $existing_pinned = Comment::where('is_pinned', '1')
                    ->where('post_id', $new_comment->post_id)
                    ->first();

                if ($existing_pinned && $existing_pinned->id !== $new_comment->id) {
                    $existing_pinned->is_pinned = '0';
                    $existing_pinned->save();
                }

            }

            $post = Post::find($new_comment->post_id);

            if (!$post->editable) {

                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to update comment pinning.'
                ], 403);
            }

            $new_comment->is_pinned = $fields['pin_status'];
            $new_comment->save();

            DB::commit();

            if($fields['pin_status'] == '1'){
                return response()->json([
                    'success' => true,
                    'message' => 'Comment pinned successfully.',
                    'data' => $new_comment
                ]);
            } else {
                 return response()->json([
                    'success' => true,
                    'message' => 'Comment unpinned successfully.',
                    'data' => $new_comment
                ]);
            }
            
        } catch ( \Exception | \PDOException | \Throwable $e) {

            DB::rollBack();

            $logger->error('Exception during registration: ' . $e->getMessage());
            $logger->error('Submitted data: ' . json_encode($fields));

            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
                'message' => 'Failed to update comment.',
            ], 500);

        }

    }
    

}
