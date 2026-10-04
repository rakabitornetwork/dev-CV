<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ContentRequest;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Project;
use App\Models\Skill;
use App\Models\SocialLink;
use App\Models\Testimonial;
use App\Support\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContentController extends Controller
{
    /**
     * @var array<string, class-string<Model>>
     */
    private const TYPES = [
        'social-links' => SocialLink::class,
        'experiences' => Experience::class,
        'educations' => Education::class,
        'skills' => Skill::class,
        'projects' => Project::class,
        'testimonials' => Testimonial::class,
    ];

    public function index(string $type): JsonResponse
    {
        $model = $this->model($type);

        return response()->json(
            $model::query()->ordered()->get()->map->present()->values(),
        );
    }

    public function store(ContentRequest $request, string $type): JsonResponse
    {
        $model = $this->model($type);
        $data = $this->attributes($request, $type);
        $data['sort_order'] = ((int) $model::query()->max('sort_order')) + 1;
        $data['is_visible'] = $request->boolean('is_visible', true);

        $record = $model::query()->create($data);

        return response()->json($record->present(), 201);
    }

    public function update(ContentRequest $request, string $type, int $id): JsonResponse
    {
        $record = $this->find($type, $id);
        $data = $this->attributes($request, $type, $record);
        $data['is_visible'] = $request->boolean('is_visible');
        $record->update($data);

        return response()->json($record->fresh()->present());
    }

    public function destroy(string $type, int $id): JsonResponse
    {
        $record = $this->find($type, $id);

        if ($record instanceof Project) {
            Media::delete($record->cover_path);
        }

        if ($record instanceof Testimonial) {
            Media::delete($record->avatar_path);
        }

        $record->delete();

        return response()->json(null, 204);
    }

    public function move(Request $request, string $type, int $id): JsonResponse
    {
        $data = $request->validate([
            'direction' => ['required', 'in:up,down'],
        ]);

        $record = $this->find($type, $id);
        $record->move($data['direction']);

        $model = $this->model($type);

        return response()->json(
            $model::query()->ordered()->get()->map->present()->values(),
        );
    }

    public function visibility(Request $request, string $type, int $id): JsonResponse
    {
        $request->validate([
            'is_visible' => ['required', 'boolean'],
        ]);

        $record = $this->find($type, $id);
        $record->update([
            'is_visible' => $request->boolean('is_visible'),
        ]);

        return response()->json($record->fresh()->present());
    }

    /**
     * @return class-string<Model>
     */
    private function model(string $type): string
    {
        abort_unless(isset(self::TYPES[$type]), 404);

        return self::TYPES[$type];
    }

    private function find(string $type, int $id): Model
    {
        $model = $this->model($type);

        return $model::query()->findOrFail($id);
    }

    /**
     * @return array<string, mixed>
     */
    private function attributes(ContentRequest $request, string $type, ?Model $current = null): array
    {
        $data = $request->safe()->except(['cover', 'avatar', 'is_visible']);

        if ($type === 'experiences') {
            $data['is_current'] = $request->boolean('is_current');
            $data['start_date'] = $data['start_date'].'-01';
            $data['end_date'] = $data['is_current'] || blank($data['end_date'] ?? null)
                ? null
                : $data['end_date'].'-01';
        }

        if ($type === 'projects' && $request->hasFile('cover')) {
            Media::delete($current instanceof Project ? $current->cover_path : null);
            $data['cover_path'] = Media::store($request->file('cover'), 'cv/projects');
        }

        if ($type === 'testimonials' && $request->hasFile('avatar')) {
            Media::delete($current instanceof Testimonial ? $current->avatar_path : null);
            $data['avatar_path'] = Media::store($request->file('avatar'), 'cv/avatars');
        }

        return $data;
    }
}
