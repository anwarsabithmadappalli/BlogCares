<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TagController
{
    public function index(Request $request){
        $logger = Log::channel('user');
        $fields = $request->all();
        $validator = Validator::make($request->all(), 
        [
            'limit'  =>  'required|numeric',
            'keyword'   =>  'nullable'
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
            
            $tags = Tag::where('name', 'LIKE', '%' . $fields['keyword'] . '%')
            ->orderBy('tags.created_at')->paginate($fields['limit']);

            if($tags){
                return response()->json([
                    'success' => true,
                    'message' => 'Tags fetched successfully.',
                    'data'  =>  $tags
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch tags.',
                ], 400);
            }
            

        } catch ( \Exception | \PDOException | \Throwable $e) {

            $logger->error('Exception during registration: ' . $e->getMessage());
            $logger->error('Submitted data: ' . json_encode($fields));

            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
                'message' => 'Failed to fetch tags.',
            ], 500);

        }

    }
    public function store(Request $request){
        $logger = Log::channel('user');
        $fields = $request->all();
        $validator = Validator::make($request->all(), 
        [
            'name'  =>  'required|string|min:3|max:100',
        ],
        [
            'name.required'  =>  'Name is required.'
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
            
            $tag = new Tag;
            $tag->name = $fields['name'];
            $tag->save();

            if($tag){
                return response()->json([
                    'success' => true,
                    'message' => 'Tags added successfully.',
                    'data'  =>  $tag
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to add tag.',
                ], 400);
            }
            

        } catch ( \Exception | \PDOException | \Throwable $e) {

            $logger->error('Exception during tag adding : ' . $e->getMessage());
            $logger->error('Submitted data: ' . json_encode($fields));

            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
                'message' => 'Failed to add tag.',
            ], 500);

        }
    }
    public function destroy(Request $request){
        $logger = Log::channel('user');
        $fields = $request->all();
        $validator = Validator::make($request->all(), 
        [
            'tag_id'  =>  'required|exists:tags,id',
        ],
        [
            'tag_id.exists'  =>  "Tag Id doesn't exists."
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
            
            $tag = Tag::find($fields['tag_id']);
            $tag->delete();

            if($tag){
                return response()->json([
                    'success' => true,
                    'message' => 'Tags deleted successfully.',
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete tag.',
                ], 400);
            }
            

        } catch ( \Exception | \PDOException | \Throwable $e) {

            $logger->error('Exception during tag delete : ' . $e->getMessage());
            $logger->error('Submitted data: ' . json_encode($fields));

            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
                'message' => 'Failed to delete tag.',
            ], 500);

        }
    }
}
