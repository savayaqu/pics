<?php

namespace App\Models;


class Picture extends Model
{
    protected $fillable = [
      'name',
      'hash',
      'date',
      'size',
      'width',
      'height',
      'album_id'
    ];
    public function album() {
        return $this->belongsTo(Album::class);
    }
    public function tagPictures()
    {
        return $this->hasMany(TagPicture::class);
    }
    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'tag_pictures');
    }
    public function complaints()
    {
        return $this->hasMany(Complaint::class);
    }
}
