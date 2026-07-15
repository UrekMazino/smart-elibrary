<?php

namespace App\Http\Middleware;

use App\Services\AiSearchService;
use Closure;
use Illuminate\Http\Request;

/**
 * Hard gate for OPAC AI mode.
 *
 * Hiding the toggle in the UI is not a control — anyone can still type the URL. When the
 * admin switch is off, the AI routes must genuinely stop answering, the same way a `can:`
 * middleware makes a permission real rather than cosmetic.
 *
 * 404, not 403: when the feature is off it shouldn't exist at all, and a 403 would
 * advertise that there's an AI endpoint sitting there.
 */
class EnsureAiModeEnabled
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless(app(AiSearchService::class)->isEnabled(), 404);

        return $next($request);
    }
}
