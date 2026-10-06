@php
    // Laravel's default text for a failed permission check isn't helpful; explain it instead.
    $raw = isset($exception) ? trim((string) $exception->getMessage()) : '';
    $message = ($raw === '' || $raw === 'This action is unauthorized.' || $raw === 'User does not have the right permissions.' || $raw === 'User does not have the right roles.')
        ? 'Your role doesn’t have permission to open this page or do this action.'
        : $raw;

    $user = auth()->user();
    $isStaff = $user && in_array($user->usertype, ['Administrator', 'Developer'], true);
    $startUrl = $isStaff ? \App\Http\Controllers\HomeController::adminLandingUrl($user) : url('/');
    $back = url()->previous();
    $showBack = $back && $back !== url()->current() && str_starts_with($back, url('/'));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Access denied · eLibrary</title>
    <style>
        :root { --ink: #22283b; --muted: #6b7489; --line: #e4e8f0; --brand: #6571ff; --bg: #f5f7fb; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
               background: var(--bg); color: var(--ink); font: 15px/1.55 -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; padding: 16px; }
        .card { background: #fff; border: 1px solid var(--line); border-radius: 14px; max-width: 460px; width: 100%;
                padding: 32px 28px; text-align: center; box-shadow: 0 6px 24px rgba(20, 30, 60, .06); }
        .icon { width: 56px; height: 56px; border-radius: 50%; background: #fff1f0; color: #d63939; display: inline-flex;
                align-items: center; justify-content: center; margin-bottom: 14px; }
        h1 { font-size: 20px; margin: 0 0 8px; }
        p { color: var(--muted); margin: 0 0 6px; }
        .hint { font-size: 13px; margin-top: 10px; }
        .actions { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; margin-top: 22px; }
        a.btn { display: inline-block; padding: 9px 16px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; }
        a.primary { background: var(--brand); color: #fff; }
        a.secondary { border: 1px solid var(--line); color: var(--ink); background: #fff; }
        a.btn:focus-visible { outline: 3px solid #b7bdff; outline-offset: 2px; }
    </style>
</head>
<body>
    <main class="card" role="main">
        <div class="icon" aria-hidden="true">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        </div>
        <h1>Access denied</h1>
        <p>{{ $message }}</p>
        @if($isStaff)
            <p class="hint">If you need this, ask a Super Administrator to grant your role the permission in <strong>Administration → Roles</strong>.</p>
        @endif
        <div class="actions">
            @if($showBack)
                <a class="btn secondary" href="{{ $back }}">← Go back</a>
            @endif
            <a class="btn primary" href="{{ $startUrl }}">{{ $isStaff ? 'Go to my start page' : 'Go to the homepage' }}</a>
        </div>
    </main>
</body>
</html>
