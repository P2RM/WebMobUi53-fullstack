<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Poll;
use App\Models\PollOption;
use Illuminate\Http\Request;

class ApiPollOptionController extends Controller
{
    //créer option
    public function store(Request $request, int $pollId)
    {
        //double verif (existe & owner)
        $poll = Poll::where('id', $pollId)->where('user_id', $request->user()->id)->first();

        if (!$poll) {
            return response()->json(['message' => 'Poll not found.'], 404);
        }

        $data = $request->validate([
            'label' => 'required|string|max:255',
        ]);

        $option = $poll->options()->create($data); //créer option

        return response()->json($option, 201);
    }

    //maj option
    public function update(Request $request, int $pollId, int $optionId)
    {
        //double verif (existe & owner)
        $poll = Poll::where('id', $pollId)->where('user_id', $request->user()->id)->first();

        if (!$poll) {
            return response()->json(['message' => 'Poll not found.'], 404);
        }

        $option = PollOption::where('id', $optionId)->where('poll_id', $pollId)->first();

        if (!$option) {
            return response()->json(['message' => 'Option not found.'], 404);
        }

        $data = $request->validate([
            'label' => 'required|string|max:255',
        ]);

        $option->update($data);

        return response()->json($option);
    }

    //supprimer option
    public function destroy(Request $request, int $pollId, int $optionId)
    {
        //double verif (existe & owner)
        $poll = Poll::where('id', $pollId)->where('user_id', $request->user()->id)->first();

        if (!$poll) {
            return response()->json(['message' => 'Poll not found.'], 404);
        }

        $option = PollOption::where('id', $optionId)->where('poll_id', $pollId)->first();

        if (!$option) {
            return response()->json(['message' => 'Option not found.'], 404);
        }

        $option->delete();

        return response()->json(['message' => 'success']);
    }
}
