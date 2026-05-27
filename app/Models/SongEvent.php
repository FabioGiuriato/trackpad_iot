<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SongEvent extends Model
{
    public $timestamps = false;

    protected $table = 'song_events';

    protected $fillable = [
        'song_id',
        'button_id',
        'time_ms',
    ];

    public function song()
    {
        return $this->belongsTo(Song::class);
    }

    public function button()
    {
        return $this->belongsTo(Button::class);
    }
}
