
let menuData = [];
async function loadMenuFromServer() {
    try {
        const res = await fetch("api.php?action=list");
        menuData = await res.json();
        displayMenu(currentFilter);
    } catch (e) {
        console.error("Gagal memuat menu", e);
    }
}

// Konfigurasi WhatsApp
const whatsappConfig = {
    number: '6285165375085', // Nomor WhatsApzp restoran
    message: 'bang ajo, saya ingin pesan:'
};

// State management
let currentUser = null;
let cart = [];
let currentFilter = 'all';

// DOM Content Loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

// Initialize aplikasi
async function initializeApp() {
    checkAuthStatus();
    loadCartFromStorage();
    updateCartDisplay();

    await loadMenuFromServer(); // ← Penting

    if (document.getElementById('menuGrid')) {
        displayMenu();
    }
    
    if (document.getElementById('cartItems')) {
        displayCart();
    }
}

document.addEventListener("DOMContentLoaded", initializeApp);




function showRegister() {
    document.getElementById('loginForm').classList.add('hidden');
    document.getElementById('registerForm').classList.remove('hidden');
}

// Show login form
function showLogin() {
    document.getElementById('registerForm').classList.add('hidden');
    document.getElementById('loginForm').classList.remove('hidden');
}

// Validasi password real-time
function validatePassword() {
    const password = document.getElementById('registerPassword').value;
    const registerBtn = document.getElementById('registerBtn');
    
    // Kriteria validasi
    const checks = {
        length: password.length >= 8 && password.length <= 12,
        uppercase: /[A-Z]/.test(password),
        lowercase: /[a-z]/.test(password),
        number: /[0-9]/.test(password),
        special: /[@#$!]/.test(password)
    };
    
    // Update indikator visual
    updatePasswordCheck('lengthCheck', checks.length);
    updatePasswordCheck('uppercaseCheck', checks.uppercase);
    updatePasswordCheck('lowercaseCheck', checks.lowercase);
    updatePasswordCheck('numberCheck', checks.number);
    updatePasswordCheck('specialCheck', checks.special);
    
    // Enable/disable tombol register
    const allValid = Object.values(checks).every(check => check);
    
    if (allValid) {
        registerBtn.disabled = false;
        registerBtn.classList.remove('bg-gray-400', 'cursor-not-allowed');
        registerBtn.classList.add('bg-maroon', 'hover:bg-red-800', 'cursor-pointer');
    } else {
        registerBtn.disabled = true;
        registerBtn.classList.add('bg-gray-400', 'cursor-not-allowed');
        registerBtn.classList.remove('bg-maroon', 'hover:bg-red-800', 'cursor-pointer');
    }
}

// Update password check indicator
function updatePasswordCheck(elementId, isValid) {
    const element = document.getElementById(elementId);
    const icon = element.querySelector('span');
    
    if (isValid) {
        icon.textContent = '✅';
        element.classList.add('text-green-600');
        element.classList.remove('text-red-600');
    } else {
        icon.textContent = '❌';
        element.classList.add('text-red-600');
        element.classList.remove('text-green-600');
    }
}

// Register function
function register() {
    const name = document.getElementById('registerName').value.trim();
    const email = document.getElementById('registerEmail').value.trim();
    const password = document.getElementById('registerPassword').value;
    
    // Validasi input
    if (!name || !email || !password) {
        showMessage('Semua field harus diisi!', 'error');
        return;
    }
    
    // Cek apakah user sudah terdaftar
    const existingUsers = JSON.parse(localStorage.getItem('users') || '[]');
    const userExists = existingUsers.some(user => user.email === email);
    
    if (userExists) {
        showMessage('Email/nomor telepon sudah terdaftar!', 'error');
        return;
    }
    
    // Simpan user baru
    const newUser = {
        id: Date.now(),
        name: name,
        email: email,
        password: btoa(password), // Simple encoding
        registeredAt: new Date().toISOString()
    };
    
    existingUsers.push(newUser);
    localStorage.setItem('users', JSON.stringify(existingUsers));
    
    showMessage('Registrasi berhasil! Silakan login.', 'success');
    
    // Reset form dan kembali ke login
    document.getElementById('registerForm').reset();
    setTimeout(() => {
        showLogin();
    }, 1500);
}

// Login function
function login() {
    const email = document.getElementById('loginEmail').value.trim();
    const password = document.getElementById('loginPassword').value;
    
    if (!email || !password) {
        showMessage('Email dan password harus diisi!', 'error');
        return;
    }
    
    // Cek user di localStorage
    const users = JSON.parse(localStorage.getItem('users') || '[]');
    const user = users.find(u => u.email === email && atob(u.password) === password);
    
    if (user) {
        // Login berhasil
        currentUser = user;
        localStorage.setItem('currentUser', JSON.stringify(user));
        showMessage('Login berhasil! Mengalihkan...', 'success');
        
        setTimeout(() => {
            window.location.href = 'home.html';
        }, 1000);
    } else {
        showMessage('Email/nomor telepon atau password salah!', 'error');
    }
}

// Logout function
function logout() {
    localStorage.removeItem('currentUser');
    currentUser = null;
    cart = [];
    localStorage.removeItem('cart');
    window.location.href = 'index.php';
}

// === MENU FUNCTIONS ===

// Load menu
function loadMenu() {
    displayMenu(currentFilter);
}

// Display menu berdasarkan filter
// === DISPLAY MENU (Dengan gambar + animasi) ===
function displayMenu(filter = 'all') {
    const menuGrid = document.getElementById('menuGrid');
    if (!menuGrid) return;

    const filteredMenu = filter === 'all' ? menuData : menuData.filter(item => item.category === filter);

    // Animasi slide up dulu
    const cards = menuGrid.querySelectorAll('.menu-card');
    cards.forEach((card, i) => {
        card.style.animationDelay = `${i * 50}ms`;
        card.classList.add('animate-slideUp');
    });

    setTimeout(() => {
        menuGrid.innerHTML = '';
        filteredMenu.forEach((item, i) => {
            const card = document.createElement('div');
            card.className = `menu-card bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition duration-300 transform hover:-translate-y-2 animate-slideDown`;
            card.style.animationDelay = `${i * 100}ms`;

            card.innerHTML = `
                <div class="h-48 bg-gradient-to-br from-red-500 to-red-700 flex items-center justify-center overflow-hidden">
                    <img src="${item.image}" alt="${item.name}" 
                         class="w-full h-full object-cover"
                         onerror="this.src='https://via.placeholder.com/300x200/4B0000/FFD700?text=${encodeURIComponent(item.name)}'">
                </div>
                <div class="p-6">
                    <h3 class="text-xl font-bold text-white mb-2">${item.name}</h3>
                    <p class="text-gray-200 text-sm mb-4">${item.description}</p>
                    <p class="text-2xl font-bold text-gold mb-4">Rp ${item.price.toLocaleString('id-ID')}</p>
                    <div class="space-y-2">
                        <button onclick="addToCart('${item.id}', ${item.price})" 
                                class="w-full bg-maroon text-white py-2 rounded-lg hover:bg-red-800 transition duration-300 font-medium">
                            Tambah ke Keranjang
                        </button>
                        <button onclick="orderNow('${item.id}', ${item.price})" 
                                class="w-full bg-green-600 text-white py-2 rounded-lg hover:bg-green-700 transition duration-300 font-medium">
                            Pesan Sekarang
                        </button>
                    </div>
                </div>
            `;
            menuGrid.appendChild(card);
        });
    }, cards.length > 0 ? 400 : 0);
}

function filterMenu(category) {
    currentFilter = category;

    // Update tombol aktif
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.classList.remove('active', 'bg-maroon', 'text-white');
        btn.classList.add('bg-white', 'text-maroon', 'border-2', 'border-maroon');
    });

    // Cari tombol yang sesuai kategori
    const activeBtn = Array.from(document.querySelectorAll('.filter-btn'))
        .find(btn => btn.getAttribute('onclick').includes(`'${category}'`));
    
    if (activeBtn) {
        activeBtn.classList.add('active', 'bg-maroon', 'text-white');
        activeBtn.classList.remove('bg-white', 'text-maroon', 'border-2', 'border-maroon');
    }

    displayMenu(category);
}


