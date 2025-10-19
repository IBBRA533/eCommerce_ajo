// JavaScript untuk Website Rumah Makan Nasi Padang

// Data menu lengkap
const menuData = [
   
    // Lauk Pauk
    { 
        id: 'rendang', 
        name: 'Rendang Daging', 
        price: 35000, 
        category: 'lauk', 
      
        description: 'Daging sapi yang dimasak dengan santan dan rempah-rempah khas Minang',
        image: 'img/rendang.jpg'   // ✅ tambahkan ini
    },
    { 
        id: 'ayam-pop', 
        name: 'Ayam Pop', 
        price: 28000, 
        category: 'lauk', 
        emoji: '🍗', 
        description: 'Ayam kampung yang dimasak dengan bumbu kuning khas Padang',
        image: 'img/ayam-pop.jpg'  // ✅ tambahkan ini
    },
    { 
        id: 'gulai-kambing', 
        name: 'Gulai Kambing', 
        price: 40000, 
        category: 'lauk', 
        emoji: '🍲', 
        description: 'Gulai kambing dengan kuah santan yang gurih dan kaya rempah',
        image: 'gambar/1.jpg'
    },
    
];

// Konfigurasi WhatsApp
const whatsappConfig = {
    number: '6285165375085', // Nomor WhatsApp restoran
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
function initializeApp() {
    checkAuthStatus();
    loadCartFromStorage();
    updateCartDisplay();
    
    // Load menu jika di halaman menu
    if (document.getElementById('menuGrid')) {
        loadMenu();
    }
    
    // Load cart jika di halaman cart
    if (document.getElementById('cartItems')) {
        displayCart();
    }
}

// === AUTHENTICATION FUNCTIONS ===

// Cek status login


// Show register form
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
function displayMenu(filter = 'all') {
    const menuGrid = document.getElementById('menuGrid');
    if (!menuGrid) return;
    
    const filteredMenu = filter === 'all' ? menuData : menuData.filter(item => item.category === filter);
    
   menuGrid.innerHTML = filteredMenu.map(item => `
    <div class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition duration-300 transform hover:-translate-y-2 card-hover">
        <div class="h-48 bg-gray-200 flex items-center justify-center overflow-hidden">
            ${item.image 
                ? `<img src="${item.image}" alt="${item.name}" class="w-full h-full object-cover">` 
                : `<span class="text-6xl">${item.emoji}</span>`}
        </div>
        <div class="p-6">
            <h3 class="text-xl font-bold text-maroon mb-2">${item.name}</h3>
            <p class="text-gray-600 text-sm mb-4">${item.description}</p>
            <p class="text-2xl font-bold text-gold mb-4">Rp ${item.price.toLocaleString('id-ID')}</p>
            <div class="space-y-2">
                <button onclick="addToCart('${item.id}', ${item.price})" class="w-full bg-maroon text-white py-2 rounded-lg hover:bg-red-800 transition duration-300 font-medium">
                    Tambah ke Keranjang
                </button>
                <button onclick="orderNow('${item.id}', ${item.price})" class="w-full bg-green-600 text-white py-2 rounded-lg hover:bg-green-700 transition duration-300 font-medium">
                    Pesan Sekarang
                </button>
            </div>
        </div>
    </div>
`).join('');

}

// Filter menu
function filterMenu(category, event) {
    currentFilter = category;

    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.classList.remove('active', 'bg-maroon', 'text-white');
        btn.classList.add('bg-white', 'text-maroon', 'border-2', 'border-maroon');
    });

    event.target.classList.add('active', 'bg-maroon', 'text-white');
    event.target.classList.remove('bg-white', 'text-maroon', 'border-2', 'border-maroon');

    displayMenu(category);
}


// === CART FUNCTIONS ===
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

// Add to cart
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
            emoji: menuItem.emoji
        });
    }
    
    saveCartToStorage();
    updateCartDisplay();
    showMessage(`${menuItem.name} ditambahkan ke keranjang!`, 'success');
}

// Order now (langsung ke WhatsApp)
function orderNow(itemId, price) {
    const menuItem = menuData.find(item => item.id === itemId);
    if (!menuItem) return;
    
    const message = `${whatsappConfig.message}\n- ${menuItem.name} 1 porsi (Rp ${price.toLocaleString('id-ID')})\nTotal: Rp ${price.toLocaleString('id-ID')}`;
    const whatsappUrl = `https://wa.me/${whatsappConfig.number}?text=${encodeURIComponent(message)}`;
    
    window.open(whatsappUrl, '_blank');
}

// Update cart display
function updateCartDisplay() {
    const cartCount = document.getElementById('cartCount');
    if (cartCount) {
        const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
        cartCount.textContent = totalItems;
    }
}

// Save cart to localStorage
function saveCartToStorage() {
    localStorage.setItem('cart', JSON.stringify(cart));
}

// Load cart from localStorage
function loadCartFromStorage() {
    const savedCart = localStorage.getItem('cart');
    if (savedCart) {
        cart = JSON.parse(savedCart);
    }
}

