<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Experience;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Skill;
use App\Models\Testimonial;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $profile = Profile::query()->first();

        return response()->json([
            'counts' => [
                'experiences' => Experience::query()->count(),
                'skills' => Skill::query()->count(),
                'projects' => Project::query()->count(),
                'testimonials' => Testimonial::query()->count(),
            ],
            'profile' => $profile ? [
                'name' => $profile->name,
                'headline' => $profile->headline,
            ] : null,
        ]);
    }
}