function checkAuthStatus() {
    const user = localStorage.getItem('currentUser');
    if (user) {
        currentUser = JSON.parse(user);
        const userNameElements = document.querySelectorAll('#userName');
        userNameElements.forEach(el => el.textContent = currentUser.name);
    } else {
        currentUser = null;
    }
}

function addToCart(itemId, price) {
    const menuItem = menuData.find(item => item.id === itemId);
    if (!menuItem) return;
    
    const existingItem = cart.find(item => item.id === itemId);
    
    if (existingItem) {
        existingItem.quantity += 1;
    } else {
        cart.push({
            id: itemId,
            name: menuItem.name,
            price: price,
            quantity: 1,
            image: menuItem.image  // ← tambahkan ini
        });
    }
    
    saveCartToStorage();
    updateCartDisplay();
    showMessage(`${menuItem.name} ditambahkan ke keranjang!`, 'success');
}


function orderNow(itemId, price) {
    const menuItem = menuData.find(item => item.id === itemId);
    if (!menuItem) return;
    
    const message = `${whatsappConfig.message}\n- ${menuItem.name} 1 porsi (Rp ${price.toLocaleString('id-ID')})\nTotal: Rp ${price.toLocaleString('id-ID')}`;
    const whatsappUrl = `https://wa.me/${whatsappConfig.number}?text=${encodeURIComponent(message)}`;
    
    window.open(whatsappUrl, '_blank');
}


