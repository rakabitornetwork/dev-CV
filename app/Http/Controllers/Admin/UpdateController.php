<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Deployer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UpdateController extends Controller
{
    public function __construct(private Deployer $deployer) {}

    public function show(): JsonResponse
    {
        return response()->json($this->deployer->snapshot());
    }

    public function check(): JsonResponse
    {
        return response()->json($this->deployer->snapshot(fetch: true));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'confirm' => ['accepted'],
            'mode' => ['required', 'in:update,rebuild'],
        ]);

        return response()->json(
            $this->deployer->dispatch($data['mode'] === 'rebuild'),
            202,
        );
    }
}
