{{-- Quick review popup — a SweetAlert version of the /review form, for signed-in patrons
     (included once by opac.layout.master). Pages call:
       QuickReview.download(catalogId, title)    after a download starts
       QuickReview.startRead(catalogId, title)   when the reader opens
       QuickReview.endRead()                     when the reader closes (asks if it was open >= 1 min)
     It only opens when the patron hasn't reviewed the material yet and hasn't dismissed the
     popup for it before ("Not now" is remembered per patron + material in this browser).
     After a save it fires a `quickreview:saved` event on document. --}}
@php
    // Quick picks: one tap submits that review (its stars + text) straight away. Patrons who want
    // to say something else rate and write their own below. 'when' limits a pick to one
    // context ('download' or 'read').
    $qrTemplates = [
        ['text' => 'Just downloaded it, will read it later.', 'rating' => 5, 'when' => 'download'],
        ['text' => 'Informative material. Thank you!', 'rating' => 5],
        ['text' => 'Thank you for the free access.', 'rating' => 5],
        ['text' => 'Very useful for my research.', 'rating' => 5],
        ['text' => 'Clear and easy to understand.', 'rating' => 5],
        ['text' => 'Not what I was looking for, but it is a good material.', 'rating' => 5],
        ['text' => 'Helpful, but I wish it had more details.', 'rating' => 4],
    ];