function updateCartDisplay() {
    const cartCount = document.getElementById('cartCount');
    if (cartCount) {
        const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
        cartCount.textContent = totalItems;
    }
}


function saveCartToStorage() {
    localStorage.setItem('cart', JSON.stringify(cart));
}


function loadCartFromStorage() {
    const savedCart = localStorage.getItem('cart');
    if (savedCart) {
        cart = JSON.parse(savedCart);
    }
}
function displayCart() {
    const emptyCart = document.getElementById('emptyCart');
    const cartItems = document.getElementById('cartItems');
    const cartItemsList = document.getElementById('cartItemsList');
    
    if (cart.length === 0) {
        emptyCart.classList.remove('hidden');
        cartItems.classList.add('hidden');
        return;
    }
    
    emptyCart.classList.add('hidden');
    cartItems.classList.remove('hidden');
    
    cartItemsList.innerHTML = cart.map(cartItem => {
        // Ambil data lengkap dari menuData
        const menuItem = menuData.find(item => item.id === cartItem.id);
        if (!menuItem) return '';

        return `
            <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg cart-item bg-white/10 backdrop-blur-sm">
                <div class="flex items-center space-x-4">
                    <div class="w-16 h-16 rounded-lg overflow-hidden bg-gradient-to-br from-red-500 to-red-700">
                        <img src="${menuItem.image}" alt="${menuItem.name}" 
                             class="w-full h-full object-cover"
                             onerror="this.src='https://via.placeholder.com/64/4B0000/FFD700?text=${menuItem.name.charAt(0)}'">
                    </div>
                    <div>
                        <h4 class="font-bold text-white">${menuItem.name}</h4>
                        <p class="text-gray-300 text-sm">Rp ${menuItem.price.toLocaleString('id-ID')} / porsi</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-2">
                        <button onclick="updateCartQuantity('${cartItem.id}', ${cartItem.quantity - 1})" 
                                class="bg-gray-200 text-gray-700 w-8 h-8 rounded-full hover:bg-gray-300 transition duration-300 text-lg font-bold">-</button>
                        <span class="font-bold text-lg w-8 text-center text-white">${cartItem.quantity}</span>
                        <button onclick="updateCartQuantity('${cartItem.id}', ${cartItem.quantity + 1})" 
                                class="bg-maroon text-white w-8 h-8 rounded-full hover:bg-red-800 transition duration-300 text-lg font-bold">+</button>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-gold">Rp ${(menuItem.price * cartItem.quantity).toLocaleString('id-ID')}</p>
                        <button onclick="removeFromCart('${cartItem.id}')" class="text-red-400 hover:text-red-600 text-sm">Hapus</button>
                    </div>
                </div>
            </div>
        `;
    }).join('');
    
    updateCartTotals();
}


function updateCartQuantity(itemId, newQuantity) {
    if (newQuantity <= 0) {
        removeFromCart(itemId);
        return;
    }
    
    const item = cart.find(item => item.id === itemId);
    if (item) {
        item.quantity = newQuantity;
        saveCartToStorage();
        updateCartDisplay();
        displayCart();
    }
}


function removeFromCart(itemId) {
    cart = cart.filter(item => item.id !== itemId);
    saveCartToStorage();
    updateCartDisplay();
    displayCart();
    showMessage('Item dihapus dari keranjang!', 'success');
}


