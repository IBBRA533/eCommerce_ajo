
const menuBtn = document.getElementById('menuBtn');
const mobileMenu = document.getElementById('mobileMenu');

if (menuBtn && mobileMenu) {
  menuBtn.addEventListener('click', () => {
    if (mobileMenu.classList.contains('hidden')) {
      
      mobileMenu.classList.remove('hidden');
      mobileMenu.classList.add('animate-slideDown');
    } else {
     
      mobileMenu.classList.add('animate-slideUp');
      mobileMenu.addEventListener('animationend', () => {
        mobileMenu.classList.add('hidden');
        mobileMenu.classList.remove('animate-slideDown', 'animate-slideUp');
      }, { once: true });
    }
  });
}
