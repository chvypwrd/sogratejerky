<?php

namespace App\Http\Controllers;

use App\Models\Instructor;
use Illuminate\View\View;

class InstructorController extends Controller
{
    public function index(): View
    {
        $instructors = Instructor::active()
            ->ordered()
            ->with('media')
            ->get();

        return view('instructors.index', compact('instructors'));
    }

    public function show(Instructor $instructor): View
    {
        if (! $instructor->active) {
            abort(404);
        }

        $instructor->load('media');

        return view('instructors.show', compact('instructor'));
    }
}
