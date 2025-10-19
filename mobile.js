// mobileMenu.js

// Ambil tombol dan menu mobile
const menuBtn = document.getElementById('menuBtn');
const mobileMenu = document.getElementById('mobileMenu');

if (menuBtn && mobileMenu) {
  menuBtn.addEventListener('click', () => {
    if (mobileMenu.classList.contains('hidden')) {
      // tampilkan menu dengan animasi slideDown
      mobileMenu.classList.remove('hidden');
      mobileMenu.classList.add('animate-slideDown');
    } else {
      // sembunyikan menu dengan animasi slideUp
      mobileMenu.classList.add('animate-slideUp');
      mobileMenu.addEventListener('animationend', () => {
        mobileMenu.classList.add('hidden');
        mobileMenu.classList.remove('animate-slideDown', 'animate-slideUp');
      }, { once: true });
    }
  });
}
