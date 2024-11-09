<?php

namespace App\Models;

use App\Exceptions\Api\ApiException;
use App\Exceptions\Api\NotFoundException;

class Invitation extends Model
{
    protected $fillable = [
        'link',
        'expires_at',
        'album_id',
        'join_limit'
    ];

    public function checkExpires() {
        if (now()->greaterThan($this->expires_at)) {
            $this->delete();
            throw new ApiException('Invitation expired', 409);
        }
        if ($this->join_limit !== null &&
            $this->join_limit < 1) {
            $this->delete();
            throw new ApiException('Invitation expired', 409);
        }
    }

    public function album() {
        return $this->belongsTo(Album::class);
    }

    public function getRouteKeyName() {
        return 'link';
    }
}
