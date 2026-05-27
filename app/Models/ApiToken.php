<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ApiToken extends Model
{
    protected $table = 'api_tokens';

    protected $fillable = [
        'user_id',
        'name',
        'token_hash',
        'last_used_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public static function issueFor(User $user, ?string $name = null): array
    {
        $plainToken = 'tp_' . Str::random(80);

        $apiToken = self::create([
            'user_id' => $user->id,
            'name' => $name ?: 'android',
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addDays(30),
        ]);

        return [
            'plain_token' => $plainToken,
            'api_token' => $apiToken,
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
