<?php

namespace App\Http\Controllers;

use App\Models\Button;
use App\Models\UserButtonMapping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ButtonMappingController extends Controller
{
    public function edit()
    {
        $user = Auth::user();
        $customizableType = config('trackpad.customizable_type');
        UserButtonMapping::ensureDefaultsFor($user);

        $mappings = $user->buttonMappings()
            ->with('button')
            ->whereBetween('slot', [1, 5])
            ->orderBy('slot')
            ->get()
            ->keyBy('slot');

        return view('buttons.mapping', [
            'mappings' => $mappings,
            'buttons' => Button::where('tipo', $customizableType)->orderBy('id')->get(),
            'typeLabel' => 'Troll',
        ]);
    }

    public function update(Request $request)
    {
        $customizableType = config('trackpad.customizable_type');
        $customButtonIds = Button::where('tipo', $customizableType)->pluck('id')->all();

        $data = $request->validate([
            'buttons' => ['required', 'array', 'size:5'],
            'buttons.1' => ['required', 'integer', Rule::in($customButtonIds)],
            'buttons.2' => ['required', 'integer', Rule::in($customButtonIds)],
            'buttons.3' => ['required', 'integer', Rule::in($customButtonIds)],
            'buttons.4' => ['required', 'integer', Rule::in($customButtonIds)],
            'buttons.5' => ['required', 'integer', Rule::in($customButtonIds)],
        ]);

        foreach ($data['buttons'] as $slot => $buttonId) {
            UserButtonMapping::updateOrCreate(
                [
                    'user_id' => Auth::id(),
                    'slot' => (int) $slot,
                ],
                [
                    'button_id' => $buttonId,
                ]
            );
        }

        return redirect()->route('buttons.mapping')->with('status', 'Mappatura pulsanti personalizzati aggiornata.');
    }

    public function reset()
    {
        $user = Auth::user();

        $user->buttonMappings()
            ->whereBetween('slot', [1, 5])
            ->delete();

        UserButtonMapping::ensureDefaultsFor($user);

        return redirect()->route('buttons.mapping')->with('status', 'Pulsanti personalizzati riportati allo standard.');
    }
}
