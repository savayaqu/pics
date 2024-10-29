<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\Api\ApiException;
use App\Http\Controllers\Controller;
use App\Models\Album;
use App\Models\Picture;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PictureController extends Controller
{
    public function index(Request $request, $album_id)
    {
        $user = Auth::user();
        $album = Album::where('id', $album_id)->first();
        if(!$album)
        {
            throw new ApiException('Альбом не найден', 404);
        }
        $pictures = Picture::without('album')->where('album_id', $album->id)->get();
        if($pictures->isEmpty())
        {
            throw new ApiException('Картинки в альбоме не найдены', 404);
        }
        return response()->json(['pictures' => $pictures])->setStatusCode(200);
    }

    public function download($album_id, $picture_id, Request $request)
    {
        $user = User::where('remember_token', $request->token)->first();

        // Проверка на существование пользователя
        if (!$user) {
            throw new ApiException('Не авторизован', 401);
        }

        $album = Album::where('id', $album_id)->where('user_id', $user->id)->first();
        $picture = Picture::where('album_id', $album_id)->where('id', $picture_id)->where('user_id', $user->id)->first();

        if (!$album) {
            throw new ApiException('Альбом не найден', 404);
        }
        if (!$picture) {
            throw new ApiException('Картинка не найдена', 404);
        }

        return response()->file(Storage::download($picture->path))->setStatusCode(200);
    }

    public function create(Request $request, $album_id)
    {
        $user = Auth::user();
        $files = $request->file('pictures');
        $album = Album::where('id', $album_id)->where('user_id', $user->id)->first();

        $path = $user->login.'/albums/'.$album->id.'/pictures/';
        $responses = [];
        foreach ($files as $file) {
            $filename = $file->getClientOriginalName();
            $pictureHash = hash('xxh3', Storage::path($path.$filename));

            $picture = Picture::where('album_id', $album_id)->where('hash', $pictureHash)->first();
            if ($picture) {
                $responses['errored'][] = [
                    'name' => $filename,
                    'message' => 'already exist in this album'
                ];
                continue;
            }
            $file->storeAs($path, $filename);
            $sizes = getimagesize(Storage::path($path.$filename));

            $pictureDB = Picture::create([
                'name' => $filename,
                'hash' => $pictureHash,
                'date' => Carbon::createFromTimestamp(Storage::lastModified($path.$filename)),
                'size' => Storage::size($path.$filename),
                'width' => $sizes[0],
                'height' => $sizes[1],
                'album_id' => $album_id,
            ]);
            $responses['successful'][] = $pictureDB;
        }
        return response($responses);
    }
}
