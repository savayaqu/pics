<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class TagPicture extends Pivot
{
    protected $table = 'tag_pictures';

    protected $fillable = [
        'tag_id',
        'picture_id',
    ];

    public function tag() {
        return $this->belongsTo(Tag::class);
    }
    public function picture() {
        return $this->belongsTo(Picture::class);
    }
}
