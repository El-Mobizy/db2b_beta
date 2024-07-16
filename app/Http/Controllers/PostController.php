<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->only(['title', 'body']);

        $post = Post::create($data);

        return response()->json($post, 201);
    }

    // READ - Tous les posts
    public function index()
    {
        $posts = Post::all();
        return response()->json($posts);
    }

    // READ - Un post par ID
    public function show($id)
    {
        $post = Post::find($id);
        if ($post) {
            return response()->json($post);
        }
        return response()->json(['message' => 'Post not found'], 404);
    }

    // UPDATE
    public function update(Request $request, $id)
    {
        $post = Post::find($id);
        if ($post) {
            $data = $request->only(['title', 'content']);
            $post->update($data);
            return response()->json($post);
        }
        return response()->json(['message' => 'Post not found'], 404);
    }

    // DELETE
    public function destroy($id)
    {
        $post = Post::find($id);
        if ($post) {
            $post->delete();
            return response()->json(['message' => 'Post deleted']);
        }
        return response()->json(['message' => 'Post not found'], 404);
    }
}
