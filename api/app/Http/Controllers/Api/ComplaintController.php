<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\Api\ApiException;
use App\Exceptions\Api\ForbiddenException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Complaint\ComplaintCreateRequest;
use App\Http\Requests\Api\Complaint\ComplaintUpdateRequest;
use App\Http\Resources\AlbumResource;
use App\Http\Resources\ComplaintResource;
use App\Models\Album;
use App\Models\Complaint;
use App\Models\Picture;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class ComplaintController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $status = $request->query('status'); // Получаем параметр status из запроса
        $sortBy = $request->query('sort', 'created_at');   // Сортировка по полю (по умолчанию дата)
        $orderBy = $request->has('reverse') ? 'desc' : 'asc'; // Направление сортировки
        $limit = intval($request->query('limit'));
        if (!$limit) {
            $limit = 30;
        }

        // Проверка валидации сортировки
        $allowedSortFields = [
            'id', 'description', 'status', 'album_id', 'picture_id', 'from_user_id',
            'about_user_id', 'complaint_type_id', 'created_at', 'updated_at'
        ];
        if (!in_array($sortBy, $allowedSortFields)) {
            throw new ApiException('Sort must be of the following types: ' . join(', ', $allowedSortFields), 400);
        }

        // Инициализация запроса на выборку жалоб
        $query = Complaint::with(['type', 'aboutUser', 'fromUser', 'picture', 'album'])
            ->orderBy($sortBy, $orderBy);

        // Фильтрация по статусу
        if ($request->has('status')) {
            if ($status === "null") {
                // Выбираем не рассмотренные записи
                $query->whereNull('status');
            } else {
                // Фильтруем по конкретному значению
                $query->where('status', $status);
            }
        }

        // Если пользователь не админ => выводим только его жалобы
        if ($user->role->code !== 'admin') {
            $query->where('from_user_id', $user->id);
        }

        // Получаем все жалобы в пагинированном виде
        $complaintsPage = $query->paginate($limit);

        // Группируем жалобы по альбому
        $groupedComplaints = $complaintsPage->getCollection()->groupBy(function ($complaint) {
            return $complaint->album ? $complaint->album->id : 'no_album'; // Группируем по album_id
        })->map(function ($complaints, $albumId) {
            $album = $complaints->first()->album;  // Берём альбом из первой жалобы в группе, если он есть
            return [
                'album' => $album ? AlbumResource::make($album) : null, // Используем AlbumResource
                'complaintsCount' => $complaints->count(), // Количество жалоб на этот альбом
                'complaints' => ComplaintResource::collection($complaints), // Жалобы, относящиеся к этому альбому
            ];
        });

        // Преобразуем результат в нужный формат для ответа
        return response()->json([
            'page'       => $complaintsPage->currentPage(),
            'limit'      => $complaintsPage->perPage(),
            'total'      => $complaintsPage->total(),
            'complaints'     => $groupedComplaints->values()->all(), // Возвращаем сгруппированные альбомы с жалобами
        ]);
    }


    public function storeToPicture(ComplaintCreateRequest $request, Album $album, Picture $picture): JsonResponse
    {
        $user = Auth::user();
        $isAccessible = $album->usersViaAccess()->where('user_id', $user->id)->exists();
        if(!$isAccessible)
            throw new ForbiddenException();

        $isExisted = Complaint
            ::where('from_user_id', $user->id)
            ->where('picture_id', $picture->id)
            ->exists();
        if ($isExisted)
            throw new ApiException('You are already complain to this picture', 409);

        Complaint::create([
            'picture_id'        => $picture->id,
            'album_id'          => $album->id,
            'description'       => $request->input('description'),
            'complaint_type_id' => $request->input('typeId'),
            'from_user_id'      => $user->id,
            'about_user_id'     => $album->user_id,
        ]);
        return response()->json(null, 204);
    }

    public function storeToAlbum(ComplaintCreateRequest $request, Album $album): JsonResponse
    {
        $user = Auth::user();
        $isAccessible = $album->usersViaAccess()->where('user_id', $user->id)->exists();
        if(!$isAccessible)
            throw new ForbiddenException();

        $isExisted = Complaint
            ::where('from_user_id', $user->id)
            ->where('album_id', $album->id)
            ->exists();
        if($isExisted)
            throw new ApiException('You are already complain to this album', 409);

        Complaint::create([
            'album_id'          => $album->id,
            'description'       => $request->input('description'),
            'complaint_type_id' => $request->input('typeId'),
            'from_user_id'      => $user->id,
            'about_user_id'     => $album->user_id,
        ]);
        return response()->json(null, 204);
    }

    public function updateBatch(Complaint $complaint, ComplaintUpdateRequest $request): JsonResponse
    {
        $complaints = Complaint::with(['type', 'aboutUser', 'fromUser', 'picture', 'album'])
            ->where('about_user_id', $complaint->about_user_id)
            ->where('album_id'     , $complaint->album_id)
            ->orWhere('picture_id' , $complaint->picture_id)
            ->get();
        foreach ($complaints as $complaint) {
            $complaint->status = $request->input('status');
            $complaint->save();
        }
        return response()->json(['complaints' => ComplaintResource::collection($complaints)]);
    }

    public function destroy(Complaint $complaint): JsonResponse
    {
        $user = Auth::user();
        if ($complaint->from_user_id !== $user->id)
            throw new ForbiddenException();

        $complaint->delete();
        return response()->json(null, 204);
    }
}
