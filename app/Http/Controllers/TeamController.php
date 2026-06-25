<?php

namespace App\Http\Controllers;

use App\Models\CounselorProfile;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TeamController extends Controller
{
    public function index()
    {
        $counselors = CounselorProfile::visible()
            ->with('user:id,name')
            ->orderBy('display_order')
            ->get();

        return Inertia::render('Team', [
            'counselors' => $counselors
        ]);
    }
}
