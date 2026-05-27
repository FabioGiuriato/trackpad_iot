<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Button extends Model
{
    public $timestamps = false;

    protected $table = 'buttons';

    protected $fillable = [
        'sound_file',
        'tipo',
        'nome_tipo',
    ];

    protected static function booted(): void
    {
        static::saving(function (Button $button) {
            $button->nome_tipo = self::nameForType((int) $button->tipo);
        });
    }

    public static function nameForType(int $type): string
    {
        return config('trackpad.database_type_names')[$type] ?? 'troll';
    }

    public function getTypeNameAttribute(): string
    {
        return config('trackpad.types')[$this->tipo] ?? 'Tipo ' . $this->tipo;
    }

    public function events()
    {
        return $this->hasMany(SongEvent::class);
    }
}
