<?php

namespace App\Support;

use App\Models\Education;
use App\Models\Experience;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Section;
use App\Models\Skill;
use App\Models\SocialLink;
use App\Models\Testimonial;

class CvPayload
{
    /**
     * Public landing data. Hidden sections and hidden items stay out.
     *
     * @return array<string, mixed>
     */
    public static function landing(): array
    {
        $profile = Profile::query()->first();

        return [
            'profile' => $profile?->present(),
            'sections' => Section::query()->visible()->ordered()->get()->map->present()->values(),
            'social_links' => SocialLink::query()->visible()->ordered()->get()->map->present()->values(),
            'experiences' => Experience::query()->visible()->ordered()->get()->map->present()->values(),
            'educations' => Education::query()->visible()->ordered()->get()->map->present()->values(),
            'skills' => Skill::query()->visible()->ordered()->get()->map->present()->values(),
            'projects' => Project::query()->visible()->ordered()->get()->map->present()->values(),
            'testimonials' => Testimonial::query()->visible()->ordered()->get()->map->present()->values(),
        ];
    }
}
