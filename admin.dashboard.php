<?php
require_once 'db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
requireAdmin();
$admin = $_SESSION['admin'];
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Dashboard Admin - Menu</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    /* sedikit override untuk tampilan light */
    .accent-maroon { background:#7b1f1f; color: #fff; }
    .accent-maroon-hover:hover { background:#a02b2b; }
    .table-row-hover:hover { background: #f8fafc; }
    .modal-backdrop{ background: rgba(0,0,0,.3); }
    .img-preview { width: 140px; height: 92px; object-fit: cover; border-radius: .375rem; border: 1px solid #e6e6e6; }
  </style>
</head>
<body class="min-h-screen bg-gray-50 text-gray-800">
  <div class="max-w-7xl mx-auto p-6">
    <header class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-semibold">Dashboard Menu</h1>
      <div class="flex items-center gap-4">
        <div class="text-gray-600">Halo, <span class="font-medium"><?php echo htmlspecialchars($admin['name'] ?? $admin['username']); ?></span></div>
        <a href="logout.php" class="px-3 py-1 rounded border border-red-600 text-red-600 hover:bg-red-50">Logout</a>
      </div>
    </header>

    <div class="mb-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
      <div class="flex items-center gap-2">
        <button id="btnAdd" class="px-4 py-2 rounded accent-maroon accent-maroon-hover">Tambah Menu</button>
        <button id="btnReload" class="px-4 py-2 rounded border bg-white hover:bg-gray-50">Muat Ulang</button>
      </div>
      <div class="flex items-center gap-2">
        <input id="search" placeholder="Cari nama / id" class="p-2 rounded border shadow-sm w-64" />
      </div>
    </div>

    <div id="tableWrap" class="bg-white p-4 rounded shadow-sm border">
      <table class="w-full table-auto text-left">
        <thead>
          <tr class="text-sm text-gray-600">
            <th class="p-3">#</th>
            <th class="p-3">Gambar</th>
            <th class="p-3">ID</th>
            <th class="p-3">Nama</th>
            <th class="p-3">Kategori</th>
            <th class="p-3">Harga</th>
            <th class="p-3">Aksi</th>
          </tr>
        </thead>
        <tbody id="tbody" class="text-sm"></tbody>
      </table>
    </div>
  </div>

  <!-- Modal form (light) -->
  <div id="modal" class="fixed inset-0 hidden items-center justify-center modal-backdrop z-50">
    <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-2xl">
      <h2 id="modalTitle" class="text-xl font-semibold mb-4">Tambah Menu</h2>
      <form id="form" autocomplete="off">
        <input type="hidden" id="mode" value="add" />
        <div class="grid grid-cols-2 gap-3 mb-3">
          <div>
            <label class="block text-sm text-gray-600">ID (unik)</label>
            <input id="fid" class="w-full mt-1 p-2 rounded border shadow-sm" required />
          </div>
          <div>
            <label class="block text-sm text-gray-600">Nama</label>
            <input id="fname" class="w-full mt-1 p-2 rounded border shadow-sm" required />
          </div>
        </div>

        <div class="grid grid-cols-3 gap-3 mb-3">
          <div>
            <label class="block text-sm text-gray-600">Kategori</label>
            <select id="fcategory" class="w-full mt-1 p-2 rounded border shadow-sm">
              <option value="lauk">lauk</option>
              <option value="Tambahan">Tambahan</option>
              <option value="minuman">minuman</option>
            </select>
          </div>
          <div>
            <label class="block text-sm text-gray-600">Harga</label>
            <input id="fprice" type="number" class="w-full mt-1 p-2 rounded border shadow-sm" placeholder="Harga" />
          </div>
          <div>
            <label class="block text-sm text-gray-600">Gambar (jpg/png)</label>
            <input id="fimage" name="image" type="file" accept="image/*" class="w-full mt-1" />
          </div>
        </div>

        <div class="mb-3 flex gap-4">
          <div class="w-44">
            <label class="block text-sm text-gray-600">Preview</label>
            <div id="previewWrap" class="mt-2">
              <img id="imgPreview" src="" alt="preview" class="img-preview hidden">
              <div id="noPreview" class="text-sm text-gray-500">Belum ada gambar</div>
            </div>
          </div>
          <div class="flex-1">
            <label class="block text-sm text-gray-600">Deskripsi</label>
            <textarea id="fdesc" rows="4" class="w-full mt-1 p-2 rounded border shadow-sm" placeholder="Deskripsi"></textarea>
          </div>
        </div>

        <div class="flex justify-end gap-2">
          <button type="button" id="btnCancel" class="px-4 py-2 rounded border">Batal</button>
          <button class="px-4 py-2 rounded accent-maroon accent-maroon-hover">Simpan</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    // helper short-hand
    function el(id){ return document.getElementById(id); }

    // fetch list dari api.php?action=list
    async function fetchMenus(q=''){
      const url = 'api.php?action=list' + (q ? ('&q=' + encodeURIComponent(q)) : '');
      const res = await fetch(url, { credentials: 'same-origin' });
      return res.ok ? res.json() : [];
    }

    // render table (light styles)
    async function render(){
      const q = el('search').value.trim();
      let data = [];
      try { data = await fetchMenus(q); } catch(err) { console.error(err); data = []; }

      const tbody = el('tbody');
      if (!Array.isArray(data) || data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="p-4 text-center text-gray-500">Tidak ada data</td></tr>';
        return;
      }

      tbody.innerHTML = data.map((it, idx) => {
        const img = escapeAttr(it.image || '');
        return `
        <tr class="table-row-hover">
          <td class="p-3 align-middle">${idx+1}</td>
          <td class="p-3"><img src="${img}" class="w-28 h-16 object-cover rounded border" onerror="this.src='https://via.placeholder.com/140x80?text='+encodeURIComponent((it.name||'').charAt(0))"></td>
          <td class="p-3 font-mono text-sm">${escapeHtml(it.id || '')}</td>
          <td class="p-3">${escapeHtml(it.name || '')}</td>
          <td class="p-3">${escapeHtml(it.category || '')}</td>
          <td class="p-3">Rp ${new Intl.NumberFormat('id-ID',{minimumFractionDigits:0}).format(Number(it.price || 0))}</td>
          <td class="p-3">
            <button onclick="openEdit('${encodeURIComponent(it.id)}')" class="px-3 py-1 bg-blue-600 text-white rounded mr-2">Edit</button>
            <button onclick="del('${encodeURIComponent(it.id)}')" class="px-3 py-1 bg-red-600 text-white rounded">Hapus</button>
          </td>
        </tr>`;
      }).join("\n");
    }

    // helper escape
    function escapeHtml(str) { return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;'); }
    function escapeAttr(str){ return String(str).replace(/"/g,'%22').replace(/'/g,'%27'); }

    // modal controls
    function showModal(mode='add'){ el('mode').value = mode; el('modal').classList.remove('hidden'); el('modal').classList.add('flex'); }
    function hideModal(){ el('modal').classList.add('hidden'); el('modal').classList.remove('flex'); clearFileInput(); }

    // events
    el('btnReload').addEventListener('click', render);
    el('search').addEventListener('input', () => {
      if (window._searchTimer) clearTimeout(window._searchTimer);
      window._searchTimer = setTimeout(() => render(), 300);
    });

    el('btnAdd').addEventListener('click', ()=> {
      el('modalTitle').textContent = 'Tambah Menu';
      el('fid').disabled = false; el('fid').value = '';
      el('fname').value = ''; el('fcategory').value = 'lauk'; el('fprice').value = '';
      el('fimage').value = ''; el('fdesc').value = '';
      setPreview(null); showModal('add');
    });

    el('btnCancel').addEventListener('click', hideModal);

    // delete
    async function del(encodedId){
      const id = decodeURIComponent(encodedId);
      if (!confirm('Hapus menu ini?')) return;
      try {
        const res = await fetch('api.php?action=delete', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id })
        });
        const j = await res.json();
        alert(j.message || 'Selesai');
      } catch(err) { console.error(err); alert('Gagal menghapus. Cek console.'); }
      render();
    }
    window.del = del;

    // open edit
    async function openEdit(encodedId){
      const id = decodeURIComponent(encodedId);
      try {
        const res = await fetch('api.php?action=get&id=' + encodeURIComponent(id), { credentials: 'same-origin' });
        const j = await res.json();
        if (!j || !j.id) return alert('Item tidak ditemukan');
        el('modalTitle').textContent = 'Edit Menu';
        el('fid').value = j.id; el('fid').disabled = true;
        el('fname').value = j.name || ''; el('fcategory').value = j.category || 'lauk';
        el('fprice').value = j.price || ''; el('fdesc').value = j.description || '';
        setPreview(j.image || null);
        el('fimage').value = ''; el('fimage').dataset.current = j.image || '';
        showModal('edit');
      } catch(err) { console.error(err); alert('Gagal mengambil data. Cek console.'); }
    }
    window.openEdit = openEdit;

    // file preview handling
    const fileInput = el('fimage');
    fileInput.addEventListener('change', function(){
      if (this.files && this.files[0]) {
        const file = this.files[0];
        if (!file.type.startsWith('image/')) { alert('Pilih file gambar'); this.value=''; return; }
        const reader = new FileReader();
        reader.onload = function(e) { setPreview(e.target.result); };
        reader.readAsDataURL(file);
      } else { setPreview(null); }
    });

    function setPreview(src){
      const img = el('imgPreview'); const noPrev = el('noPreview');
      if (!src) { img.classList.add('hidden'); noPrev.classList.remove('hidden'); img.src = ''; }
      else { img.src = src; img.classList.remove('hidden'); noPrev.classList.add('hidden'); }
    }
    function clearFileInput(){ el('fimage').value = ''; el('fimage').dataset.current = ''; setPreview(null); }

    // upload image via AJAX
    async function uploadImageIfAny(){
      const input = el('fimage');
      if (input.files && input.files.length > 0) {
        const file = input.files[0];
        const fd = new FormData();
        fd.append('file', file);
        try {
          const res = await fetch('upload.php', { method: 'POST', body: fd, credentials: 'same-origin' });
          const j = await res.json();
          if (j.error) throw new Error(j.message || 'Upload gagal');
          return j.file_url;
        } catch (err) { console.error(err); alert('Gagal upload gambar: ' + (err.message || 'cek console')); return null; }
      }
      return el('fimage').dataset.current || '';
    }

    // submit create/update
    el('form').addEventListener('submit', async function(e){
      e.preventDefault();
      const mode = el('mode').value;
      const uploadedUrl = await uploadImageIfAny();
      if (uploadedUrl === null) return;
      const payload = {
        id: el('fid').value.trim(),
        name: el('fname').value.trim(),
        category: el('fcategory').value,
        price: Number(el('fprice').value) || 0,
        image: uploadedUrl || '',
        description: el('fdesc').value.trim()
      };
      const action = mode === 'add' ? 'create' : 'update';
      try {
        const res = await fetch('api.php?action=' + action, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload),
          credentials: 'same-origin'
        });
        const j = await res.json();
        if (j.error) alert(j.message || 'Terjadi kesalahan');
        else { alert(j.message || 'Selesai'); hideModal(); }
      } catch(err) { console.error(err); alert('Gagal menyimpan. Cek console.'); }
      render();
    });

    // init
    document.addEventListener('DOMContentLoaded', function(){ render(); });
  </script>
</body>
</html>
