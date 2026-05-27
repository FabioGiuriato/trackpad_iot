<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserButtonMapping extends Model
{
    protected $table = 'user_button_mappings';

    protected $fillable = [
        'user_id',
        'slot',
        'button_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function button()
    {
        return $this->belongsTo(Button::class);
    }

    public static function ensureDefaultsFor(User $user): void
    {
        $customizableType = config('trackpad.customizable_type');

        $standardButtons = Button::where('tipo', $customizableType)
            ->orderBy('id')
            ->take(5)
            ->get();

        foreach ($standardButtons as $index => $button) {
            $mapping = self::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'slot' => $index + 1,
                ],
                [
                    'button_id' => $button->id,
                ]
            );

            if ((int) ($mapping->button?->tipo ?? $customizableType) !== (int) $customizableType) {
                $mapping->update([
                    'button_id' => $button->id,
                ]);
            }
        }
    }

    public static function buttonsFor(User $user)
    {
        self::ensureDefaultsFor($user);

        return self::with('button')
            ->where('user_id', $user->id)
            ->whereBetween('slot', [1, 5])
            ->orderBy('slot')
            ->get()
            ->pluck('button')
            ->filter(fn ($button) => $button && (int) $button->tipo === (int) config('trackpad.customizable_type'))
            ->values();
    }
}
