@extends("admin.layouts.master")

@section('css')
    <style>
        /* ---- Resources tab ---- */
        .res-panel { display: flex; gap: 16px; align-items: flex-start; }
        .res-thumb {
            flex: 0 0 96px; width: 96px; min-height: 120px; border: 1px solid #eef0f2; border-radius: 8px;
            background: #f7f9fc; display: flex; flex-direction: column; align-items: center;
            justify-content: center; padding: 8px; text-align: center;
        }
        /* The <img> ids/classes below are wired to cropper.js — don't rename them. */
        .res-thumb img { max-width: 100%; max-height: 104px; height: auto; border-radius: 3px; }
        .res-thumb-cover img { box-shadow: 0 1px 6px rgba(0,0,0,.18); }
        .res-none { font-size: 11px; color: #a3adc2; margin-top: 6px; }

        .res-meta { flex: 1; min-width: 0; }
        .res-meta dt {
            font-size: 10.5px; text-transform: uppercase; letter-spacing: .4px;
            color: #a3adc2; font-weight: 700; margin-bottom: 1px;
        }
        .res-meta dd { font-size: 12.5px; color: #33415c; margin-bottom: 8px; word-break: break-all; }
        .res-meta dd.mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 11.5px; }

        .res-label { font-size: 12px; font-weight: 600; color: #33415c; margin-bottom: 4px; display: block; }

        /* `.badge` prefixed by an id/class beats the template's `.badge { color:#fff }`. */
        .card-body .res-badge-ok   { background: #e8f6ef; color: #1c7c54; font-weight: 600; font-size: 10.5px; }
        .card-body .res-badge-none { background: #f4f5f7; color: #8a94a6; font-weight: 600; font-size: 10.5px; }
        .card-body .res-badge-auto { background: #fff5e0; color: #a06e14; font-weight: 600; font-size: 10.5px; }

        .even-row {
            background-color: #f2f2f2;
        }

        .odd-row {
            background-color: #ffffff; 
        }

        .delete-icon {
            color: red;
        }

        .delete-icon:hover {
            color: pink;
        }

        /* Count beside a tab label (e.g. Reviews 4). */
        .nav-tabs .tab-count { display: inline-block; min-width: 20px; margin-left: 4px; padding: 1px 6px; border-radius: 10px; background: #eef0f6; color: #5b6478; font-size: 11px; font-weight: 600; text-align: center; }
        .nav-tabs .nav-link.active .tab-count { background: #eef0ff; color: #4b55d9; }
    </style>
    <link rel="stylesheet" href="{{ asset('assets/css/cropper.min.css') }}">
@endsection

@section('content')
<div class="page-content">

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
        @php
            // Point the first crumb at the list this record actually lives in: published
            // records show in Record list, held ones (incl. freshly created records, which
            // default to is_published = 0) in On-hold list. Keeps the trail correct whether
            // you arrived from a list, or via the redirect after create / edit.
            $fromPublished = (int) optional($record)->is_published === 1;
            $fromUrl   = $fromPublished ? '/admin/record-list' : '/admin/on-hold-list';
            $fromLabel = $fromPublished ? 'Record list' : 'On-hold list';
        @endphp
        <nav class="page-breadcrumb mb-0">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ url($fromUrl) }}">{{ $fromLabel }}</a></li>
                <li class="breadcrumb-item">View details</li>
                <li class="breadcrumb-item active" aria-current="page">{{ $title }}</li>
            </ol>
        </nav>
        <div class="d-flex gap-2">
            {{-- One Action menu, like each row of the Record List and On-Hold List. --}}
            <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-info btn-sm dropdown-toggle" id="recordActionBtn" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Action</button>
                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="recordActionBtn">
                    @can('publish-hold.catalog')
                        <a class="dropdown-item" href="#" onclick="togglePublishOnhold({{ (int) $record->is_published }}, {{ $record->id }}); return false;">{{ $record->is_published ? 'Add to On-Hold list' : 'Add to Record list' }}</a>
                    @endcan
                    @can('edit.catalog')
                        <a class="dropdown-item" href="#" onclick="actionFrm({{ $record->id }}, 'edit'); return false;">Edit</a>
                    @endcan
                    <div class="dropdown-divider"></div>
                    <h6 class="dropdown-header">Export</h6>
                    <a class="dropdown-item" href="{{ route('export-record', ['id' => $record->id, 'format' => 'mrc']) }}">MARC21 binary (.mrc)</a>
                    <a class="dropdown-item" href="{{ route('export-record', ['id' => $record->id, 'format' => 'xml']) }}">MARCXML (.xml)</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="#" title="Queue this record for AI (RAG) re-indexing" onclick="document.getElementById('reindexFrm').submit(); return false;">Re-index for AI</a>
                    @can('delete.catalog')
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item text-danger" href="#" onclick="deleteRecord(); return false;">Delete</a>
                    @endcan
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="goBack()">
                <i data-feather="arrow-left" class="icon-sm"></i> Back
            </button>
        </div>
    </div>

    {{-- Flagged at the batch import and not reviewed yet (CatalogRecordCheck). --}}
    @if(!empty($importIssue))
        <div class="alert alert-warning py-2 mb-3" style="font-size: 13px;" role="alert">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                    <i data-feather="alert-triangle" class="icon-sm me-1"></i>
                    <strong>Flagged at import</strong> (batch #{{ $importIssue->catalog_import_id }}, record {{ $importIssue->record_no }} in the file).
                    Saving an edit checks it again.
                    <ul class="mb-0 mt-1 ps-4">
                        @foreach($importIssue->problems as $p)<li>{{ $p['message'] }}</li>@endforeach
                    </ul>
                </div>
                <div class="d-flex gap-2">
                    @can('view.catalog.importlist')
                        <a href="{{ route('batch-report', ['id' => $importIssue->catalog_import_id]) }}" class="btn btn-sm btn-outline-secondary">Import report</a>
                    @endcan
                    @can('edit.catalog')
                        <form method="post" action="{{ route('import-issue-reviewed', ['issue' => $importIssue->id]) }}">
                            @csrf
                            <input type="hidden" name="return_to" value="record">
                            <button type="submit" class="btn btn-sm btn-warning">Mark as reviewed</button>
                        </form>
                    @endcan
                </div>
            </div>
        </div>
    @endif

    @if(session('rag_reindexed'))
        <div class="alert alert-success alert-dismissible py-2 mb-3" style="font-size: 13px;" role="alert">
            <i data-feather="check-circle" class="icon-sm me-1"></i>
            Queued for AI re-indexing &mdash; it will be picked up on the next indexing run.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Details for:</span><h3> {!! $title !!} </h3>
                    @php $ragStatus = \App\Services\RagSync::statusFor($record->id, $record->is_published); @endphp
                    <span class="badge {{ $ragStatus['class'] }}" style="font-size: 11px;" title="AI (RAG) search index status">
                        <i data-feather="cpu" class="icon-sm" style="width:13px;height:13px;"></i> {{ $ragStatus['label'] }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-baseline mb-4 mb-md-3">
                        <h6 class="card-title mb-0">Record Information</h6>
                        {{-- Edit and publish / hold are in the Action menu at the top of the page. --}}
                    </div>
                    <ul class="nav nav-tabs" id="myTab" role="tablist">
                        <li class="nav-item">
                          <a class="nav-link {{ isset($uploadStatus) || isset($copyUpdate)  ? '' : 'active' }}" id="full-record-tab" data-bs-toggle="tab" href="#full-record" role="tab" aria-controls="full-record" aria-selected="true">Full Record</a>
                        </li>
                        <li class="nav-item">
                          <a class="nav-link" id="marc-record-tab" data-bs-toggle="tab" href="#marc-record" role="tab" aria-controls="marc-record" aria-selected="false">MARC Record</a>
                        </li>
                        <li class="nav-item">
                          <a class="nav-link" id="citation-tab" data-bs-toggle="tab" href="#citation" role="tab" aria-controls="citation" aria-selected="false">Citation</a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link" id="record-hist-tab" data-bs-toggle="tab" href="#record-hist" role="tab" aria-controls="record-hist" aria-selected="false">Record History</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ isset($uploadStatus) ? 'active' : '' }}" id="resources-tab" data-bs-toggle="tab" href="#resources" role="tab" aria-controls="resources" aria-selected="false">Resources</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ isset($copyUpdate) ? 'active' : '' }}" id="physical-copy-tab" data-bs-toggle="tab" href="#physical-copy" role="tab" aria-controls="physical-copy" aria-selected="false">Physical Copy</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="reviews-tab" data-bs-toggle="tab" href="#reviews" role="tab" aria-controls="reviews" aria-selected="false">
                                Reviews <span class="tab-count">{{ $reviews->count() }}</span>
                            </a>
                        </li>
                    </ul>
                    <div class="tab-content border border-top-0 p-3" id="myTabContent">

                    <!-- Full Record Tab -->
                    <div class="tab-pane fade {{ isset($uploadStatus) || isset($copyUpdate) ? '' : 'show active' }}" id="full-record" role="tabpanel" aria-labelledby="full-record-tab"></div>

                    <!-- MARC Record Tab -->
                    <div class="tab-pane fade" id="marc-record" role="tabpanel" aria-labelledby="marc-record-tab"></div>

                    <!-- Citation Tab -->
                    <div class="tab-pane fade" id="citation" role="tabpanel" aria-labelledby="citation-tab">
                        @forelse(($citations ?? []) as $style => $text)
                            <div class="row mb-1 {{ $loop->even ? 'even-row' : 'odd-row' }}">
                                <div class="p-2 col-lg-2 col-md-2 col-sm-12 text-left"><strong>{{ $style }}</strong></div>
                                <div class="p-2 col-lg-9 col-md-9 col-sm-10 text-left" id="cite-{{ $style }}">{!! $text !!}</div>
                                <div class="p-2 col-lg-1 col-md-1 col-sm-2 text-end">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" title="Copy" onclick="copyCitation('cite-{{ $style }}')"><i data-feather="copy" class="icon-sm"></i></button>
                                </div>
                            </div>
                        @empty
                            <div class="p-2 text-muted">No citation data available for this record.</div>
                        @endforelse
                    </div>

                    <!-- Record History Tab -->
                    <div class="tab-pane fade" id="record-hist" role="tabpanel" aria-labelledby="record-hist-tab"></div>

                    <!-- Record Resources Tab -->
                    <div class="tab-pane fade {{ isset($uploadStatus) ? 'show active' : '' }}" id="resources" role="tabpanel" aria-labelledby="resources-tab">
                        @isset($uploadError)
                            <div class="alert alert-danger py-2" style="font-size: 13px;">{{ $uploadError }}</div>
                        @endisset

                        @php
                            // Resolve the files on disk so the panel can state facts (size, date)
                            // rather than just "uploaded". A row can outlive its file.
                            $ftPath    = $fullText  ? public_path($fullText->FileLocation) : null;
                            $ftExists  = $ftPath && is_file($ftPath);
                            $ftUrl     = $fullText  ? asset($fullText->FileLocation) : null;

                            $cvPath    = $coverPage ? public_path($coverPage->FrontPageLocation) : null;
                            $cvExists  = $cvPath && is_file($cvPath);
                            $cvUrl     = $coverPage ? asset($coverPage->FrontPageLocation) : null;
                            // Placeholder covers are generated by the app (gen-*), not uploaded.
                            $cvIsAuto  = $coverPage && str_starts_with((string) $coverPage->FrontPageName, 'gen-');

                            $fmtSize = function ($bytes) {
                                if ($bytes === false || $bytes === null) return null;
                                foreach (['B','KB','MB','GB'] as $u) {
                                    if ($bytes < 1024) return round($bytes, $u === 'B' ? 0 : 1) . ' ' . $u;
                                    $bytes /= 1024;
                                }
                                return round($bytes, 1) . ' TB';
                            };
                        @endphp

                        <div class="row">
                            {{-- ---------------- Full text ---------------- --}}
                            <div class="col-lg-6 grid-margin stretch-card">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <h6 class="card-title mb-0">FULL TEXT</h6>
                                            @if($fullText)
                                                <span class="badge res-badge-ok rounded-pill">Attached</span>
                                            @else
                                                <span class="badge res-badge-none rounded-pill">Not attached</span>
                                            @endif
                                        </div>
                                        <p class="text-muted mb-3" style="font-size: 12.5px;">
                                            The PDF patrons read and download. One per record &mdash; uploading again replaces it.
                                        </p>

                                        <div class="res-panel">
                                            <div class="res-thumb">
                                                <img class="fulltext-view" id="full-text-img-view"
                                                     @if(is_null($fullText)) style="opacity: 0.25;" @endif
                                                     src="{{ is_null($fullText) ? asset('assets/images/empty-pdf.jfif') : asset('assets/images/pdf-placeholder.jfif') }}" alt="">
                                                @if(is_null($fullText))
                                                    <div id="full-text-no-upload" class="res-none">No file</div>
                                                @endif
                                            </div>

                                            <div class="res-meta">
                                                @if($fullText)
                                                    <dl class="mb-0">
                                                        <dt>File</dt>
                                                        <dd class="mono">{{ $fullText->FileName }}</dd>
                                                        <dt>Size</dt>
                                                        <dd>
                                                            @if($ftExists)
                                                                {{ $fmtSize(@filesize($ftPath)) }}
                                                            @else
                                                                <span class="text-danger">missing on disk</span>
                                                            @endif
                                                        </dd>
                                                        <dt>Uploaded</dt>
                                                        <dd class="mb-0">{{ $fullText->created_at ? \Carbon\Carbon::parse($fullText->created_at)->format('d M Y, g:i a') : '—' }}</dd>
                                                    </dl>
                                                @else
                                                    <p class="text-muted mb-0" style="font-size: 12.5px;">
                                                        No full text yet. Choose a PDF below to attach one.
                                                    </p>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- Actions --}}
                                        <div class="d-flex flex-wrap gap-2 mt-3">
                                            @if($fullText && $ftExists)
                                                {{-- Opens the browser's own PDF viewer in a new tab. --}}
                                                <a href="{{ $ftUrl }}" target="_blank" rel="noopener" class="btn btn-primary btn-sm">
                                                    <i data-feather="book-open" class="icon-sm me-1"></i>Read PDF
                                                </a>
                                                <a href="{{ $ftUrl }}" download="{{ $fullText->FileName }}" class="btn btn-outline-secondary btn-sm">
                                                    <i data-feather="download" class="icon-sm me-1"></i>Download
                                                </a>
                                            @elseif($fullText && ! $ftExists)
                                                <span class="text-danger" style="font-size: 12.5px;">
                                                    The record points to a file that isn&rsquo;t on disk &mdash; re-upload it.
                                                </span>
                                            @endif
                                        </div>

                                        @can('edit.catalog')
                                        <hr class="my-3">

                                        <label class="res-label" for="fulltextUpload">
                                            {{ $fullText ? 'Replace file' : 'Upload file' }} <span class="text-muted">(PDF, max 100 MB)</span>
                                        </label>
                                        <form action="{{ route('upload-fulltext') }}" method="POST" enctype="multipart/form-data">
                                            @csrf
                                            <input type="hidden" value="{{$record->id}}" name="catalog_id">
                                            <input type="hidden" value="{{ $title }}" name="title">
                                            <input class="form-control form-control-sm" type="file" id="fulltextUpload" name="fullText" accept="application/pdf,.pdf">
                                            {{-- cropper.js un-hides this once a valid PDF is chosen. --}}
                                            <button class="btn btn-info btn-sm d-none mt-2" id="fulltext-btn">
                                                <i data-feather="upload" class="icon-sm me-1"></i>Upload Fulltext
                                            </button>
                                        </form>

                                        @if(!is_null($fullText))
                                            <form action="{{ route('delete-fulltext') }}" method="POST" class="mt-2">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" value="{{$record->id}}" name="catalog_id">
                                                <input type="hidden" value="{{ $title }}" name="title">
                                                <button type="submit" class="btn btn-outline-danger btn-sm" id="delete-ft-btn">
                                                    <i data-feather="trash-2" class="icon-sm me-1"></i>Remove Fulltext
                                                </button>
                                            </form>
                                        @endif
                                        @endcan
                                    </div>
                                </div>
                            </div>

                            {{-- ---------------- Cover photo ---------------- --}}
                            <div class="col-lg-6 grid-margin stretch-card">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <h6 class="card-title mb-0">COVER PHOTO</h6>
                                            @if($cvIsAuto)
                                                <span class="badge res-badge-auto rounded-pill" title="Generated from the title">Placeholder on</span>
                                            @elseif($coverPage)
                                                <span class="badge res-badge-ok rounded-pill">Attached</span>
                                            @else
                                                <span class="badge res-badge-none rounded-pill">Not attached</span>
                                            @endif
                                        </div>
                                        <p class="text-muted mb-3" style="font-size: 12.5px;">
                                            Shown on the OPAC and in listings.
                                            @if($cvIsAuto)
                                                This placeholder was generated from the title &mdash; upload a real cover to replace it.
                                            @endif
                                        </p>
                                        @isset($coverNotice)
                                            <div class="alert alert-success py-2" style="font-size: 12.5px;" role="status">{{ $coverNotice }}</div>
                                        @endisset

                                        <div class="res-panel">
                                            <div class="res-thumb res-thumb-cover">
                                                {{-- .img-view + #cover-photo-img-view are what cropper.js swaps on file select. --}}
                                                <img class="img-view" id="cover-photo-img-view"
                                                     @if(is_null($coverPage)) style="opacity: 0.25;" @endif
                                                     src="{{ is_null($coverPage) ? asset('assets/images/placeholder.jpg') : $cvUrl }}" alt="">
                                                @if(is_null($coverPage))
                                                    <div id="cover-photo-no-upload" class="res-none">No image</div>
                                                @endif
                                            </div>

                                            <div class="res-meta">
                                                @if($coverPage)
                                                    <dl class="mb-0">
                                                        <dt>File</dt>
                                                        <dd class="mono">{{ $coverPage->FrontPageName }}</dd>
                                                        <dt>Size</dt>
                                                        <dd>
                                                            @if($cvExists)
                                                                {{ $fmtSize(@filesize($cvPath)) }}
                                                                @php $dim = @getimagesize($cvPath); @endphp
                                                                @if($dim) <span class="text-muted">&middot; {{ $dim[0] }}&times;{{ $dim[1] }}</span> @endif
                                                            @else
                                                                <span class="text-danger">missing on disk</span>
                                                            @endif
                                                        </dd>
                                                        <dt>{{ $cvIsAuto ? 'Generated' : 'Uploaded' }}</dt>
                                                        <dd class="mb-0">{{ $coverPage->created_at ? \Carbon\Carbon::parse($coverPage->created_at)->format('d M Y, g:i a') : '—' }}</dd>
                                                    </dl>
                                                @else
                                                    <p class="text-muted mb-0" style="font-size: 12.5px;">
                                                        No cover yet. Choose an image below to attach one.
                                                    </p>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- Actions --}}
                                        <div class="d-flex flex-wrap gap-2 mt-3">
                                            @if($coverPage && $cvExists)
                                                <button type="button" class="btn btn-primary btn-sm"
                                                        onclick="viewCoverPhoto()">
                                                    <i data-feather="image" class="icon-sm me-1"></i>View Coverphoto
                                                </button>
                                                <a href="{{ $cvUrl }}" target="_blank" rel="noopener" class="btn btn-outline-secondary btn-sm">
                                                    <i data-feather="external-link" class="icon-sm me-1"></i>Open in new tab
                                                </a>
                                            @elseif($coverPage && ! $cvExists)
                                                <span class="text-danger" style="font-size: 12.5px;">
                                                    The record points to an image that isn&rsquo;t on disk &mdash; re-upload it.
                                                </span>
                                            @endif
                                        </div>

                                        @can('edit.catalog')
                                        <hr class="my-3">

                                        @php
                                            $placeholdersOn  = settingEnabled('enable_placeholder_covers');   // Settings: all records
                                            $placeholderPref = (int) ($record->cover_placeholder ?? 1) === 1;  // this record
                                            $hasRealCover    = $coverPage && ! $cvIsAuto;
                                        @endphp
                                        {{-- Placeholder switch: off is remembered, so no generator brings it back. --}}
                                        <form method="POST" action="{{ route('cover-placeholder', ['id' => $record->id]) }}" id="placeholderFrm" class="mb-3">
                                            @csrf
                                            <input type="hidden" name="title" value="{{ $title }}">
                                            <input type="hidden" name="on" id="placeholderOn" value="{{ $placeholderPref ? 1 : 0 }}">
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" role="switch" id="placeholderSwitch"
                                                       @checked($placeholderPref) @disabled(! $placeholdersOn)
                                                       onchange="document.getElementById('placeholderOn').value = this.checked ? 1 : 0; if (window.appBusy) appBusy.show(); this.form.submit();">
                                                <label class="form-check-label" for="placeholderSwitch">Placeholder cover</label>
                                            </div>
                                            <div class="text-muted" style="font-size: 12px;">
                                                @if(! $placeholdersOn)
                                                    Placeholder covers are turned off for all records in Settings.
                                                @elseif($hasRealCover)
                                                    Not shown while a real cover is attached{{ $placeholderPref ? '; it comes back if that cover is removed' : '' }}.
                                                @elseif($placeholderPref)
                                                    A cover generated from the title is shown until a real one is uploaded.
                                                @else
                                                    Off: this record shows no cover until a real one is uploaded.
                                                @endif
                                            </div>
                                        </form>

                                        <label class="res-label" for="coverUpload">
                                            {{ $hasRealCover ? 'Replace image' : 'Upload image' }} <span class="text-muted">(JPG/PNG, max 10 MB)</span>
                                        </label>
                                        <form action="{{ route('upload-coverphoto') }}" method="POST" enctype="multipart/form-data">
                                            @csrf
                                            <input type="hidden" value="{{$record->id}}" name="catalog_id">
                                            <input type="hidden" value="{{ $title }}" name="title">
                                            <input class="form-control form-control-sm" type="file" id="coverUpload" name="coverImage" accept="image/jpeg,image/png">
                                            {{-- cropper.js un-hides this once a valid image is chosen. --}}
                                            <button class="btn btn-info btn-sm d-none mt-2" id="cover-photo-btn">
                                                <i data-feather="upload" class="icon-sm me-1"></i>Save Cover Photo
                                            </button>
                                        </form>

                                        {{-- A placeholder is turned off with the switch above; this removes a real cover. --}}
                                        @if($hasRealCover)
                                            <form action="{{ route('delete-coverphoto') }}" method="POST" class="mt-2">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" value="{{$record->id}}" name="catalog_id">
                                                <input type="hidden" value="{{ $title }}" name="title">
                                                <button type="submit" class="btn btn-outline-danger btn-sm" id="delete-dp-btn">
                                                    <i data-feather="trash-2" class="icon-sm me-1"></i>Remove Cover Photo
                                                </button>
                                            </form>
                                        @endif
                                        @endcan
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Cover photo lightbox --}}
                    <div class="modal fade" id="coverPhotoModal" tabindex="-1" aria-labelledby="coverPhotoModalLabel" aria-hidden="true">
                      <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                          <div class="modal-header">
                            <h6 class="modal-title" id="coverPhotoModalLabel">Cover photo</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                          </div>
                          <div class="modal-body text-center" style="background:#f7f9fc;">
                            <img id="coverPhotoModalImg" src="" alt="Cover photo"
                                 style="max-width:100%; max-height:70vh; border-radius:4px; box-shadow:0 2px 12px rgba(0,0,0,.18);">
                          </div>
                          @if($coverPage && $cvExists)
                          <div class="modal-footer">
                            <a href="{{ $cvUrl }}" target="_blank" rel="noopener" class="btn btn-outline-secondary btn-sm">Open in new tab</a>
                            <button type="button" class="btn btn-primary btn-sm" data-bs-dismiss="modal">Close</button>
                          </div>
                          @endif
                        </div>
                      </div>
                    </div>

                    <!-- Reviews Tab -->
                    <div class="tab-pane fade" id="reviews" role="tabpanel" aria-labelledby="reviews-tab">
                        @include('admin.partials.record-reviews', ['reviews' => $reviews])
                    </div>

                    <!-- Physical Copy Tab -->
                    <div class="tab-pane fade {{ isset($copyUpdate) ? 'show active' : '' }}" id="physical-copy" role="tabpanel" aria-labelledby="physical-copy-tab">
                        @if(!empty($copyError))
                            <div class="alert alert-danger py-2" style="font-size: 13px;" role="alert">{{ $copyError }}</div>
                        @endif
                        <div class="row">
                            <div class="col-md-12 stretch-card">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-2 text-center mb-2">
                                                <h5 class="text-muted" id="physical-count">Physical Count: {{ isset($totalCopies) ? $totalCopies : 'N/A' }}</h5>
                                            </div>
                                            <div class="col-md-1"></div>
                                            <div class="col-md-1 text-center mb-2">
                                                <button type="button" onclick="searchCopy()" class="btn btn-sm btn-info" id="copy-search-btn">Search</button>
                                            </div>
                                            <div class="col-md-5 text-center mb-2">
                                                <input type="text" class="form-control" id="copy-search-input">
                                            </div>
                                            <div class="col-md-1"></div>
                                            <div class="col-md-2 float-right text-center mb-2">
                                                
                                                @can('edit.catalog')
                                                <a href="#" type="button" onclick="showAddCopyModal()" class="btn btn-sm btn-outline-primary">+ Copy/ies</a>
                                                @endcan
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 grid-margin stretch-card">
                                <div class="card">
                                    <div class="card-body">
                                        
                                        <div class="table-responsive">
                                            <table class="table" id="copyTbl" style="width: 100%;">
                                                <thead>
                                                    <tr>
                                                        <th>#</th>
                                                        <th>CIRCULATION NO.</th>
                                                        <th>STATUS</th>
                                                        <th>SOURCE</th>
                                                        <th>LOCATION</th>
                                                        <th>ACTION</th>
                                                    </tr>
                                                </thead>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                    
                        </div>
                        <!-- add-copy-modal -->
                        @include('admin.modals.add-copy-modal')
                        <!-- edit-copy-modal -->
                        @include('admin.modals.edit-copy-modal')

                    </div>
                    
                    @php 
                        $materialData =  getMaterialData($record->material_type_id);
                        $materialName = $materialData->name;
                        $createdBy = getPatronInfo($record->created_by, 'name');
                        $updatedBy = getPatronInfo($record->updated_by, 'name');
                    @endphp 
                    <input type="hidden" id="catalog-id" value="{{ $record->id }}">
                    <input type="hidden" id="fields_data" value="{{ $record->field_data }}">
                    <input type="hidden" id="record" value="{{ $record }}">
                    <input type="hidden" id="material_name" value="{{ $materialName }}">
                    <input type="hidden" id="created_by" value="{{ $createdBy }}">
                    <input type="hidden" id="updated_by" value="{{ $updatedBy }}">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<form method="post" action="{{ url('/admin/publish-to-opac') }}" id="publishFrm">
	@csrf
	<input type="hidden" name="catalog_id" id="publish_catalog_id">
</form>
<form method="post" action="{{ url('/admin/hold-view-from-opac') }}" id="holdFrm">
	@csrf
	<input type="hidden" name="catalog_id" id="hold_catalog_id">
</form>
{{-- Behind the Action menu's "Re-index for AI" and "Delete". --}}
<form method="POST" action="{{ route('reindex-record', ['id' => $record->id]) }}" id="reindexFrm" class="d-none">
	@csrf
</form>
@can('delete.catalog')
<form method="POST" action="{{ route('delete-record', ['id' => $record->id]) }}" id="deleteRecordFrm" class="d-none">
	@csrf
	@method('DELETE')
	<input type="hidden" name="return_to" value="list">
</form>
@endcan

<form method="post" action="" id="delete_copy_frm">
	@csrf
    @method('DELETE')
    <input type="hidden" value="{{ $record->id }}" name="catalog_id">
    <input type="hidden" value="{{ $title }}" name="title" id="hidden-title">

</form>
@endsection
@section('js')
<!-- <script src="{{ asset('js/admin/citation-js/index.js') }}"></script> -->
<!-- Include @citation-js/core library -->
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/DataTables-1.12.1/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/DataTables-1.12.1/js/dataTables.bootstrap5.min.js') }}"></script>
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/buttons/js/dataTables.buttons.min.js') }}"></script>
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/buttons/js/buttons.bootstrap5.min.js') }}"></script>

<script>
// Return to the list the admin came from (record-list or on-hold-list);
// fall back to the record list if there's no history to go back to.
function goBack() {
    if (document.referrer && window.history.length > 1) {
        window.history.back();
    } else {
        window.location.href = "{{ url('/admin/record-list') }}";
    }
}

// Action menu > Delete. The record leaves the OPAC, the search index and the AI index; the
// page then returns to the list the record was in.
function deleteRecord() {
    Swal.fire({
        title: 'Delete this record?',
        text: 'It is removed from the OPAC, the search index and the AI index.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonClass: 'me-2',
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then(function (result) {
        if (result.value) {
            if (window.appBusy) appBusy.show();
            document.getElementById('deleteRecordFrm').submit();
        }
    });
}

function copyCitation(id) {
    var el = document.getElementById(id);
    if (!el || !navigator.clipboard) return;
    navigator.clipboard.writeText(el.innerText.trim()).then(function () {
        if (window.Swal) Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Citation copied', showConfirmButton: false, timer: 1200 });
    });
}

// Cover photo lightbox. Reads the src off the on-page thumbnail rather than a baked-in
// URL, so it shows whatever is currently displayed — including a freshly picked file
// that cropper.js has previewed but not uploaded yet.
function viewCoverPhoto() {
    var thumb = document.getElementById('cover-photo-img-view');
    var modalImg = document.getElementById('coverPhotoModalImg');
    if (!thumb || !modalImg) return;
    modalImg.src = thumb.src;
    if (window.bootstrap) {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('coverPhotoModal')).show();
    } else {
        $('#coverPhotoModal').modal('show');
    }
}

$(function () {
    if (window.feather) feather.replace();

    // Tabs are linkable: /admin/view-record/{id}#reviews opens that tab, and switching tabs
    // updates the hash so a reload (or a shared link) comes back to the same tab.
    // This page is usually reached by a POST (record lists, uploads, copy edits), whose URL has no
    // GET route — rewriting the address there and reloading gave a 405. So the address bar always
    // shows the record's own GET URL; a reload then re-opens the record instead of re-posting.
    var recordUrl = @json(parse_url(url('/admin/view-record/' . $record->id), PHP_URL_PATH));   // path only: always same-origin
    function setAddress(hash) {
        try { history.replaceState(null, '', recordUrl + (hash || '')); } catch (e) {}
    }
    setAddress(location.hash);
    var tabs = document.querySelectorAll('#myTab a[data-bs-toggle="tab"]');
    tabs.forEach(function (a) {
        if (location.hash && a.getAttribute('href') === location.hash && window.bootstrap) bootstrap.Tab.getOrCreateInstance(a).show();
        a.addEventListener('shown.bs.tab', function () { setAddress(a.getAttribute('href')); });
    });
});
</script>
<script type="text/javascript" src="{{ asset('assets/js/cropper.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/js/cropper.min.js') }}"></script>
<script> let pdfDefaultThumb = "{{ asset('assets/images/pdf-placeholder.jfif') }}";</script>
<script> let copyTblUrl = "{{ url('/admin/copy-list-json') }}";</script>
<script src="{{ asset('js/admin/catalog-view-record.js') }}?v={{ filemtime(public_path('js/admin/catalog-view-record.js')) }}"></script>
<script>
    $(document).ready(function(){
        let searchValue = "";
        let catalogId = $('#catalog-id').val();
        $.ajax({
            type: 'GET',
            url: copyTblUrl,
            data: {
                "catalog_id": catalogId,
                "search_value": searchValue
            },
            dataType: 'json',
            success: function(res) {
                // Check if the response data is in the expected format
            
                if (res && res.aaData) {
                    let copyListArr = $.map(res.aaData, function(value, key) {
                        return [value];
                    });
                    
                    // Prepare the data in the expected format for DataTables
                    let data = copyListArr.map(record => ({
                        "id": record.id,
                        "circulation_number": record.circulation_number,
                        "status_name": record.status_name,
                        "source": record.source,
                        "location": record.location,
                    }));

                    // DataTable initialization
                    $('#copyTbl').DataTable({
                        searching: false,
                        data: data,
                        columns: [
                            { data: 'id', orderable: false },
                            { data: 'circulation_number', orderable: false },
                            { data: 'status_name', orderable: false },
                            { data: 'source', orderable: false },
                            { data: 'location', orderable: false },
                            { 
                                "data": function (row, type, val, meta) {
                                    if (!@json(auth()->user()->can('edit.catalog'))) return '<span class="text-muted small">—</span>';   // copies are part of editing the record
                                    return '<div class="btn-group" role="group"><button id="btnGroupDrop1" type="button" class="btn btn-info dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Action</button><div class="dropdown-menu" aria-labelledby="btnGroupDrop1"><a class="dropdown-item" href="#" onclick="showEditCopyModal('+row.id+')">Edit</a><a class="dropdown-item" href="#" onclick="actionFrm('+row.id+',\'delete\')">Delete</a></div></div>';
                                }, orderable: false
                            },
                        ]
                    });
                } else {
                    console.log("Data format error or empty data.");
                }
            },
            error: function(xhr, status, error) {
                // More specific error handling to identify the issue
                console.error("Error:", status, error);
            }
        });

    });

    function searchCopy() {
        let searchValue = $("#copy-search-input").val();
        let catalogId = $('#catalog-id').val();
        $.ajax({
            type: 'GET',
            url: copyTblUrl,
            data: {
                "catalog_id": catalogId,
                "search_value": searchValue
            },
            dataType: 'json',
            success: function(res) {
                // Check if the response data is in the expected format
            
                if (res && res.aaData) {
                    let copyListArr = $.map(res.aaData, function(value, key) {
                        return [value];
                    });
                    
                    // Prepare the data in the expected format for DataTables
                    let data = copyListArr.map(record => ({
                        "id": record.id,
                        "circulation_number": record.circulation_number,
                        "status_name": record.status_name,
                        "source": record.source,
                        "location": record.location,
                    }));

                    let table = $('#copyTbl').DataTable();
                    table.destroy();
                    // DataTable initialization

                    $('#copyTbl').DataTable({
                        searching: false,
                        data: data,
                        columns: [
                            { data: 'id', orderable: false },
                            { data: 'circulation_number', orderable: false },
                            { data: 'status_name', orderable: false },
                            { data: 'source', orderable: false },
                            { data: 'location', orderable: false },
                            { 
                                "data": function (row, type, val, meta) {
                                    if (!@json(auth()->user()->can('edit.catalog'))) return '<span class="text-muted small">—</span>';   // copies are part of editing the record
                                    return '<div class="btn-group" role="group"><button id="btnGroupDrop1" type="button" class="btn btn-info dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Action</button><div class="dropdown-menu" aria-labelledby="btnGroupDrop1"><a class="dropdown-item" href="#" onclick="showEditCopyModal('+row.id+')">Edit</a><a class="dropdown-item" href="#" onclick="actionFrm('+row.id+',\'delete\')">Delete</a></div></div>';
                                }, orderable: false
                            },
                        ]
                    });
                } else {
                    console.log("Data format error or empty data.");
                }
            },
            error: function(xhr, status, error) {
                // More specific error handling to identify the issue
                console.error("Error:", status, error);
            }
        });
    }

    function actionFrm(id,action)
    {
        Swal.fire({
            title: 'Are you sure?',
            text: "",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonClass: 'me-2',
            confirmButtonText: 'Yes',
            cancelButtonText: 'No',
            reverseButtons: true
            }).then((result) => {
            if (result.value) {
                appBusy.show();

                if(action == 'edit') {
                    let act = "{{ route('edit-record', ['id' => ':id']) }}".replace(':id', id);
                    window.location.href = act;
                }

                if(action == 'delete') {
                    let act = "{{ route('delete-copy', ['id' => ':id']) }}".replace(':id', id);

                    $("#delete_copy_frm").prop('action',act);
                    $("#delete_copy_frm").submit();
                }


                

            } else if (
                // Read more about handling dismissals
                result.dismiss === Swal.DismissReason.cancel
            ) {
                Swal.fire(
                'Cancelled',
                'Your data is safe!',
                'error'
                )
            }
        })

    }

    function togglePublishOnhold(action, id)
    {
        let title = "";
        let message = "";
        let formId = ""
        if(action == 1) {
            title = "Hold this record?";
            message = "This record will be hidden from OPAC view.";
            formId = "#holdFrm";
            catalogId = "#hold_catalog_id"
        } else {
            title = "Publish this record? ";
            message = "This record will be available to OPAC view.";
            formId = "#publishFrm";
            catalogId ="#publish_catalog_id";
        }
        Swal.fire({
            title: title,
            text: message,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonClass: 'me-2',
            confirmButtonText: 'Yes',
            cancelButtonText: 'No',
        }).then((result) => {
            if (result.value) {
                $(catalogId).val(id);
                $.ajax({
                    url: $(formId).attr('action'),
                    method: $(formId).attr('method'),
                    data: $(formId).serialize(),
                    success: function (response) {
                        Swal.fire(
                            'Success',
                            'The record has been updated successfully!',
                            'success'
                        );
                        setTimeout(() => {
                            location.reload();
                        }, 1000); // 2000 milliseconds = 2 seconds
                    },
                    error: function () {
                        Swal.fire(
                            'Error',
                            'An error occurred while updating the record.',
                            'error'
                        );
                    }
                });
            } else if (
                result.dismiss === Swal.DismissReason.cancel
            ) {
                Swal.fire(
                    'Cancelled',
                    'Data was not deleted!',
                    'error'
                );
            }
        });
    }
</script>
@endsection