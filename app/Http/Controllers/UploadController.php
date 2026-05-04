<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    public function image(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $file = $request->file('image');
        $name = Str::random(24) . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('uploads', $name, 'public');

        return response()->json([
            'success' => true,
            'data' => [
                'path' => $path,
                'url'  => Storage::url($path),
            ],
        ]);
    }
}
