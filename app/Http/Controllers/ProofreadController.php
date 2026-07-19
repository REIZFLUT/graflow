<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProofreadRequest;
use App\Services\ProofreadingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProofreadController extends Controller
{
    public function check(ProofreadRequest $request, ProofreadingService $service): JsonResponse
    {
        if (! $service->isConfigured()) {
            return response()->json([
                'message' => 'AI proofreading is not configured.',
                'reason' => 'not_configured',
            ], 503);
        }

        try {
            $issues = $service->check($request->text(), $request->language());
        } catch (Throwable $exception) {
            Log::warning('AI proofreading failed.', [
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'AI proofreading failed.',
                'reason' => 'failed',
            ], 502);
        }

        return response()->json([
            'issues' => $issues,
        ]);
    }
}
