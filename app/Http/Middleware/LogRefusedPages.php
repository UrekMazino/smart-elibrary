<?php

namespace App\Http\Middleware;

use App\Services\SecurityLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * A page or action refused (403: a permission or role missing, or a rule such as "not your own account") goes into
 * the security log, with who asked, for what, and the reason shown, if any. The refusal itself is made elsewhere.
 */
class LogRefusedPages
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response->getStatusCode() === Response::HTTP_FORBIDDEN) {
            $reason = trim((string) ($response->exception?->getMessage() ?? ''));
            SecurityLog::record('access.denied', $request->user(), array_filter([
                'reason' => $reason !== '' && $reason !== 'This action is unauthorized.' ? Str::limit($reason, 160) : null,
            ]));
        }

        return $response;
    }
}
