<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Poll;
use App\Models\PollVote;
use Illuminate\Http\Request;

class ApiPollVoteController extends Controller
{
    public function store(Request $request, string $token)
    {
        $poll = Poll::with('options')->where('secret_token', $token)->first();

        if (!$poll) {
            return response()->json(['message' => 'Poll not found.'], 404);
        }

        if ($poll->is_draft) {
            return response()->json(['message' => 'Poll not started.'], 403);
        }

        if ($poll->ends_at && now()->isAfter($poll->ends_at)) {
            return response()->json(['message' => 'Poll is closed.'], 403);
        }

        $data = $request->validate([
            'poll_option_id' => 'required|integer',
        ]);

        $optionExists = $poll->options->contains('id', $data['poll_option_id']);
        if (!$optionExists) {
            return response()->json(['message' => 'Invalid option.'], 422);
        }

        if (!$poll->allow_multiple_choices) {
            $alreadyVoted = PollVote::where('poll_id', $poll->id)
                ->where('user_id', $request->user()->id)
                ->exists();

            if ($alreadyVoted) {
                return response()->json(['message' => 'Already voted.'], 403);
            }
        }

        $vote = PollVote::create([
            'poll_id' => $poll->id,
            'user_id' => $request->user()->id,
            'poll_option_id' => $data['poll_option_id'],
        ]);

        return response()->json($vote, 201);
    }
}
