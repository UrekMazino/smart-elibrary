/* Admin dashboard (resources/views/admin/index.blade.php): the header clock and the Activity section. */

/* Header clock: date, time and greeting in the viewer's own time zone (the browser's), ticking every
 * second. The server renders app-timezone values only as the no-JS fallback. Not aria-live: a
 * clock announcing every second would drown out everything else. */
(function () {
  'use strict';

  var dateEl = document.getElementById('dsh-today');
  var timeEl = document.getElementById('dsh-clock');
  var tzEl = document.getElementById('dsh-tz');
  var greetEl = document.getElementById('dsh-greeting');
  if (!timeEl || !window.Intl) return;

  var dateFmt = new Intl.DateTimeFormat('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
  var timeFmt = new Intl.DateTimeFormat('en-US', { hour: 'numeric', minute: '2-digit', second: '2-digit' });
  var zoneFmt = new Intl.DateTimeFormat('en-US', { timeZoneName: 'short' });
  var zone = Intl.DateTimeFormat().resolvedOptions().timeZone || '';
  if (zone) tzEl.title = 'Your time zone: ' + zone;

  function pad(n) { return (n < 10 ? '0' : '') + n; }

  function tick() {
    var now = new Date();
    var ymd = now.getFullYear() + '-' + pad(now.getMonth() + 1) + '-' + pad(now.getDate());
    var tz = zoneFmt.formatToParts(now).filter(function (p) { return p.type === 'timeZoneName'; })[0];
    var h = now.getHours();

    timeEl.textContent = timeFmt.format(now);
    timeEl.setAttribute('datetime', now.toISOString());
    if (dateEl.getAttribute('datetime') !== ymd) {
      dateEl.textContent = dateFmt.format(now);
      dateEl.setAttribute('datetime', ymd);
    }
    tzEl.textContent = tz ? tz.value : '';   // e.g. "GMT+8"; changes across daylight-saving switches
    greetEl.textContent = h < 12 ? 'Good morning' : (h < 18 ? 'Good afternoon' : 'Good evening');

    setTimeout(tick, 1000 - now.getMilliseconds());   // land on the next whole second
  }

  dateEl.removeAttribute('datetime');   // make the first tick replace the server's date
  tick();
})();

/* Activity section.
 *
 * One request per date range: GET DASHBOARD.activityUrl?range=…[&from=…&to=…]
 * (AdminController@dashboardActivity -> DashboardService::activity). The KPI tiles are the tabs
 * of the trend chart; the trend overlays the previous period in gray and has a table view.
 * The range and metric live in the URL (?range=30d&metric=downloads) so a view survives a reload.
 */
(function () {
  'use strict';

  var cfg = window.DASHBOARD || {};
  var root = document.getElementById('dsh-activity');
  if (!root) return;

  // The staff member's colours (Appearance), as the page shows them: the accent (its lighter variant on a
  // dark background) and the theme's text and line roles (unset for the standard look: the old colours).
  var page = document.documentElement, css = getComputedStyle(page);
  var role = function (name, fallback) { return css.getPropertyValue(name).trim() || fallback; };
  var dark = page.dataset.mode === 'dark' || (page.dataset.mode === 'auto' && window.matchMedia('(prefers-color-scheme: dark)').matches);
  var ACCENT = role(dark ? '--t-primary-on-dark' : '--t-primary', '#6571ff'), PREV = role('--ui-muted', '#8a94a6'), GRID = role('--ui-border', '#eceff4'),
      MUTED = role('--ui-muted', '#8a94a6'), SURFACE = role('--ui-surface', '#fff'), AXIS = role('--ui-border', '#dfe3eb'), CROSS = role('--ui-muted-2', '#c3c9d6');
  var METRICS = {
    views:     { title: 'Record views', noun: 'views' },
    downloads: { title: 'Downloads', noun: 'downloads' },
    reads:     { title: 'Online reads', noun: 'reads' },
    patrons:   { title: 'New patrons', noun: 'new patrons' }
  };
  var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var nf = new Intl.NumberFormat('en-US');
  var compact = new Intl.NumberFormat('en-US', { notation: 'compact', maximumFractionDigits: 1 });

  var state = { range: '30d', from: null, to: null, metric: 'views', top: 'viewed', table: false };
  var data = null, chart = null, request = 0;

  function $(id) { return document.getElementById(id); }
  function fmt(n) { return n >= 10000 ? compact.format(n) : nf.format(n); }
  function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }
  function cap(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : s; }
  function longDate(ymd) { return new Date(ymd + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }); }
  function icon(name) { return window.feather && feather.icons[name] ? feather.icons[name].toSvg() : ''; }

  // ---------- URL state ----------
  (function readUrl() {
    var q = new URLSearchParams(location.search);
    var r = q.get('range');
    if (r && (cfg.ranges[r] || r === 'custom')) state.range = r;
    if (state.range === 'custom') {
      state.from = q.get('from'); state.to = q.get('to');
      if (!/^\d{4}-\d{2}-\d{2}$/.test(state.from || '') || !/^\d{4}-\d{2}-\d{2}$/.test(state.to || '')) { state.range = '30d'; state.from = state.to = null; }
    }
    if (METRICS[q.get('metric')]) state.metric = q.get('metric');
  })();

  function writeUrl() {
    var q = new URLSearchParams(location.search);
    q.set('range', state.range);
    if (state.range === 'custom') { q.set('from', state.from); q.set('to', state.to); } else { q.delete('from'); q.delete('to'); }
    if (state.metric !== 'views') q.set('metric', state.metric); else q.delete('metric');
    history.replaceState(null, '', location.pathname + '?' + q.toString());
  }

  // ---------- Data ----------
  function load() {
    var id = ++request;
    root.classList.add('is-loading');
    root.setAttribute('aria-busy', 'true');
    $('dsh-error').hidden = true;

    var q = new URLSearchParams({ range: state.range });
    if (state.range === 'custom') { q.set('from', state.from); q.set('to', state.to); }

    fetch(cfg.activityUrl + '?' + q.toString(), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); })
      .then(function (d) { if (id === request) { data = d; render(); } })
      .catch(function () { if (id === request) $('dsh-error').hidden = false; })
      .finally(function () {
        if (id !== request) return;
        root.classList.remove('is-loading', 'is-first-load');
        root.removeAttribute('aria-busy');
      });
  }

  // ---------- Render ----------
  function render() {
    var r = data.range;
    $('dsh-range-label').textContent = r.label;
    $('dsh-compare').textContent = r.compare ? 'Compared with the ' + r.compare : 'All recorded activity since ' + longDate(r.from);
    document.querySelectorAll('.dsh-range-opt').forEach(function (b) { b.setAttribute('aria-pressed', b.dataset.range === r.key ? 'true' : 'false'); });
    var form = $('dsh-range-custom');
    form.from.value = r.from; form.to.value = r.to;

    Object.keys(METRICS).forEach(function (m) {
      var tile = root.querySelector('.dsh-kpi[data-metric="' + m + '"]');
      var k = data.kpis[m];
      tile.querySelector('[data-kpi-value]').textContent = fmt(k.value);
      renderDelta(tile.querySelector('[data-kpi-delta]'), k);
      tile.querySelector('[data-kpi-foot]').textContent = foot(m);
      spark(tile.querySelector('[data-kpi-spark]'), k.series);
    });

    renderTrend();
    renderTop();
  }

  function foot(m) {
    if (m === 'views') return data.guestShare === null ? 'No record views yet' : data.guestShare + '% by guests';
    if (m === 'downloads') return 'PDFs downloaded by patrons';
    if (m === 'reads') return 'First opens in the online reader';
    return nf.format(cfg.patronsTotal || 0) + ' patrons in all';
  }

  // "+24% vs previous 30 days" — direction is carried by the arrow and the sign, not colour alone.
  function renderDelta(el, k) {
    el.className = 'dsh-kpi-delta';
    el.textContent = '';
    if (k.previous === null) { el.textContent = 'Whole history'; return; }
    var diff = k.value - k.previous;
    if (diff === 0) { el.textContent = 'No change vs ' + data.range.compare; return; }
    var up = diff > 0;
    el.classList.add(up ? 'is-up' : 'is-down');
    var amount = k.previous ? Math.round(Math.abs(diff) / k.previous * 100) + '%' : nf.format(Math.abs(diff));
    var strong = document.createElement('strong');
    strong.innerHTML = icon(up ? 'arrow-up-right' : 'arrow-down-right');
    strong.appendChild(document.createTextNode((up ? '+' : '−') + amount));
    el.appendChild(strong);
    el.appendChild(document.createTextNode(' vs ' + data.range.compare));
  }

  function spark(svg, values) {
    var w = 120, h = 32, n = values.length;
    if (n < 2) { svg.innerHTML = ''; return; }
    var max = Math.max.apply(null, values.concat([1]));
    var d = values.map(function (v, i) {
      return (i ? 'L' : 'M') + (i / (n - 1) * w).toFixed(1) + ' ' + (h - 2 - v / max * (h - 4)).toFixed(1);
    }).join(' ');
    svg.innerHTML = '<path class="area" d="' + d + ' L' + w + ' ' + h + ' L0 ' + h + ' Z"/><path class="line" d="' + d + '"/>';
  }

  function renderTrend() {
    var m = METRICS[state.metric], k = data.kpis[state.metric], r = data.range;
    var hasPrev = !!k.prevSeries;
    $('dsh-trend-title').textContent = m.title;
    $('dsh-trend-sub').textContent = 'Per ' + r.bucket + ' · ' + r.label;
    // A single series needs no legend — the title already names it.
    $('dsh-legend').hidden = !hasPrev;
    $('dsh-legend-current').textContent = r.label;
    if (hasPrev) $('dsh-legend-prev').textContent = cap(r.compare);

    $('dsh-trend-chart').hidden = state.table;
    $('dsh-trend-table').hidden = !state.table;
    if (state.table) renderTable(); else renderChart(k, hasPrev);
  }

  function renderChart(k, hasPrev) {
    if (chart) { chart.destroy(); chart = null; }
    if (!window.ApexCharts) { $('dsh-trend-chart').textContent = 'Charts are unavailable; use the table view.'; return; }
    var n = data.labels.length;
    var series = [{ name: data.range.label, type: 'area', data: k.series }];
    if (hasPrev) series.push({ name: cap(data.range.compare), type: 'line', data: k.prevSeries });

    chart = new ApexCharts($('dsh-trend-chart'), {
      chart: { type: 'line', height: 330, fontFamily: 'inherit', parentHeightOffset: 0, toolbar: { show: false }, zoom: { enabled: false },
               animations: { enabled: !reduceMotion, speed: 280, animateGradually: { enabled: false } } },
      series: series,
      colors: hasPrev ? [ACCENT, PREV] : [ACCENT],
      stroke: { width: 2, curve: 'straight', lineCap: 'round' },
      fill: { type: 'solid', opacity: hasPrev ? [0.1, 1] : [0.1] },
      markers: { size: n === 1 ? 5 : 0, strokeWidth: 2, strokeColors: SURFACE, hover: { size: 5 } },
      dataLabels: { enabled: false },
      legend: { show: false },
      states: { hover: { filter: { type: 'none' } }, active: { filter: { type: 'none' } } },
      grid: { borderColor: GRID, strokeDashArray: 0, padding: { left: 10, right: 12 }, xaxis: { lines: { show: false } } },
      xaxis: {
        categories: data.labels,
        tickAmount: n > 10 ? 7 : undefined,
        labels: { rotate: 0, hideOverlappingLabels: true, style: { colors: MUTED, fontSize: '11px' } },
        axisBorder: { color: AXIS }, axisTicks: { show: false },
        tooltip: { enabled: false },
        crosshairs: { stroke: { color: CROSS, width: 1, dashArray: 0 } }
      },
      yaxis: {
        min: 0, max: function (max) { return max < 4 ? 4 : max; }, tickAmount: 4, forceNiceScale: true,
        labels: { formatter: function (v) { return fmt(Math.round(v)); }, style: { colors: [MUTED], fontSize: '11px' } }
      },
      tooltip: { shared: true, intersect: false, custom: tooltip }
    });
    chart.render();
  }

  // One tooltip for both series; the value leads, the date follows.
  function tooltip(o) {
    var i = o.dataPointIndex, k = data.kpis[state.metric];
    var html = '<div class="dsh-tip">' + esc(METRICS[state.metric].title) +
      '<div class="dsh-tip-row"><span class="dsh-key dsh-key-current"></span><strong>' + nf.format(k.series[i]) + '</strong>' + esc(data.labels[i]) + '</div>';
    if (k.prevSeries) {
      html += '<div class="dsh-tip-row"><span class="dsh-key dsh-key-prev"></span><strong>' + nf.format(k.prevSeries[i]) + '</strong>' + esc(data.prevLabels[i]) + '</div>';
    }
    return html + '</div>';
  }

  function renderTable() {
    if (chart) { chart.destroy(); chart = null; }
    var k = data.kpis[state.metric], hasPrev = !!k.prevSeries, b = cap(data.range.bucket);
    var t = '<table class="dsh-table"><caption class="visually-hidden">' + esc(METRICS[state.metric].title + ', ' + data.range.label) + '</caption><thead><tr>' +
      '<th scope="col">' + b + '</th><th scope="col" class="num">' + esc(METRICS[state.metric].title) + '</th>' +
      (hasPrev ? '<th scope="col">Previous ' + esc(data.range.bucket) + '</th><th scope="col" class="num">Previous</th>' : '') + '</tr></thead><tbody>';
    data.labels.forEach(function (label, i) {
      t += '<tr><td>' + esc(label) + '</td><td class="num">' + nf.format(k.series[i]) + '</td>' +
        (hasPrev ? '<td>' + esc(data.prevLabels[i]) + '</td><td class="num">' + nf.format(k.prevSeries[i]) + '</td>' : '') + '</tr>';
    });
    t += '</tbody><tfoot><tr><th scope="row">Total</th><td class="num"><strong>' + nf.format(k.value) + '</strong></td>' +
      (hasPrev ? '<td></td><td class="num"><strong>' + nf.format(k.previous) + '</strong></td>' : '') + '</tr></tfoot></table>';
    $('dsh-trend-table').innerHTML = t;
  }

  // Top materials: Utilities → Rankings' lists for its window (all time, or the home page's last 90 days).
  var TOP = {
    viewed:     { noun: 'views', empty: 'No views' },
    downloaded: { noun: 'readers', empty: 'No downloads' },
    rated:      { noun: 'score', empty: 'No ratings' }
  };
  var nf2 = new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  function renderTop() {
    var win = data.top.window || { key: 'recent', label: 'last 90 days' };
    var list = data.top[state.top] || [], ol = $('dsh-top'), noun = TOP[state.top].noun;
    $('dsh-top-sub').textContent = 'As ranked on the home page, ' + win.label;
    var link = $('dsh-top-link');
    if (link) link.href = link.dataset.base + (win.key === 'all' ? '?window=all' : '');
    ol.textContent = '';
    if (!list.length) {
      var empty = document.createElement('li');
      empty.className = 'dsh-empty';
      empty.textContent = TOP[state.top].empty + ' in the ' + win.label + '.';
      ol.appendChild(empty);
      return;
    }
    var max = list[0].count || 1;
    list.forEach(function (it) {
      var li = document.createElement('li');
      var title = document.createElement(cfg.recordUrl ? 'a' : 'span');
      title.className = 'dsh-top-title';
      title.textContent = it.title;
      title.title = it.by ? it.title + ' / ' + it.by : it.title;
      if (cfg.recordUrl) title.href = cfg.recordUrl + '/' + it.id;

      var n = document.createElement('span');
      n.className = 'dsh-top-n';
      n.textContent = state.top === 'rated' ? nf2.format(it.count) : fmt(it.count);
      var sr = document.createElement('span');
      sr.className = 'visually-hidden';
      sr.textContent = ' ' + noun;
      n.appendChild(sr);

      li.appendChild(title);
      li.appendChild(n);
      var sub = [it.by, it.detail].filter(Boolean).join(' · ');
      if (sub) {
        var by = document.createElement('span');
        by.className = 'dsh-top-by';
        by.textContent = sub;
        li.appendChild(by);
      }
      var track = document.createElement('span');
      track.className = 'dsh-top-track';
      track.setAttribute('aria-hidden', 'true');
      track.innerHTML = '<span style="width:' + Math.max(3, Math.round(it.count / max * 100)) + '%"></span>';
      li.appendChild(track);
      ol.appendChild(li);
    });
  }

  // ---------- Controls ----------
  var tabs = Array.prototype.slice.call(root.querySelectorAll('.dsh-kpi'));
  function selectMetric(m, focus, initial) {
    state.metric = m;
    tabs.forEach(function (t) {
      var on = t.dataset.metric === m;
      t.setAttribute('aria-selected', on ? 'true' : 'false');
      t.tabIndex = on ? 0 : -1;
      if (on && focus) t.focus();
    });
    if (!initial) writeUrl();
    if (data) renderTrend();
  }
  tabs.forEach(function (t, i) {
    t.addEventListener('click', function () { selectMetric(t.dataset.metric); });
    t.addEventListener('keydown', function (e) {
      var step = e.key === 'ArrowRight' || e.key === 'ArrowDown' ? 1 : e.key === 'ArrowLeft' || e.key === 'ArrowUp' ? -1 : 0;
      if (!step) return;
      e.preventDefault();
      selectMetric(tabs[(i + step + tabs.length) % tabs.length].dataset.metric, true);
    });
  });

  // ---------- "About" notes on the cards: one open at a time; Esc or a click elsewhere closes it ----------
  var infoBtns = Array.prototype.slice.call(root.querySelectorAll('.dsh-info-btn'));
  function closeInfo(except) {
    infoBtns.forEach(function (b) {
      if (b === except || b.getAttribute('aria-expanded') !== 'true') return;
      b.setAttribute('aria-expanded', 'false');
      document.getElementById(b.getAttribute('aria-controls')).hidden = true;
    });
  }
  infoBtns.forEach(function (b) {
    b.addEventListener('click', function () {
      var open = b.getAttribute('aria-expanded') !== 'true';
      closeInfo(b);
      b.setAttribute('aria-expanded', open ? 'true' : 'false');
      var pop = document.getElementById(b.getAttribute('aria-controls'));
      pop.hidden = !open;
      if (!open) return;
      // Opens leftward from the card's right edge; on a card at the left (under the sidebar's edge), rightward instead.
      pop.classList.remove('is-start');
      if (pop.getBoundingClientRect().left < root.getBoundingClientRect().left) pop.classList.add('is-start');
    });
  });
  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    var open = infoBtns.filter(function (b) { return b.getAttribute('aria-expanded') === 'true'; })[0];
    if (open) { closeInfo(); open.focus(); }
  });
  document.addEventListener('click', function (e) {
    if (!e.target.closest('.dsh-kpi-info')) closeInfo();
  });

  root.querySelectorAll('[data-top]').forEach(function (b) {
    b.addEventListener('click', function () {
      state.top = b.dataset.top;
      root.querySelectorAll('[data-top]').forEach(function (x) { x.setAttribute('aria-pressed', x === b ? 'true' : 'false'); });
      if (data) renderTop();
    });
  });

  var toggle = $('dsh-table-toggle');
  toggle.addEventListener('click', function () {
    state.table = !state.table;
    var label = state.table ? 'Show as a chart' : 'Show as a table';
    toggle.setAttribute('aria-pressed', state.table ? 'true' : 'false');
    toggle.title = label;
    toggle.innerHTML = icon(state.table ? 'trending-up' : 'list') + '<span class="visually-hidden">' + label + '</span>';
    if (data) renderTrend();
  });

  function closeMenu() {
    var btn = $('dsh-range-btn');
    if (window.bootstrap && bootstrap.Dropdown) bootstrap.Dropdown.getOrCreateInstance(btn).hide();
    btn.focus();
  }
  root.querySelectorAll('.dsh-range-opt').forEach(function (b) {
    b.addEventListener('click', function () {
      state.range = b.dataset.range; state.from = state.to = null;
      closeMenu(); writeUrl(); load();
    });
  });
  $('dsh-range-custom').addEventListener('submit', function (e) {
    e.preventDefault();
    var f = this.from.value, t = this.to.value;
    if (!f || !t) return;
    if (f > t) { var x = f; f = t; t = x; }
    state.range = 'custom'; state.from = f; state.to = t;
    closeMenu(); writeUrl(); load();
  });
  $('dsh-retry').addEventListener('click', load);

  // ---------- Start ----------
  root.classList.add('is-first-load');
  selectMetric(state.metric, false, true);
  load();
})();
