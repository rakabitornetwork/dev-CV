<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProfileRequest;
use App\Models\Profile;
use App\Support\Media;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json(Profile::query()->first()?->present());
    }

    public function update(ProfileRequest $request): JsonResponse
    {
        $profile = Profile::query()->first() ?? new Profile;
        $data = $request->safe()->except(['photo', 'cv_pdf']);

        if ($request->hasFile('photo')) {
            Media::delete($profile->photo_path);
            $data['photo_path'] = Media::store($request->file('photo'), 'cv');
        }

        if ($request->hasFile('cv_pdf')) {
            Media::delete($profile->cv_pdf_path);
            $data['cv_pdf_path'] = Media::store($request->file('cv_pdf'), 'cv');
        }

        $profile->fill($data)->save();

        return response()->json($profile->fresh()->present());
    }
}
