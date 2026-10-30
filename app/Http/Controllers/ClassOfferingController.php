<?php

namespace App\Http\Controllers;

use App\Models\ClassOffering;
use Illuminate\View\View;

class ClassOfferingController extends Controller
{
    public function index(): View
    {
        $classes = ClassOffering::active()
            ->ordered()
            ->with('media')
            ->get();

        return view('classes.index', compact('classes'));
    }

    public function show(ClassOffering $classOffering): View
    {
        if (! $classOffering->active) {
            abort(404);
        }

        $classOffering->load('media');

        return view('classes.show', ['class' => $classOffering]);
    }
}
