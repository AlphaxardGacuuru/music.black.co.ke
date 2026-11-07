<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AiAgents;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminAiAgentsController extends Controller
{
    public function __construct(protected AiAgents $ai) {}

    public function show(): JsonResponse
    {
        return response()
            ->json(['data' => $this->status()]);
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        $this->ai->setEnabled($request->boolean('enabled'));

        return response()
            ->json([
                'message' => $request->boolean('enabled') ? 'AI agents turned on' : 'AI agents turned off',
                'data' => $this->status(),
            ]);
    }

    /**
     * @return array{enabled: bool, configured: bool}
     */
    protected function status(): array
    {
        return [
            'enabled' => $this->ai->enabled(),
            'configured' => filled(config('ai.providers.'.config('ai.default').'.key')),
        ];
    }
}