// Display cart page
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
    
    // Display cart items
    cartItemsList.innerHTML = cart.map(item => `
        <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg cart-item">
            <div class="flex items-center space-x-4">
                <span class="text-3xl">${item.emoji}</span>
                <div>
                    <h4 class="font-bold text-maroon">${item.name}</h4>
                    <p class="text-gray-600">Rp ${item.price.toLocaleString('id-ID')}</p>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <div class="flex items-center space-x-2">
                    <button onclick="updateCartQuantity('${item.id}', ${item.quantity - 1})" class="bg-gray-200 text-gray-700 w-8 h-8 rounded-full hover:bg-gray-300 transition duration-300">-</button>
                    <span class="font-bold text-lg w-8 text-center">${item.quantity}</span>
                    <button onclick="updateCartQuantity('${item.id}', ${item.quantity + 1})" class="bg-maroon text-white w-8 h-8 rounded-full hover:bg-red-800 transition duration-300">+</button>
                </div>
                <div class="text-right">
                    <p class="font-bold text-maroon">Rp ${(item.price * item.quantity).toLocaleString('id-ID')}</p>
                    <button onclick="removeFromCart('${item.id}')" class="text-red-500 hover:text-red-700 text-sm">Hapus</button>
                </div>
            </div>
        </div>
    `).join('');
    
    // Update totals
    updateCartTotals();
}

// Update cart quantity
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

// Remove from cart
function removeFromCart(itemId) {
    cart = cart.filter(item => item.id !== itemId);
    saveCartToStorage();
    updateCartDisplay();
    displayCart();
    showMessage('Item dihapus dari keranjang!', 'success');
}

// Clear cart
function clearCart() {
    if (confirm('Apakah Anda yakin ingin mengosongkan keranjang?')) {
        cart = [];
        saveCartToStorage();
        updateCartDisplay();
        displayCart();
        showMessage('Keranjang berhasil dikosongkan!', 'success');
    }
}

// Update cart totals
function updateCartTotals() {
    const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    
    document.getElementById('subtotal').textContent = `Rp ${subtotal.toLocaleString('id-ID')}`;
    document.getElementById('total').textContent = `Rp ${subtotal.toLocaleString('id-ID')}`;
}

// Order via WhatsApp
function orderViaWhatsApp() {
    if (cart.length === 0) {
        showMessage('Keranjang kosong!', 'error');
        return;
    }
    
    let message = whatsappConfig.message + '\n';
    let total = 0;
    
    cart.forEach(item => {
        const itemTotal = item.price * item.quantity;
        message += `- ${item.name} ${item.quantity} porsi (Rp ${itemTotal.toLocaleString('id-ID')})\n`;
        total += itemTotal;
    });
    
    message += `\nTotal: Rp ${total.toLocaleString('id-ID')}`;
    
    const whatsappUrl = `https://wa.me/${whatsappConfig.number}?text=${encodeURIComponent(message)}`;
    window.open(whatsappUrl, '_blank');
}

// === UTILITY FUNCTIONS ===

// Toggle mobile menu
function toggleMobileMenu() {
    const mobileMenu = document.getElementById('mobileMenu');
    mobileMenu.classList.toggle('hidden');
}

// Show message
function showMessage(message, type = 'info') {
    // Remove existing messages
    const existingMessages = document.querySelectorAll('.message');
    existingMessages.forEach(msg => msg.remove());
    
    // Create message element
    const messageDiv = document.createElement('div');
    messageDiv.className = `message fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg ${
        type === 'success' ? 'bg-green-500 text-white' : 
        type === 'error' ? 'bg-red-500 text-white' : 
        'bg-blue-500 text-white'
    }`;
    messageDiv.textContent = message;
    
    document.body.appendChild(messageDiv);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        messageDiv.remove();
    }, 3000);
}

// Format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(amount);
}

// Smooth scroll
function smoothScroll(target) {
    document.querySelector(target).scrollIntoView({
        behavior: 'smooth'
    });
}

// Initialize tooltips (if needed)
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

// Lazy loading images (if needed)
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

// Service Worker registration (for PWA features)
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

// Performance monitoring
function measurePerformance() {
    if ('performance' in window) {
        window.addEventListener('load', () => {
            const loadTime = performance.timing.loadEventEnd - performance.timing.navigationStart;
            console.log(`Page load time: ${loadTime}ms`);
        });
    }
}

// Initialize performance monitoring
measurePerformance();

// Error handling
window.addEventListener('error', (event) => {
    console.error('Global error:', event.error);
    // Could send to error tracking service
});

// Unhandled promise rejection handling
window.addEventListener('unhandledrejection', (event) => {
    console.error('Unhandled promise rejection:', event.reason);
    event.preventDefault();
});
function logout() {
    // Tampilkan konfirmasi sebelum logout (opsional)
    if (confirm("Apakah Anda yakin ingin keluar?")) {
        // Hapus data user dari localStorage (kalau kamu pakai login JS)
        localStorage.removeItem('currentUser');

        // Redirect ke file logout PHP untuk hapus session
        window.location.href = "logout.php";
    }

}