<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Poll;
use Illuminate\Http\Request;

class ApiPollController extends Controller
{
    //return polls du usr connecté
    public function index(Request $request)
    {
        $polls = $request->user()->polls()->with('options')->orderBy('created_at', 'desc')->get();

        return $polls;
    }

    //créer un poll
    public function store(Request $request)
    {
    $data = $request->validate([
        'question'               => 'required|string|max:255',
        'title'                  => 'nullable|string|max:255', //peut etre null
        'allow_multiple_choices' => 'sometimes|boolean', //facultatif (si là = boolean)
        'results_public'         => 'sometimes|boolean',
        'duration'               => 'nullable|integer|min:1',
    ]);

    $poll = Poll::create([
        'user_id'                => $request->user()->id,
        'question'               => $data['question'],
        'title'                  => $data['title'] ?? null,
        'secret_token'           => \Illuminate\Support\Str::random(32), //token random par poll (remplace)
        'is_draft'               => true, //commence de base en brouillon
        'allow_multiple_choices' => $data['allow_multiple_choices'] ?? false,
        'results_public'         => $data['results_public'] ?? false,
        'duration'               => $data['duration'] ?? null,
    ]);

        return response()->json($poll->load('options'), 201); //return poll créé
    }

    //affichage du poll par token
    public function show(string $token)
    {
        $poll = Poll::with(['options' => function ($query) {
            $query->withCount('votes'); //charge aussi nb votes (votes_count sur chaque option)
        }])->where('secret_token', $token)->first();

        if (!$poll) {
            return response()->json(['message' => 'Poll not found.'], 404);
        }

        $user = auth('sanctum')->user();//recup usr mais pas request car peut ne pas être connecté (vote sans login)
                                        //(utile pour savoir si owner)
        $poll->is_owner = $user && $user->id === $poll->user_id; //affichage result au owner mm si resultats privés

        return $poll;
    }

    //supprimer poll
    public function remove(Request $request, int $id)
    {
        $poll = Poll::where('id', $id)->where('user_id', $request->user()->id)->first(); //double verif (existe & owner)

        if (!$poll) {
            return response()->json(['message' => 'Poll not found.'], 404);
        }

        $poll->delete();

        return response()->json(['message' => 'success'], 200);
    }

    //maj
    public function update(Request $request, int $id)
    {
        $poll = Poll::where('id', $id)->where('user_id', $request->user()->id)->first();

        if (!$poll) {
            return response()->json(['message' => 'Poll not found.'], 404);
        }

        //sometimes|required -> si champ envoyé = obligatoire
        // mais peut ne pas être envoyé (modif partiellement sans tout refaire)
        $data = $request->validate([
            'question' => 'sometimes|required|string|max:255',
            'title' => 'nullable|string|max:255',
            'allow_multiple_choices' => 'sometimes|boolean',
            'results_public' => 'sometimes|boolean',
            'duration' => 'nullable|integer|min:1',
        ]);

        $poll->update($data);

        return response()->json($poll->load('options'));
    }

    //lance draft
    public function start(Request $request, int $id)
    {
        $poll = Poll::where('id', $id)->where('user_id', $request->user()->id)->first();

        if (!$poll) {
            return response()->json(['message' => 'Poll not found.'], 404);
        }

        if (!$poll->is_draft) {
            return response()->json(['message' => 'Poll already started.'], 400);
        }

        $poll->is_draft = false;
        $poll->started_at = now();

        if ($poll->duration) {
            $poll->ends_at = now()->addSeconds($poll->duration); //calc date fin
        }

        $poll->save();

        return response()->json($poll->load('options'));
    }
}