@endphp
<style>
    .qr-container { z-index: 21000 !important; }   /* above the reader (20000) and Quick view (2001) */
    .qr { text-align: left; font-size: 14px; color: #333; }
    .qr-lead { margin: 0 0 2px; text-align: center; font-size: 13.5px; color: #6b7680; }
    .qr-material { margin: 0 0 12px; text-align: center; font-weight: 600; color: #22283b; line-height: 1.35;
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .qr-rate { display: flex; justify-content: center; gap: 4px; }
    .qr-star { position: relative; margin: 0; padding: 2px; line-height: 0; border-radius: 6px; cursor: pointer; }
    .qr-star input { position: absolute; width: 1px; height: 1px; opacity: 0; pointer-events: none; }
    .qr-star svg { width: 34px; height: 34px; fill: none; stroke: #c3cad3; stroke-width: 1.4; stroke-linejoin: round; transition: transform .12s, fill .12s, stroke .12s; }
    .qr-star.is-on svg { fill: #f5b301; stroke: #e0a100; }
    .qr-star:hover svg { transform: scale(1.1); }
    .qr-star:has(input:focus-visible) { outline: 2px solid #00a496; outline-offset: 1px; }
    .qr-rate-label { min-height: 18px; margin: 2px 0 12px; text-align: center; font-size: 12.5px; color: #8a94a6; }
    .qr-sub { margin: 0 0 8px; font-size: 12.5px; font-weight: 600; color: #6b7680; }
    .qr-chips { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 14px; }
    .qr-or { display: flex; align-items: center; gap: 10px; margin: 0 0 8px; font-size: 12.5px; color: #8a94a6; }
    .qr-or::before, .qr-or::after { content: ''; flex: 1; border-top: 1px solid #e6eaee; }
    .qr-chip { font: inherit; font-size: 13px; line-height: 1.3; text-align: left; color: #33404a; background: #fff;
        border: 1px solid #d5dde0; border-radius: 16px; padding: 6px 12px; cursor: pointer; transition: background .12s, border-color .12s; }
    .qr-chip:hover { border-color: #00857a; background: #f3faf9; }
    .qr-chip.is-picked { color: #00594f; background: #e6f4f2; border-color: #00857a; box-shadow: inset 0 0 0 1px #00857a; }
    .qr-chip:disabled { cursor: default; opacity: .55; }
    .qr-chip.is-picked:disabled { opacity: 1; }   /* the one being sent stays clear */
    .qr-chip:focus-visible { outline: 2px solid #00a496; outline-offset: 2px; }
    .qr-chip-stars { margin-left: 4px; font-size: 12px; color: #c98f00; white-space: nowrap; }
    .qr-text { display: block; box-sizing: border-box; width: 100%; min-height: 76px; resize: vertical; font: inherit; font-size: 13.5px;
        color: #333; padding: 9px 11px; border: 1px solid #d5dde0; border-radius: 8px; }
    .qr-text:focus { outline: none; border-color: #00857a; box-shadow: 0 0 0 3px rgba(0, 133, 122, .15); }
</style>
<script>
window.QuickReview = (function () {
    'use strict';
    var BASE      = @json(url('/review/quick'));
    var CSRF      = @json(csrf_token());
    var USER      = @json(Auth::id());
    var TEMPLATES = @json($qrTemplates);
    var READ_MS   = 60 * 1000;   // reading at least this long earns the prompt
    var LABELS    = ['Tap a star to rate', 'Poor', 'Fair', 'Good', 'Very good', 'Excellent'];
    var LEADS     = { download: 'Your download has started. How would you rate this material?', read: 'Thanks for reading! How would you rate this material?' };
    var STAR      = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.6l2.9 5.9 6.5.9-4.7 4.6 1.1 6.5L12 17.4l-5.8 3.1 1.1-6.5L2.6 9.4l6.5-.9z"/></svg>';
    var reading = null;   // { id, title, since } while the reader is open
    var asking  = null;   // catalog id with a prompt pending or showing

    function skipKey(id) { return 'qr-skip:' + USER + ':' + id; }
    function skipped(id) { try { return localStorage.getItem(skipKey(id)) === '1'; } catch (e) { return false; } }
    function skip(id) { try { localStorage.setItem(skipKey(id), '1'); } catch (e) {} }
    // Titles can carry markup; DOMParser reads the text without running or loading anything.
    function plain(s) { return (new DOMParser().parseFromString(String(s || ''), 'text/html').body.textContent || '').trim(); }
    function esc(s) { return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }

    function ask(id, title, context) {
        if (!window.Swal || !id || asking === id || skipped(id)) return;
        asking = id;
        fetch(BASE + '/' + encodeURIComponent(id), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.ok ? r.json() : { reviewed: true }; })
            .then(function (s) { if (s.reviewed) asking = null; else show(id, plain(title), context); })
            .catch(function () { asking = null; });
    }

    function show(id, title, context) {
        var stars = '', chips = '';
        for (var n = 1; n <= 5; n++) {
            stars += '<label class="qr-star" data-n="' + n + '"><input type="radio" name="qr-rating" value="' + n + '" aria-label="' + n + ' of 5 stars, ' + LABELS[n] + '">' + STAR + '</label>';
        }
        TEMPLATES.forEach(function (t, i) {
            if (t.when && t.when !== context) return;
            chips += '<button type="button" class="qr-chip" data-i="' + i + '">' + esc(t.text) +
                     '<span class="qr-chip-stars" aria-label=", ' + t.rating + ' stars">' + t.rating + '&#9733;</span></button>';
        });

        Swal.fire({
            titleText: 'Leave a quick review',
            html: '<div class="qr">' +
                      '<p class="qr-lead">' + esc(LEADS[context] || LEADS.read) + '</p>' +
                      (title ? '<p class="qr-material">' + esc(title) + '</p>' : '') +
                      '<p class="qr-sub">Quick picks: tap one to send it right away</p>' +
                      '<div class="qr-chips">' + chips + '</div>' +
                      '<p class="qr-or">or rate and write your own</p>' +
                      '<div class="qr-rate" role="radiogroup" aria-label="Your rating">' + stars + '</div>' +
                      '<p class="qr-rate-label" aria-live="polite">' + LABELS[0] + '</p>' +
                      '<textarea class="qr-text" maxlength="1000" placeholder="Write your own review (optional)" aria-label="Your review"></textarea>' +
                  '</div>',
            width: 560,
            showCancelButton: true,
            confirmButtonText: 'Submit review',
            cancelButtonText: 'Not now',
            confirmButtonColor: '#00857a',
            reverseButtons: true,
            allowOutsideClick: false,   // a stray click shouldn't throw away a half-written review
            showLoaderOnConfirm: true,
            customClass: { container: 'qr-container' },
            didOpen: wire,
            preConfirm: function () { return save(id); }
        }).then(function (r) {
            asking = null;
            if (r.isConfirmed) {
                Swal.fire({ icon: 'success', title: 'Thanks for your review!', showConfirmButton: false, timer: 1600, customClass: { container: 'qr-container' } })
                    .then(function () { document.dispatchEvent(new CustomEvent('quickreview:saved', { detail: { catalogId: id } })); });
            } else if (r.isDismissed) {
                skip(id);   // "Not now" / Esc: don't ask again for this material (it stays in To Review)
            }
        });
    }

    function wire(popup) {
        var stars = popup.querySelectorAll('.qr-star');
        var label = popup.querySelector('.qr-rate-label');
        var text  = popup.querySelector('.qr-text');
        var chips = popup.querySelectorAll('.qr-chip');
        function rating() { var c = popup.querySelector('input[name="qr-rating"]:checked'); return c ? +c.value : 0; }
        function paint(n) {
            stars.forEach(function (s) { s.classList.toggle('is-on', +s.dataset.n <= n); });
            label.textContent = LABELS[n];
        }
        stars.forEach(function (s) {
            var input = s.querySelector('input');
            input.addEventListener('change', function () { paint(+input.value); Swal.resetValidationMessage(); });
            s.addEventListener('mouseenter', function () { paint(+s.dataset.n); });
            s.addEventListener('mouseleave', function () { paint(rating()); });
        });
        // A quick pick fills in its stars and text, then submits (same path as "Submit review").
        chips.forEach(function (chip) {
            chip.addEventListener('click', function () {
                var t = TEMPLATES[chip.dataset.i];
                chips.forEach(function (c) { c.classList.toggle('is-picked', c === chip); });
                text.value = t.text;
                popup.querySelector('input[name="qr-rating"][value="' + t.rating + '"]').checked = true;
                paint(t.rating);
                Swal.resetValidationMessage();
                Swal.clickConfirm();
            });
        });
        // If a send fails and the patron edits the text, the pick no longer describes it.
        text.addEventListener('input', function () {
            chips.forEach(function (c) { c.classList.remove('is-picked'); });
        });
    }

    function save(id) {
        var popup = Swal.getPopup();
        var checked = popup.querySelector('input[name="qr-rating"]:checked');
        var chips = popup.querySelectorAll('.qr-chip');
        function busy(on) { chips.forEach(function (c) { c.disabled = on; }); }
        if (!checked) { Swal.showValidationMessage('Please tap a star rating first.'); return false; }
        busy(true);
        return fetch(BASE, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ catalog_id: id, rating: +checked.value, remarks: popup.querySelector('.qr-text').value })
        }).then(function (r) {
            if (r.ok || r.status === 409) return true;   // 409: already reviewed (e.g. in another tab)
            busy(false);
            Swal.showValidationMessage(r.status === 419 ? 'Your session has expired. Please refresh the page and try again.' : 'That didn’t save. Please try again.');
            return false;
        }).catch(function () {
            busy(false);
            Swal.showValidationMessage('That didn’t save. Please try again.');
            return false;
        });
    }

    return {
        download: function (id, title) { setTimeout(function () { ask(String(id), title, 'download'); }, 1500); },   // let the download start first
        startRead: function (id, title) { reading = { id: String(id), title: title, since: Date.now() }; },
        endRead: function () {
            var r = reading; reading = null;
            if (r && Date.now() - r.since >= READ_MS) ask(r.id, r.title, 'read');
        }
    };
})();
</script>
