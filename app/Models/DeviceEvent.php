<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceEvent extends Model
{
    public $timestamps = false;

    protected $table = 'device_events';

    protected $fillable = [
        'mqtt_topic',
        'potenziometro',
        'pot_percentuale',
        'volume',
        'levetta',
        'joystick_x_valore',
        'joystick_x_posizione',
        'joystick_click',
        'button1',
        'button2',
        'button3',
        'button4',
        'button5',
        'raw_payload',
    ];

    protected function casts(): array
    {
        return [
            'raw_payload' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
