<?php

namespace App\Http\Resources;

use App\Models\Album;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AlbumResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $userIsOwner = $this->user_id === $user->id;
        return [
            'id'    => $this->id,
            'name'  => $this->name,
            'owner' => $this->whenLoaded('user', $this->when(!$userIsOwner, fn() => UserPublicResource::make($this->user))),
            $this->mergeWhen($userIsOwner, [
                'path'      => $this->whenNotNull($this->path),
                'createdAt' => $this->created_at,
                'grantAccessesCount'    => $this->whenCounted('usersViaAccess', fn($count) => $this->when($count, $count)),
                'grantAccesses'         => $this->whenLoaded ('usersViaAccess',
                    fn() => $this->when($this->usersViaAccess->isNotEmpty(), fn() => UserPublicResource::collection($this->usersViaAccess))
                ),
                'invitationsCount'      => $this->whenCounted('invitations', fn($count) => $this->when($count, $count)),
                'invitations'           => $this->whenLoaded ('invitations',
                    fn() => $this->when($this->invitations->isNotEmpty(), fn() => InvitationResource::collection($this->invitations))
                ),
            ]),
            'picturesCount'      => $this->whenCounted('pictures', fn($count) => $this->when($count, $count)),
            'picturesInfo' => $this->whenLoaded ('pictures',
                fn() => $this->when($this->pictures->isNotEmpty(), fn() => [
                    'sign' => Album::getSign($user, $this->id),
                    'ids' => $this->pictures->pluck('id'),
            ])),
        ];
    }
}
