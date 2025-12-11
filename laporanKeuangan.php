<?php
// admin_sales.php (realtime server-date + periode Day/Week/Month + prev/next + export)
require_once 'db.php';

if (session_status() === PHP_SESSION_NONE) session_start();
date_default_timezone_set('Asia/Jakarta');

if (function_exists('requireAdmin')) { requireAdmin(); }
else { if (empty($_SESSION['admin'])) { header('Location: login.php'); exit; } }

$admin = $_SESSION['admin'] ?? [];
function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }

$server_ts_ms = (int)(microtime(true) * 1000);
$server_date = date('Y-m-d');
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Admin - Rekap (Day/Week/Month)</title>
  <script src="https://cdn.tailwindcss.com" defer></script>
  <style>
    .flash-new { animation: flashrow 1.2s ease-in-out; }
    @keyframes flashrow { 0% { background: #e6ffed; } 50% { background:#fff } 100% { background:#fff } }
    .clock { font-weight:600; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, "Roboto Mono", "Courier New", monospace; }
    .btn { padding:.5rem .75rem; border-radius:.375rem; border:1px solid #e5e7eb; background:#fff; cursor:pointer; }
    .btn-primary { background:#047857; color:#fff; border-color:#047857; }
  </style>
</head>
<body class="min-h-screen bg-gray-50 text-gray-800">
  <div class="max-w-7xl mx-auto p-6">
    <header class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-semibold">Rekap Pendapatan — Day / Week / Month</h1>
        <p class="text-sm text-gray-600">Halo, <span class="font-medium"><?php echo e($admin['name'] ?? $admin['username'] ?? 'Admin'); ?></span></p>
      </div>
      <div class="text-right">
        <div>Waktu server: <span id="serverClock" class="clock">-</span></div>
        <div>Terakhir fetch: <span id="lastUpdated">-</span></div>
      </div>
    </header>

    <!-- Controls: period, prev/next, load, export -->
    <section class="bg-white p-4 rounded shadow mb-6 flex flex-col md:flex-row md:items-center gap-4">
      <div class="flex items-center gap-2">
        <label class="text-sm text-gray-600">Periode:</label>
        <select id="periodSelect" class="p-2 rounded border">
          <option value="day">Day</option>
          <option value="week">Week</option>
          <option value="month">Month</option>
        </select>
      </div>

      <div class="flex items-center gap-2">
        <button id="btnPrev" class="btn">Prev</button>
        <button id="btnNext" class="btn">Next</button>
      </div>

      <div class="flex items-center gap-2 ml-auto">
        <label class="text-sm text-gray-600">Tanggal rekap (server)</label>
        <input id="summaryDate" type="date" value="<?php echo e($server_date); ?>" readonly class="p-2 rounded border" />
        <button id="btnLoadSummary" class="btn btn-primary">Muat</button>
        <button id="btnExport" class="btn">Export CSV</button>
      </div>
    </section>

    <!-- Summary cards -->
    <section class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
      <div class="p-4 bg-white rounded shadow">
        <div class="text-sm text-gray-500">Periode aktif</div>
        <div id="cardRange" class="text-lg font-bold">-</div>
      </div>
      <div class="p-4 bg-white rounded shadow">
        <div class="text-sm text-gray-500">Total Pesanan</div>
        <div id="cardCount" class="text-lg font-bold">0</div>
      </div>
      <div class="p-4 bg-white rounded shadow">
        <div class="text-sm text-gray-500">Total Pendapatan</div>
        <div id="cardTotal" class="text-lg font-bold">Rp 0</div>
      </div>
    </section>

    <!-- Table -->
    <section class="bg-white p-0 rounded shadow-sm border">
      <div id="summaryHeader" class="p-4 border-b text-sm text-gray-700"></div>
      <div class="p-4 overflow-auto">
        <table class="w-full text-sm">
          <thead><tr><th class="p-2">#</th><th class="p-2">Order Code</th><th class="p-2">Customer</th><th class="p-2">Total (Rp)</th><th class="p-2">Created At</th><th class="p-2">Items</th></tr></thead>
          <tbody id="summaryTbody"><tr><td colspan="6" class="p-6 text-center text-gray-500">Muat untuk melihat data</td></tr></tbody>
          <tfoot id="summaryTfoot"></tfoot>
        </table>
      </div>
    </section>
  </div>

<script>
/* Client logic:
   - period: day / week / month
   - compute start & end (YYYY-MM-DD) for current period
   - Prev/Next shifts period backwards/forwards
   - summary uses api.php?action=sales_summary_range&start=...&end=...
   - export calls api.php?action=export_sales&start=...&end=...
   - server-clock synced from PHP ($server_ts_ms)
*/

const el = id => document.getElementById(id);
const serverTsFromPhp = <?php echo json_encode($server_ts_ms); ?>;
let serverOffsetMs = serverTsFromPhp - Date.now();

function nowServer() { return new Date(Date.now() + serverOffsetMs); }
function fmtDate(d) { // YYYY-MM-DD
  return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
}
function addDays(d, n) { const x = new Date(d); x.setDate(x.getDate()+n); return x; }
function startOfWeek(d) { // Monday as start
  const x = new Date(d); const day = (x.getDay() + 6) % 7; x.setDate(x.getDate() - day); x.setHours(0,0,0,0); return x; }
function endOfWeek(d) { const s = startOfWeek(d); const e = addDays(s,6); e.setHours(23,59,59,999); return e; }
function startOfMonth(d) { const x = new Date(d.getFullYear(), d.getMonth(), 1); x.setHours(0,0,0,0); return x; }
function endOfMonth(d) { const x = new Date(d.getFullYear(), d.getMonth()+1, 0); x.setHours(23,59,59,999); return x; }

let period = 'day'; // default
let anchorDate = new Date(nowServer()); // used to compute current period (server-synced)

// server clock & auto update summaryDate
function startClock() {
  function tick(){
    const s = nowServer();
    const hh = String(s.getHours()).padStart(2,'0'), mm = String(s.getMinutes()).padStart(2,'0'), ss = String(s.getSeconds()).padStart(2,'0');
    el('serverClock').textContent = `${fmtDate(s)} ${hh}:${mm}:${ss}`;
    // keep summaryDate locked to server date
    const dateStr = fmtDate(s);
    if (el('summaryDate').value !== dateStr) {
      el('summaryDate').value = dateStr;
      // update anchorDate only if period == day (or optionally keep anchored)
      anchorDate = new Date(s);
      // if current period includes today, reload automatically
      // we'll always reload to keep data fresh
      loadSummary();
    }
  }
  tick();
  return setInterval(tick, 1000);
}
startClock();

// compute start/end (strings) for current period based on anchorDate & period
function computeRange(period, anchorDate) {
  let s, e;
  if (period === 'day') {
    s = new Date(anchorDate); s.setHours(0,0,0,0);
    e = new Date(anchorDate); e.setHours(23,59,59,999);
  } else if (period === 'week') {
    s = startOfWeek(anchorDate);
    e = endOfWeek(anchorDate);
  } else if (period === 'month') {
    s = startOfMonth(anchorDate);
    e = endOfMonth(anchorDate);
  } else {
    s = new Date(anchorDate); s.setHours(0,0,0,0);
    e = new Date(anchorDate); e.setHours(23,59,59,999);
  }
  return { start: fmtDate(s), end: fmtDate(e) };
}

// fetch range summary (admin-only)
async function fetchRange(start, end) {
  const url = `api.php?action=sales_summary_range&start=${encodeURIComponent(start)}&end=${encodeURIComponent(end)}`;
  const res = await fetch(url, { credentials: 'same-origin' });
  if (!res.ok) throw new Error('HTTP ' + res.status);
  const j = await res.json();
  if (!j || !j.ok) throw new Error(j?.message || 'API error');
  return j.data;
}

// render helpers
function renderRows(rows) {
  const tbody = el('summaryTbody');
  if (!Array.isArray(rows) || rows.length === 0) {
    tbody.innerHTML = '<tr><td colspan="6" class="p-6 text-center text-gray-500">Tidak ada pesanan</td></tr>';
    return;
  }
  tbody.innerHTML = rows.map((r, idx) => {
    let items = '';
    try { const p = JSON.parse(r.items); if (Array.isArray(p)) items = p.map(it=>`${it.name} x${it.qty}`).join(' | '); } catch(e){}
    return `<tr>
      <td class="p-2">${idx+1}</td>
      <td class="p-2 font-mono">${escapeHtml(r.order_code)}</td>
      <td class="p-2">${escapeHtml(r.customer_name||'')}<br><small>${escapeHtml(r.phone||'')}</small></td>
      <td class="p-2">Rp ${new Intl.NumberFormat('id-ID').format(Number(r.total||0))}</td>
      <td class="p-2">${escapeHtml(r.created_at)}</td>
      <td class="p-2 text-xs">${escapeHtml(items)}</td>
    </tr>`;
  }).join('');
}

function renderFooter(summary) {
  el('summaryTfoot').innerHTML = `<tr><td colspan="2" class="p-2 font-semibold">TOTAL</td><td class="p-2">Pesanan: ${summary.cnt}</td><td class="p-2">Rp ${new Intl.NumberFormat('id-ID').format(summary.total_amount)}</td><td colspan="2"></td></tr>`;
}

function escapeHtml(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,"&#039;"); }

// main load function
let pollingHandle = null;
async function loadSummary() {
  // compute range
  const range = computeRange(period, anchorDate);
  el('summaryHeader').textContent = 'Memuat...';
  try {
    const data = await fetchRange(range.start, range.end);
    el('summaryHeader').innerHTML = `Menampilkan rekap untuk <strong>${range.start}</strong> sampai <strong>${range.end}</strong> (${period})`;
    renderRows(data.rows || []);
    renderFooter(data.summary || {cnt:0, total_amount:0});
    el('cardRange').textContent = `${range.start} → ${range.end}`;
    el('cardCount').textContent = data.summary?.cnt ?? 0;
    el('cardTotal').textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(data.summary?.total_amount ?? 0);
    const s = nowServer(); el('lastUpdated').textContent = `${String(s.getHours()).padStart(2,'0')}:${String(s.getMinutes()).padStart(2,'0')}:${String(s.getSeconds()).padStart(2,'0')}`;
  } catch (err) {
    console.error(err);
    el('summaryHeader').textContent = 'Gagal memuat: ' + (err.message || '');
  }
  // polling logic: only poll automatically when period includes today (so live day view), otherwise stop polling
  const todayStr = fmtDate(nowServer());
  if (range.start <= todayStr && range.end >= todayStr) {
    // enable periodic refresh
    if (pollingHandle) clearInterval(pollingHandle);
    pollingHandle = setInterval(() => loadSummary(), 5000);
  } else {
    if (pollingHandle) { clearInterval(pollingHandle); pollingHandle = null; }
  }
}

// Prev / Next shifts anchorDate
function shiftPeriod(direction) { // direction: -1 prev, +1 next
  if (period === 'day') anchorDate = addDays(anchorDate, direction * 1);
  else if (period === 'week') anchorDate = addDays(anchorDate, direction * 7);
  else if (period === 'month') { anchorDate = new Date(anchorDate.getFullYear(), anchorDate.getMonth() + direction, anchorDate.getDate()); }
  loadSummary();
}

// event binds
el('periodSelect').addEventListener('change', (e) => {
  period = e.target.value;
  // reset anchor to server now
  anchorDate = new Date(nowServer());
  loadSummary();
});
el('btnPrev').addEventListener('click', ()=> shiftPeriod(-1));
el('btnNext').addEventListener('click', ()=> shiftPeriod(1));
el('btnLoadSummary').addEventListener('click', ()=> loadSummary());

// export: use start & end
el('btnExport').addEventListener('click', () => {
  const r = computeRange(period, anchorDate);
  const url = `api.php?action=export_sales&start=${encodeURIComponent(r.start)}&end=${encodeURIComponent(r.end)}`;
  window.open(url, '_blank');
});

// initial setup: set summaryDate to server date and load
document.addEventListener('DOMContentLoaded', () => {
  el('summaryDate').value = fmtDate(nowServer());
  anchorDate = new Date(nowServer());
  // initial period can be day/week/month default day
  el('periodSelect').value = 'day';
  loadSummary();
});
</script>
</body>
</html>
