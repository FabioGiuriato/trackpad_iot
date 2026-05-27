<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\LatestDeviceEventStore;

class DeviceApiController extends Controller
{
    public function latest()
    {
        return response()
            ->json([
                'event' => app(LatestDeviceEventStore::class)->latest(),
            ])
            ->header('Cache-Control', 'no-store');
    }
}
