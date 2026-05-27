<?php

namespace App\Http\Controllers;

use App\Models\Song;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SongController extends Controller
{
    public function index()
    {
        $songs = Auth::user()
            ->songs()
            ->withCount('events')
            ->latest()
            ->get();

        return view('songs.index', [
            'songs' => $songs,
        ]);
    }

    public function store(Request $request)
    {
        $request->merge([
            'title' => trim((string) $request->input('title')),
        ]);

        $data = $request->validate([
            'title' => [
                'required',
                'string',
                'max:100',
                Rule::unique('songs', 'title')->where(fn ($query) => $query->where('user_id', Auth::id())),
            ],
            'bpm' => ['required', 'integer', 'min:60', 'max:200'],
            'events' => ['required', 'array', 'min:1'],
            'events.*.button_id' => ['required', 'integer', 'exists:buttons,id'],
            'events.*.time_ms' => ['required', 'integer', 'min:0'],
        ], [
            'title.unique' => 'Hai gia una canzone con questo nome. Scegli un nome diverso.',
        ]);

        $song = DB::transaction(function () use ($data) {
            $song = Song::create([
                'user_id' => Auth::id(),
                'title' => $data['title'],
                'bpm' => $data['bpm'],
            ]);

            $song->events()->createMany($data['events']);

            return $song;
        });

        return response()->json([
            'message' => 'Canzone salvata correttamente.',
            'redirect' => route('songs.index'),
            'song_id' => $song->id,
        ]);
    }

    public function update(Request $request, Song $song)
    {
        $this->authorizeSong($song);
        $request->merge([
            'title' => trim((string) $request->input('title')),
        ]);

        $data = $request->validate([
            'title' => [
                'required',
                'string',
                'max:100',
                Rule::unique('songs', 'title')
                    ->where(fn ($query) => $query->where('user_id', Auth::id()))
                    ->ignore($song->id),
            ],
        ], [
            'title.unique' => 'Hai gia una canzone con questo nome. Scegli un nome diverso.',
        ]);

        $song->update([
            'title' => $data['title'],
        ]);

        return redirect()->route('songs.index')->with('status', 'Canzone rinominata correttamente.');
    }

    public function destroy(Song $song)
    {
        $this->authorizeSong($song);
        $song->delete();

        return redirect()->route('songs.index')->with('status', 'Canzone eliminata correttamente.');
    }

    public function play(Song $song)
    {
        $this->authorizeSong($song);

        return redirect()->route('studio', [
            'song' => $song->id,
        ]);
    }

    private function authorizeSong(Song $song): void
    {
        if ((int) $song->user_id !== Auth::id()) {
            abort(403);
        }
    }
}
