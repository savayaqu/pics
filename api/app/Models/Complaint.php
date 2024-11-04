<?php

namespace App\Models;

class Complaint extends Model
{
    protected $fillable = [
      'description',
      'complaint_type_id',
      'picture_id',
      'album_id',
      'about_user_id',
      'from_user_id',
        'status'
    ];
    public function complaintType()
    {
        return $this->belongsTo(ComplaintType::class);
    }
    public function picture()
    {
        return $this->belongsTo(Picture::class);
    }
    public function album()
    {
        return $this->belongsTo(Album::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }
    public function aboutUser()
    {
        return $this->belongsTo(User::class, 'about_user_id');
    }
}
