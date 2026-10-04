<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Section;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SectionController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            Section::query()->ordered()->get()->map->present()->values(),
        );
    }

    public function update(Request $request, Section $section): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:80'],
            'is_visible' => ['required', 'boolean'],
        ]);

        $section->update([
            'title' => $data['title'],
            'is_visible' => $request->boolean('is_visible'),
        ]);

        return response()->json($section->fresh()->present());
    }

    public function move(Request $request, Section $section): JsonResponse
    {
        $data = $request->validate([
            'direction' => ['required', 'in:up,down'],
        ]);

        $section->move($data['direction']);

        return response()->json(
            Section::query()->ordered()->get()->map->present()->values(),
        );
    }
}
