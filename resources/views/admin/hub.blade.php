@extends("admin.layouts.master")

{{-- A hub page of cards under Administration: Accounts (AdminController::accounts) and Utilities
     (MaterialTypeController::utilities). Each builds the cards this admin may use from their permissions. --}}

@section('css')
    <style>
      .ac-head { margin-bottom: 18px; }
      .ac-head h4 { font-weight: 700; color: #22283b; margin: 0; }
      .ac-head p  { color: #8a94a6; margin: 2px 0 0; font-size: 0.8125rem; }
      .util-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; }
      .util-card {
        display: flex; gap: 14px; align-items: flex-start; padding: 18px; border-radius: 12px;
        background: #fff; border: 1px solid #eef0f4; box-shadow: 0 1px 2px rgba(20, 30, 60, .04);
        color: inherit; text-decoration: none; transition: border-color .12s, box-shadow .12s, transform .12s;
      }
      .util-card:hover, .util-card:focus-visible { border-color: color-mix(in srgb, var(--t-primary) 47%, #fff); box-shadow: 0 4px 14px rgba(var(--t-primary-rgb), .12); transform: translateY(-1px); color: inherit; }
      .util-icon { flex: 0 0 42px; height: 42px; border-radius: 10px; background: color-mix(in srgb, var(--t-primary) 12%, #fff); color: var(--t-primary); display: flex; align-items: center; justify-content: center; }
      .util-title { font-weight: 700; color: #22283b; margin: 0 0 3px; font-size: 0.9375rem; }
      .util-text { color: #6b7489; font-size: 0.78125rem; margin: 0 0 8px; line-height: 1.45; }
      .util-meta { font-size: 0.71875rem; color: #8a94a6; }
      .util-empty { text-align: center; padding: 40px 20px; border: 1px dashed #d7dded; border-radius: 12px; color: #8a94a6; background: #fafbfd; }
    </style>
@endsection

@section('content')
<div class="page-content">
    <div class="ac-head">
        <h4>{{ $title }}</h4>
        <p>{{ $intro }}</p>
    </div>

    @if(count($cards))
        <div class="util-grid">
            @foreach($cards as $c)
                <a class="util-card" href="{{ $c['url'] }}">
                    <span class="util-icon"><i data-feather="{{ $c['icon'] }}"></i></span>
                    <span>
                        <p class="util-title">{{ $c['title'] }}</p>
                        <p class="util-text">{{ $c['text'] }}</p>
                        <span class="util-meta">{{ $c['meta'] }}</span>
                    </span>
                </a>
            @endforeach
        </div>
    @else
        <div class="util-empty">
            <i data-feather="lock" class="mb-2"></i>
            <h6 class="mb-1" style="color:#55606b;">No {{ strtolower($title) }} for your role yet</h6>
            <p class="mb-0" style="font-size:0.78125rem;">Ask a Super Administrator to grant your role the permission in Administration → Accounts → Roles.</p>
        </div>
    @endif
</div>
@endsection

@section('js')
<script>$(function () { if (window.feather) feather.replace(); });</script>
@endsection