function clearCart() {
    if (confirm('Apakah Anda yakin ingin mengosongkan keranjang?')) {
        cart = [];
        saveCartToStorage();
        updateCartDisplay();
        displayCart();
        showMessage('Keranjang berhasil dikosongkan!', 'success');
    }
}

function updateCartTotals() {
    const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    
    document.getElementById('subtotal').textContent = `Rp ${subtotal.toLocaleString('id-ID')}`;
    document.getElementById('total').textContent = `Rp ${subtotal.toLocaleString('id-ID')}`;
}
function orderViaWhatsApp() {
    if (cart.length === 0) {
        showMessage('Keranjang kosong!', 'error');
        return;
    }
    
    let message = whatsappConfig.message + '\n';
    let total = 0;
    
    cart.forEach(cartItem => {
        const menuItem = menuData.find(m => m.id === cartItem.id);
        if (menuItem) {
            const itemTotal = menuItem.price * cartItem.quantity;
            message += `- ${menuItem.name} ${cartItem.quantity} porsi (Rp ${itemTotal.toLocaleString('id-ID')})\n`;
            total += itemTotal;
        }
    });
    
    message += `\nTotal: Rp ${total.toLocaleString('id-ID')}`;
    
    const whatsappUrl = `https://wa.me/${whatsappConfig.number}?text=${encodeURIComponent(message)}`;
    window.open(whatsappUrl, '_blank');
}
function toggleMobileMenu() {
    const mobileMenu = document.getElementById('mobileMenu');
    mobileMenu.classList.toggle('hidden');
}


function showMessage(message, type = 'info') {

    const existingMessages = document.querySelectorAll('.message');
    existingMessages.forEach(msg => msg.remove());
    
 
    const messageDiv = document.createElement('div');
    messageDiv.className = `message fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg ${
        type === 'success' ? 'bg-green-500 text-white' : 
        type === 'error' ? 'bg-red-500 text-white' : 
        'bg-blue-500 text-white'
    }`;
    messageDiv.textContent = message;
    
    document.body.appendChild(messageDiv);

    setTimeout(() => {
        messageDiv.remove();
    }, 3000);
}


function formatCurrency(amount) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(amount);
}


function smoothScroll(target) {
    document.querySelector(target).scrollIntoView({
        behavior: 'smooth'
    });
}

function initializeTooltips() {
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(element => {
        element.addEventListener('mouseenter', showTooltip);
        element.addEventListener('mouseleave', hideTooltip);
    });
}

function showTooltip(event) {
    const tooltip = document.createElement('div');
    tooltip.className = 'absolute bg-gray-800 text-white px-2 py-1 rounded text-sm z-50';
    tooltip.textContent = event.target.getAttribute('data-tooltip');
    
    document.body.appendChild(tooltip);
    
    const rect = event.target.getBoundingClientRect();
    tooltip.style.left = rect.left + 'px';
    tooltip.style.top = (rect.top - tooltip.offsetHeight - 5) + 'px';
    
    event.target._tooltip = tooltip;
}

function hideTooltip(event) {
    if (event.target._tooltip) {
        event.target._tooltip.remove();
        delete event.target._tooltip;
    }
}


function initializeLazyLoading() {
    const images = document.querySelectorAll('img[data-src]');
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.remove('lazy');
                observer.unobserve(img);
            }
        });
    });
    
    images.forEach(img => imageObserver.observe(img));
}


if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then(registration => {
                console.log('SW registered: ', registration);
            })
            .catch(registrationError => {
                console.log('SW registration failed: ', registrationError);
            });
    });
}

function measurePerformance() {
    if ('performance' in window) {
        window.addEventListener('load', () => {
            const loadTime = performance.timing.loadEventEnd - performance.timing.navigationStart;
            console.log(`Page load time: ${loadTime}ms`);
        });
    }
}


measurePerformance();


window.addEventListener('error', (event) => {
    console.error('Global error:', event.error);

});


window.addEventListener('unhandledrejection', (event) => {
    console.error('Unhandled promise rejection:', event.reason);
    event.preventDefault();
});
function logout() {
   
    if (confirm("Apakah Anda yakin ingin keluar?")) {
        
        localStorage.removeItem('currentUser');

        window.location.href = "logout.php";
    }

}