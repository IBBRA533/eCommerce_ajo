// script.js — versi final terintegrasi (replace your old script.js)
(() => {
  // ----- Config -----
  const whatsappConfig = {
    number: '6285165375085' // <-- sesuaikan nomor tujuan WA
  };

  // ----- State -----
  let menuData = [];
  let cart = [];

  // ----- Utils -----
  const el = id => document.getElementById(id);
  function escapeHtml(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;'); }
  function showMessage(message, type='info') {
    document.querySelectorAll('.message').forEach(m => m.remove());
    const div = document.createElement('div');
    div.className = `message fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg ${ type==='success' ? 'bg-green-500 text-white' : type==='error' ? 'bg-red-500 text-white' : 'bg-blue-600 text-white' }`;
    div.textContent = message;
    document.body.appendChild(div);
    setTimeout(()=> div.remove(), 3000);
  }
  function fmtRp(n){ return 'Rp ' + Number(n||0).toLocaleString('id-ID'); }

  // ----- Storage helpers -----
  function loadCartFromStorage(){ try { cart = JSON.parse(localStorage.getItem('cart') || '[]') || []; } catch(e){ cart = []; } }
  function saveCartToStorage(){ localStorage.setItem('cart', JSON.stringify(cart)); updateCartUI(); }
  function clearCartLocal(){ cart = []; localStorage.removeItem('cart'); updateCartUI(); }

  // ----- Menu loader (robust) -----
  async function loadMenuFromServer(){
    try {
      const res = await fetch('api.php?action=list', { credentials:'same-origin' });
      const j = await res.json().catch(()=>null);
      if (Array.isArray(j)) menuData = j;
      else if (j && Array.isArray(j.data)) menuData = j.data;
      else if (j && Array.isArray(j.rows)) menuData = j.rows;
      else menuData = [];
      // optional: render menu if page has it
      if (document.getElementById('menuGrid')) displayMenu('all');
    } catch(err){
      console.error('loadMenuFromServer error', err);
      menuData = [];
    }
  }

  // ----- Render menu (kept simple) -----
  function displayMenu(filter='all'){
    const menuGrid = document.getElementById('menuGrid');
    if (!menuGrid) return;
    const list = filter==='all' ? menuData : menuData.filter(m => (m.category||'').toLowerCase() === filter.toLowerCase());
    menuGrid.innerHTML = '';
    list.forEach((item, idx) => {
      const price = Number(item.price||0);
      const card = document.createElement('div');
      card.className = 'menu-card bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl shadow-lg overflow-hidden';
      card.innerHTML = `
        <div class="h-48 bg-gradient-to-br from-red-500 to-red-700 flex items-center justify-center overflow-hidden">
          <img src="${item.image||''}" alt="${escapeHtml(item.name||'')}" class="w-full h-full object-cover"
               onerror="this.src='https://via.placeholder.com/300x200/4B0000/FFD700?text=${encodeURIComponent(item.name||'')}'">
        </div>
        <div class="p-4">
          <h3 class="text-lg font-bold text-white">${escapeHtml(item.name||'')}</h3>
          <p class="text-sm text-gray-200">${escapeHtml(item.description||'')}</p>
          <div class="mt-3 flex gap-2">
            <button class="add-btn px-3 py-2 rounded bg-maroon text-white" data-id="${escapeHtml(String(item.id))}">Tambah</button>
            <button class="quick-btn px-3 py-2 rounded bg-green-600 text-white" data-id="${escapeHtml(String(item.id))}">Pesan Sekarang</button>
          </div>
          <div class="mt-2 font-bold text-gold">${fmtRp(price)}</div>
        </div>
      `;
      menuGrid.appendChild(card);
    });

    // delegate events
    menuGrid.querySelectorAll('.add-btn').forEach(b => b.addEventListener('click', e => {
      const id = e.currentTarget.dataset.id; addToCart(id);
    }));
    menuGrid.querySelectorAll('.quick-btn').forEach(b => b.addEventListener('click', e => {
      const id = e.currentTarget.dataset.id; quickOrderModal(id);
    }));
  }

  // ----- Cart management -----
  function addToCart(itemId, qty=1){
    const item = menuData.find(m => String(m.id) === String(itemId));
    if (!item) {
      showMessage('Item tidak ditemukan di menu', 'error');
      return;
    }
    const existing = cart.find(c => String(c.id) === String(itemId));
    if (existing) existing.quantity = (Number(existing.quantity)||0) + qty;
    else cart.push({ id: String(itemId), name: item.name||'', price: Number(item.price||0), quantity: qty, image: item.image||'' });
    saveCartToStorage();
    showMessage(`${item.name} ditambahkan ke keranjang`, 'success');
  }
  function updateCartQuantity(itemId, newQty){
    if (newQty <= 0) { removeFromCart(itemId); return; }
    const it = cart.find(c => String(c.id) === String(itemId));
    if (!it) return;
    it.quantity = newQty;
    saveCartToStorage();
  }
  function removeFromCart(itemId){
    cart = cart.filter(c => String(c.id) !== String(itemId));
    saveCartToStorage();
    showMessage('Item dihapus dari keranjang', 'info');
  }

  // ----- UI sync (cart) -----
  function updateCartUI(){
    // cart count
    const cartCount = document.getElementById('cartCount');
    if (cartCount) cartCount.textContent = cart.reduce((s,i) => s + (Number(i.quantity)||0), 0);

    // cart display elements
    const emptyCart = document.getElementById('emptyCart');
    const cartItemsWrap = document.getElementById('cartItems');
    const cartList = document.getElementById('cartItemsList');
    if (!emptyCart || !cartItemsWrap || !cartList) return;

    if (!cart || cart.length === 0) {
      emptyCart.classList.remove('hidden');
      cartItemsWrap.classList.add('hidden');
      cartList.innerHTML = '';
      updateTotals();
      return;
    }
    emptyCart.classList.add('hidden');
    cartItemsWrap.classList.remove('hidden');

    cartList.innerHTML = cart.map(ci => {
      const price = Number(ci.price||0);
      const qty = Number(ci.quantity||1);
      const s = price * qty;
      return `
        <div class="flex items-center justify-between p-4 border rounded-lg bg-white/80">
          <div class="flex items-center gap-4">
            <div class="w-16 h-16 rounded overflow-hidden">
              <img src="${ci.image||''}" alt="${escapeHtml(ci.name||'')}" class="w-full h-full object-cover" onerror="this.src='https://via.placeholder.com/64/4B0000/FFD700?text=${encodeURIComponent((ci.name||'').charAt(0))}'" />
            </div>
            <div>
              <div class="font-semibold">${escapeHtml(ci.name||'')}</div>
              <div class="text-sm">Rp ${price.toLocaleString('id-ID')} / porsi</div>
            </div>
          </div>
          <div class="flex items-center gap-4">
            <div class="flex items-center gap-2">
              <button onclick="(function(){ window.scriptAPI.updateCartQuantity('${escapeHtml(ci.id)}', ${qty-1}) })()" class="qty-decr bg-gray-100 w-8 h-8 rounded-full">-</button>
              <div class="w-8 text-center font-bold">${qty}</div>
              <button onclick="(function(){ window.scriptAPI.updateCartQuantity('${escapeHtml(ci.id)}', ${qty+1}) })()" class="qty-incr bg-maroon text-white w-8 h-8 rounded-full">+</button>
            </div>
            <div class="text-right">
              <div class="font-bold text-maroon">${fmtRp(s)}</div>
              <button onclick="(function(){ window.scriptAPI.removeFromCart('${escapeHtml(ci.id)}') })()" class="text-sm text-red-500">Hapus</button>
            </div>
          </div>
        </div>
      `;
    }).join('');
    updateTotals();
  }

  // expose for inline onclick handlers used above
  window.scriptAPI = {
    updateCartQuantity,
    removeFromCart
  };

  function updateTotals(){
    const subtotal = cart.reduce((s,it) => s + (Number(it.price||0) * Number(it.quantity||0)), 0);
    const elSubtotal = document.getElementById('subtotal');
    const elTotal = document.getElementById('total');
    if (elSubtotal) elSubtotal.textContent = fmtRp(subtotal);
    if (elTotal) elTotal.textContent = fmtRp(subtotal);
    // modal totals if present
    const modalSub = document.getElementById('modalSubtotal');
    const modalTot = document.getElementById('modalTotal');
    if (modalSub) modalSub.textContent = fmtRp(subtotal);
    if (modalTot) modalTot.textContent = fmtRp(subtotal);
  }

  // ----- Build WA message lines -----
  function buildWALines(items, customerName='', phone='', prefixText=''){
    const lines = [];
    if (prefixText) lines.push(prefixText);
    else {
      lines.push(`Pesanan dari: ${customerName || 'Pengunjung'}`);
      if (phone) lines.push(`Nomor: ${phone}`);
      lines.push('');
      lines.push('Daftar pesanan:');
    }
    let total = 0;
    items.forEach(it => {
      const qty = Number(it.quantity || it.qty || 1);
      const price = Number(it.price || 0);
      const s = qty * price;
      total += s;
      lines.push(`- ${it.name || ''} x${qty} (Rp ${price.toLocaleString('id-ID')}) — Rp ${s.toLocaleString('id-ID')}`);
    });
    lines.push('');
    lines.push(`Total: Rp ${total.toLocaleString('id-ID')}`);
    lines.push('');
    lines.push('Mohon konfirmasi ya, terima kasih.');
    return { text: lines.join('\n'), total };
  }

  // ----- Open WA (new tab) -----
  function openWhatsAppWithText(text){
    const number = whatsappConfig.number || '6285165375085';
    const url = `https://wa.me/${number}?text=${encodeURIComponent(text)}`;
    window.open(url, '_blank');
  }

  // ----- Quick order single item (modal flow) -----
  async function quickOrderModal(itemId){
    const menuItem = menuData.find(m => String(m.id) === String(itemId));
    if (!menuItem) { showMessage('Menu tidak ditemukan', 'error'); return; }
    const modalRes = await showOrderModal({ name: localStorage.getItem('lastOrderName') || 'Pengunjung', phone: localStorage.getItem('lastOrderPhone') || '', note: '' });
    if (!modalRes || !modalRes.ok) return;

    // prepare payload single item
    const payload = { customer_name: modalRes.name, phone: modalRes.phone, items: [{ id: String(itemId), qty: 1 }], note: modalRes.note || '' };

    // try save to server
    try {
      const res = await fetch('api.php?action=order', {
        method:'POST',
        headers:{ 'Content-Type':'application/json' },
        credentials:'same-origin',
        body: JSON.stringify(payload)
      });
      const j = await res.json().catch(()=>null);
      if (res.ok && j && j.ok) {
        const orderCode = j.data?.order_code || j.order_code || '';
        // WA text with order code + item list
        const wa = buildWALines([{ name: menuItem.name, quantity: 1, price: menuItem.price }], modalRes.name, modalRes.phone, `Pesanan saya (Kode: ${orderCode || '—'})\nNama: ${modalRes.name}\nNo: ${modalRes.phone}\n\nDaftar pesanan:\n`);
        openWhatsAppWithText(wa.text);
        showMessage('Pesanan disimpan & WhatsApp dibuka', 'success');
      } else {
        // server rejected — offer to open WA anyway
        if (confirm('Gagal menyimpan ke server. Tetap buka WhatsApp untuk mengirim pesan?')) {
          const wa = buildWALines([{ name: menuItem.name, quantity: 1, price: menuItem.price }], modalRes.name, modalRes.phone);
          openWhatsAppWithText(wa.text);
        } else {
          showMessage('Pesanan dibatalkan', 'info');
        }
      }
    } catch(err) {
      console.error('quickOrderModal error', err);
      if (confirm('Koneksi gagal. Buka WhatsApp saja?')) {
        const wa = buildWALines([{ name: menuItem.name, quantity: 1, price: menuItem.price }], modalRes.name, modalRes.phone);
        openWhatsAppWithText(wa.text);
      } else {
        showMessage('Pesanan dibatalkan', 'info');
      }
    }
  }

  // ----- Modal: reusable prompt (returns Promise) -----
  function showOrderModal(defaults = { name:'', phone:'', note:'' }){
    return new Promise(resolve => {
      const wrapper = document.getElementById('orderModal');
      const backdrop = document.getElementById('orderModalBackdrop');
      const nameEl = document.getElementById('orderModalName');
      const phoneEl = document.getElementById('orderModalPhone');
      const noteEl = document.getElementById('orderModalNote');
      const errEl = document.getElementById('orderModalError');
      const btnCancel = document.getElementById('orderModalCancel');
      const btnSubmit = document.getElementById('orderModalSubmit');

      if (!wrapper || !nameEl || !phoneEl || !noteEl || !btnCancel || !btnSubmit) {
        // fallback prompts
        const n = prompt('Nama pembeli:', defaults.name || 'Pengunjung') || defaults.name || 'Pengunjung';
        const p = prompt('Nomor telepon (opsional):', defaults.phone || '') || defaults.phone || '';
        const note = prompt('Catatan (opsional):', defaults.note || '') || defaults.note || '';
        localStorage.setItem('lastOrderName', n);
        localStorage.setItem('lastOrderPhone', p);
        resolve({ ok:true, name:n, phone:p, note });
        return;
      }

      nameEl.value = defaults.name || '';
      phoneEl.value = defaults.phone || '';
      noteEl.value = defaults.note || '';
      errEl.classList.add('hidden'); errEl.textContent = '';

      wrapper.classList.add('show');
      setTimeout(()=> nameEl.focus(), 60);

      function cleanup(){
        btnCancel.removeEventListener('click', onCancel);
        btnSubmit.removeEventListener('click', onSubmit);
        backdrop.removeEventListener('click', onCancel);
        wrapper.classList.remove('show');
      }
      function onCancel(e){ e?.preventDefault(); cleanup(); resolve({ ok:false }); }
      function onSubmit(e){ e?.preventDefault();
        const name = nameEl.value.trim() || 'Pengunjung';
        const phone = phoneEl.value.trim();
        const note = noteEl.value.trim();
        if (phone && phone.replace(/\D/g,'').length < 8) {
          errEl.textContent = 'Nomor telepon tampak terlalu pendek.'; errEl.classList.remove('hidden'); phoneEl.focus(); return;
        }
        localStorage.setItem('lastOrderName', name);
        localStorage.setItem('lastOrderPhone', phone);
        cleanup();
        resolve({ ok:true, name, phone, note });
      }

      btnCancel.addEventListener('click', onCancel);
      btnSubmit.addEventListener('click', onSubmit);
      backdrop.addEventListener('click', onCancel);
      [nameEl, phoneEl].forEach(inp => inp.addEventListener('keydown', e => { if (e.key === 'Enter'){ e.preventDefault(); onSubmit(); } }));
    });
  }

  // ----- Place order with cart: try server -> WA -> clear cart -----
  async function placeOrderFromCart(){
    loadCartFromStorage();
    if (!cart || cart.length === 0) { showMessage('Keranjang kosong', 'error'); return; }

    const modalRes = await showOrderModal({ name: localStorage.getItem('lastOrderName') || 'Pengunjung', phone: localStorage.getItem('lastOrderPhone') || '', note: '' });
    if (!modalRes || !modalRes.ok) return;

    const itemsForApi = cart.map(it => ({ id: String(it.id), qty: Number(it.quantity || 1) }));
    const payload = { customer_name: modalRes.name, phone: modalRes.phone, items: itemsForApi, note: modalRes.note || '' };

    try {
      const res = await fetch('api.php?action=order', {
        method:'POST',
        headers:{ 'Content-Type':'application/json' },
        credentials:'same-origin',
        body: JSON.stringify(payload)
      });
      const j = await res.json().catch(()=>null);
      if (!res.ok || !j || j.ok === false) {
        console.error('Order API failed', res, j);
        if (!confirm('Gagal menyimpan pesanan ke server. Tetap buka WhatsApp agar user bisa mengirim pesan?')) {
          showMessage('Pesanan tidak terkirim', 'info');
          return;
        }
        // user chose to continue with WA-only: open WA with current cart and clear cart
        const wa = buildWALines(cart, modalRes.name, modalRes.phone);
        openWhatsAppWithText(wa.text);
        clearCartLocal();
        showMessage('WhatsApp dibuka. Keranjang dikosongkan (lokal).', 'success');
        return;
      }

      // success: open WA with order code and clear cart
      const orderCode = j.data?.order_code || j.order_code || '';
      const wa = buildWALines(cart, modalRes.name, modalRes.phone, `Pesanan saya (Kode: ${orderCode || '—'})\nNama: ${modalRes.name}\nNo: ${modalRes.phone}\n\nDaftar pesanan:\n`);
      openWhatsAppWithText(wa.text);
      clearCartLocal();
      showMessage('Pesanan tersimpan & WhatsApp dibuka. Keranjang dikosongkan.', 'success');

    } catch(err) {
      console.error('placeOrderFromCart error', err);
      if (confirm('Terjadi kesalahan koneksi. Buka WhatsApp saja?')) {
        const wa = buildWALines(cart, modalRes.name, modalRes.phone);
        openWhatsAppWithText(wa.text);
        clearCartLocal();
        showMessage('WhatsApp dibuka. Keranjang dikosongkan (lokal).', 'success');
      } else {
        showMessage('Pesanan ditunda', 'info');
      }
    }
  }

  // ----- Order via WhatsApp button (shortcut) -----
  async function orderViaWhatsAppButton(){
    // same flow as placeOrderFromCart (kept for semantic clarity)
    await placeOrderFromCart();
  }

  // ----- Button bindings & init -----
  document.addEventListener('DOMContentLoaded', async () => {
    // initial load
    loadCartFromStorage();
    updateCartUI();
    await loadMenuFromServer();

    // Bind top-level buttons if present
    const btnClear = document.getElementById('btnClear');
    btnClear?.addEventListener('click', () => {
      if (!confirm('Kosongkan keranjang?')) return;
      clearCartLocal();
      showMessage('Keranjang dikosongkan', 'success');
    });

    const btnOrderModal = document.getElementById('btnOrderModal');
    btnOrderModal?.addEventListener('click', async () => {
      loadCartFromStorage();
      if (!cart || cart.length === 0) { showMessage('Keranjang kosong', 'error'); return; }
      // simply open modal (showOrderModal) then place order
      await placeOrderFromCart();
    });

    const btnOpenWhats = document.getElementById('btnOpenWhats');
    btnOpenWhats?.addEventListener('click', async () => {
      loadCartFromStorage();
      if (!cart || cart.length === 0) { showMessage('Keranjang kosong', 'error'); return; }
      await orderViaWhatsAppButton();
    });

    // link modal open on quick 'open' flows if HTML had earlier handlers
    // ensure modal close btns exist (these are inside showOrderModal)

    // expose debug helpers
    window._menuData = () => menuData;
    window._cartData = () => cart;
    window._refreshMenu = loadMenuFromServer;
  });
})();
