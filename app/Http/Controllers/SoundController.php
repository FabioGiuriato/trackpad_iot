<?php

namespace App\Http\Controllers;

use App\Models\Button;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SoundController extends Controller
{
    public function index()
    {
        $buttons = Button::orderBy('tipo')
            ->orderBy('id')
            ->get();

        return view('sounds.index', [
            'buttons' => $buttons,
            'musicTypes' => config('trackpad.types'),
        ]);
    }

    public function upload()
    {
        return view('sounds.upload', [
            'musicTypes' => config('trackpad.types'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tipo' => ['required', 'integer', 'in:' . implode(',', array_keys(config('trackpad.types')))],
            'sound' => ['required', 'file', 'mimes:mp3,wav,ogg', 'max:10240'],
        ]);

        Button::create([
            'tipo' => $data['tipo'],
            'sound_file' => $this->storeSoundFile($request->file('sound')),
        ]);

        return redirect()->route('sounds.index')->with('status', 'Suono caricato correttamente.');
    }

    public function update(Request $request, Button $button)
    {
        $data = $request->validate([
            'tipo' => ['required', 'integer', 'in:' . implode(',', array_keys(config('trackpad.types')))],
            'sound' => ['nullable', 'file', 'mimes:mp3,wav,ogg', 'max:10240'],
        ]);

        $button->tipo = $data['tipo'];

        if ($request->hasFile('sound')) {
            $this->deleteSoundFile($button->sound_file);
            $button->sound_file = $this->storeSoundFile($request->file('sound'));
        }

        $button->save();

        return back()->with('status', 'Suono aggiornato correttamente.');
    }

    public function destroy(Button $button)
    {
        try {
            $this->deleteSoundFile($button->sound_file);
            $button->delete();
        } catch (\Throwable $exception) {
            return back()->withErrors([
                'sound' => 'Non posso eliminare questo suono perche e usato da una canzone.',
            ]);
        }

        return back()->with('status', 'Suono eliminato correttamente.');
    }

    private function storeSoundFile($file): string
    {
        $name = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $extension = strtolower($file->getClientOriginalExtension());
        $filename = $name . '-' . time() . '.' . $extension;

        $file->move(public_path('sounds'), $filename);

        return 'sounds/' . $filename;
    }

    private function deleteSoundFile(?string $path): void
    {
        if (!$path || !Str::startsWith($path, 'sounds/')) {
            return;
        }

        File::delete(public_path($path));
    }
}
