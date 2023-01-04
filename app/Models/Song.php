<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Song extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const FILE_UPLOAD = 1;

    public const FILE_ONLINE = 2;

    public $fillable = [
        "public_key",
        "slug",
        "name",
        "thumbnail",
        "lyrics",
        "total_time",
        "file_type",
        "media_file",
    ];

    public function singers()
    {
        return $this->belongsToMany(Singer::class,  SongSinger::class, 'song_id', 'singer_id');
    }

    public function getHref()
    {
        return route('music.musicDetail', [$this->slug, $this->public_key]);
    }

    public function getSingers()
    {
        return $this->singers->pluck('name')->implode(', ');
    }
}
