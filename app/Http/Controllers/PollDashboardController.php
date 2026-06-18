<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PollDashboardController extends Controller
{
    public function __invoke(Request $request) //car une seule () -> évite préciser méthode
    {
        $polls = $request->user()->polls()->with('options')->orderBy('created_at', 'desc')->get(); //2 requetes peu importe nb de polls

        //return vue & les données passées à vue
        return view('polls.dashboard', [
            'polls' => $polls,
        ]);
    }
}
