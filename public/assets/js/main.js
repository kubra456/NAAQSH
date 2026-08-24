/**
 * NAAQŚĦ — Main Interactive Frontend Script
 */
document.addEventListener('DOMContentLoaded', function () {
  const header = document.querySelector('.site-header');
  const navToggle = document.querySelector('.nav-toggle');

  // Mobile Navigation Toggle
  if (header && navToggle) {
    navToggle.addEventListener('click', function () {
      const isOpen = header.classList.toggle('nav-open');
      navToggle.setAttribute('aria-expanded', String(isOpen));
    });

    // Close on navigation link click
    document.querySelectorAll('.nav-link, .nav-account, .main-nav .btn').forEach(function (link) {
      link.addEventListener('click', function () {
        header.classList.remove('nav-open');
        navToggle.setAttribute('aria-expanded', 'false');
      });
    });

    // Close on Escape key press
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && header.classList.contains('nav-open')) {
        header.classList.remove('nav-open');
        navToggle.setAttribute('aria-expanded', 'false');
        navToggle.focus();
      }
    });
  }

  // Scroll Header State
  let lastScroll = 0;
  window.addEventListener('scroll', function () {
    const currentScroll = window.pageYOffset;
    if (header) {
      if (currentScroll > 50) {
        header.classList.add('is-scrolled');
      } else {
        header.classList.remove('is-scrolled');
      }
    }
    lastScroll = currentScroll;
  }, { passive: true });

  // Graceful Fallback for missing/broken image files
  const curatedFallbacks = [
    'https://images.unsplash.com/photo-1519741497674-611481863552?auto=format&fit=crop&w=1200&q=80',
    'https://images.unsplash.com/photo-1520854221256-17451cc331bf?auto=format&fit=crop&w=1200&q=80',
    'https://images.unsplash.com/photo-1511285560929-80b456fea0bc?auto=format&fit=crop&w=1200&q=80',
    'https://images.unsplash.com/photo-1465495976277-4387d4b0b4c6?auto=format&fit=crop&w=1200&q=80',
    'https://images.unsplash.com/photo-1583939003579-730e3918a45a?auto=format&fit=crop&w=1200&q=80'
  ];

  document.querySelectorAll('img').forEach(function (img, idx) {
    img.addEventListener('error', function () {
      const fallback = curatedFallbacks[idx % curatedFallbacks.length];
      if (img.src !== fallback) {
        img.src = fallback;
      }
    });
  });

  // Auto-focus first field in forms
  const firstInput = document.querySelector('.contact-form input, .auth-card input');
  if (firstInput && !document.querySelector(':focus')) {
    firstInput.focus();
  }
});
