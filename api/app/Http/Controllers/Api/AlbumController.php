<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\Api\ApiException;
use App\Http\Controllers\Controller;
use App\Models\Album;
use App\Models\Picture;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AlbumController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $albums = Album::where('user_id', $user->id)->get();
        if($albums->isEmpty())
        {
            throw new ApiException('Альбомы не найдены', 404);
        }
        return response($albums)->setStatusCode(200);
    }
    public function showPictures(Request $request, $album_id)
    {
        $user = Auth::user();
        $album = Album::where('id', $album_id)->first();
        if(!$album)
        {
            throw new ApiException('Альбом не найден', 404);
        }
        $pictures = Picture::without('album')->where('album_id', $album->id)->where('user_id', $user->id)->get();
        if($pictures->isEmpty())
        {
            throw new ApiException('Картинки в альбоме не найдены', 404);
        }
        return response()->json(['pictures' => $pictures])->setStatusCode(200);
    }
    public function create(Request $request)
    {
        $user = Auth::user();
        $name = $request->input('name');
        $input_path = $request->input('path');

        // Проверка, существует ли альбом с таким путём
        $exist_album = Album::where('path', $input_path)->where('user_id', $user->id)->first();

        if ($exist_album) {
            // Если альбом с таким путём уже существует, возвращаем его ID
            return response()->json([
                'message' => 'Альбом уже существует',
                'album' => $exist_album
            ])->setStatusCode(409);

        }

        // Проверка, существует ли альбом с таким именем
        $name_counter = 1;
        $original_name = $name;

        // Если существует альбом с таким именем, модифицируем имя
        while (Album::where('name', $name)->where('user_id', $user->id)->exists()) {
            $name_counter++;
            $name = $original_name . $name_counter;
        }

        // Создание нового альбома с уникальным именем
        $new_album = Album::create([
            'name' => $name,
            'path' => $input_path,
            'user_id' => $user->id
        ]);

        $album_id = $new_album->id;
        $path = $user->login . '/albums/' . $album_id;

        // Проверка и удаление директории, если она существует
        if (Storage::exists($path)) {
            Storage::deleteDirectory($path);
        }
        Storage::makeDirectory($path);

        // Формирование ответа
        return response()->json([
            'message' => 'Альбом создан',
            'album' => $new_album
        ])->setStatusCode(201);
    }

    public function destroy(Request $request, $album_id) {

        $album = Album::where('id', $album_id)->first();
        if(!$album) {
            throw new ApiException('Альбом не найден', 404);
        }
        //Проверка что папка принадлежит текущему пользователю
        $user = Auth::user();
        $files = Picture::where('album_id', $album->id)->get();
        if(Album::where('id', $album_id)->where('user_id',$user->id)->first()) {
            Storage::deleteDirectory($user->login.'/albums/'.$album_id);
            foreach ($files as $file) {
                Picture::where('id', $file->id)->delete();
            }
            Album::where('id', $album_id)->delete();
            return response()->json(['message' => 'Альбом удалён'])->setStatusCode(200);
        }
        else {
            throw new ApiException('Доступ запрещён', 403);
        }


    }
}
