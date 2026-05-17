<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Poll;
use Illuminate\Http\Request;

class ApiPollController extends Controller
{
    /**
     * Display a listing of the authenticated user's polls.
     */
    public function index(Request $request)
    {
        $polls = $request->user()->polls()->with('options')->orderBy('created_at', 'desc')->get();

        return $polls;
    }

    public function store(Request $request)
    {
    $data = $request->validate([
        'question'               => 'required|string|max:255',
        'title'                  => 'nullable|string|max:255',
        'allow_multiple_choices' => 'sometimes|boolean',
        'results_public'         => 'sometimes|boolean',
        'duration'               => 'nullable|integer|min:1',
    ]);

    $poll = Poll::create([
        'user_id'                => $request->user()->id,
        'question'               => $data['question'],
        'title'                  => $data['title'] ?? null,
        'secret_token'           => \Illuminate\Support\Str::random(32),
        'is_draft'               => true,
        'allow_multiple_choices' => $data['allow_multiple_choices'] ?? false,
        'results_public'         => $data['results_public'] ?? false,
        'duration'               => $data['duration'] ?? null,
    ]);

        return response()->json($poll->load('options'), 201);
    }

    /**
     * Display the specified poll by its secret token.
     */
    public function show(string $token)
    {
        $poll = Poll::with(['options' => function ($query) {
            $query->withCount('votes');
        }])->where('secret_token', $token)->first();

        if (!$poll) {
            return response()->json(['message' => 'Poll not found.'], 404);
        }

        $user = auth('sanctum')->user();
        $poll->is_owner = $user && $user->id === $poll->user_id;

        return $poll;
    }

    /**
     * Remove the specified poll.
     */
    public function remove(Request $request, int $id)
    {
        $poll = Poll::where('id', $id)->where('user_id', $request->user()->id)->first();

        if (!$poll) {
            return response()->json(['message' => 'Poll not found.'], 404);
        }

        $poll->delete();

        return response()->json(['message' => 'success'], 200);
    }

    public function update(Request $request, int $id)
    {
        $poll = Poll::where('id', $id)->where('user_id', $request->user()->id)->first();

        if (!$poll) {
            return response()->json(['message' => 'Poll not found.'], 404);
        }

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
            $poll->ends_at = now()->addSeconds($poll->duration);
        }

        $poll->save();

        return response()->json($poll->load('options'));
    }
}
