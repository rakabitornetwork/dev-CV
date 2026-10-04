<?php

namespace App\Http\Controllers;

use App\Support\CvPayload;
use Illuminate\View\View;

class LandingController extends Controller
{
    public function __invoke(): View
    {
        return view('landing', [
            'cv' => CvPayload::landing(),
        ]);
    }
}
