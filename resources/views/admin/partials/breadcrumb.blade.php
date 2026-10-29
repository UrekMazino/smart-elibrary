{{-- The page's breadcrumbs, the same on every admin page: sidebar heading › menu page › … › this page.
     @include('admin.partials.breadcrumb', ['page' => 'news', 'below' => ['New Article']])
     page: a key of adminPages() (app/Http/helper.php); below: the pages under it, as adminTrail() takes them.
     A crumb links only when this account may open its page; the heading and this page never link. --}}
<nav class="page-breadcrumb {{ $class ?? '' }}" aria-label="Breadcrumb">
    <ol class="breadcrumb {{ $class ?? '' }}">
        @foreach(adminTrail($page, $below ?? []) as $crumb)
            @if($loop->last)
                <li class="breadcrumb-item active" aria-current="page">{{ $crumb['label'] }}</li>
            @elseif($crumb['submit'])
                <li class="breadcrumb-item"><a href="#" data-call="submitFormById" data-args='["{{ $crumb['submit'] }}"]'>{{ $crumb['label'] }}</a></li>
            @elseif($crumb['url'])
                <li class="breadcrumb-item"><a href="{{ $crumb['url'] }}">{{ $crumb['label'] }}</a></li>
            @else
                <li class="breadcrumb-item">{{ $crumb['label'] }}</li>
            @endif
        @endforeach
    </ol>
</nav>
