<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta
      name="description"
      content="Dash Coffee is a modern coffee and comfort food destination serving premium drinks, savory silog meals, and hearty snacks in a cozy setting."
    />
    <title>@yield('title', 'Dash Coffee | Your Daily Coffee Moment')</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/dashcoffeelogo.png') }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Josefin+Sans:wght@400;500;600;700&family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}" />
      <link rel="stylesheet" href="{{ asset('css/mobile.css') }}" />
  </head>
  <body>
    <header class="site-header">
      <nav class="navbar container" aria-label="Main navigation">
        <a href="{{ route('home') }}"
          aria-label="DashCoffee brand"
          class="brand {{ request()->routeIs('home') ? 'active' : '' }}"
          style="text-decoration: none; color: inherit;">
          <img src="{{ asset('assets/dashcoffee-logo.png') }}" alt="DashCoffee logo" class="brand-logo" />
          <span class="brand-text">DASH COFFEE</span>
        </a>
        <div class="nav-links" id="navLinks" aria-label="Navigation links">
          <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">Home</a>
          <a href="{{ route('about') }}" class="{{ request()->routeIs('about') ? 'active' : '' }}">About</a>
          <a href="{{ route('menu') }}" class="{{ request()->routeIs('menu') ? 'active' : '' }}">Menu</a>
          <a href="{{ route('contact') }}" class="{{ request()->routeIs('contact') ? 'active' : '' }}">Contact</a>
          <a href="{{ route('login') }}" class="btn btn-primary">Staff Login</a>
        </div>
        <button class="nav-toggle" id="navToggle" aria-label="menu" aria-expanded="false" aria-controls="navLinks">
          <span class="hamburger"></span>
          <span class="hamburger"></span>
          <span class="hamburger"></span>
        </button>
      </nav>
    </header>

    <main>
      @yield('content')
    </main>

    <footer class="site-footer">
      <div class="container footer-inner">
        <div class="footer-brand">
          <div class="brand" aria-label="DashCoffee footer brand">
            <span class="brand-text">Dash Coffee</span>
          </div>
        </div>
        <div class="footer-links">
          <a href="{{ route('home') }}">Home</a>
          <a href="{{ route('about') }}">About</a>
          <a href="{{ route('menu') }}">Menu</a>
          <a href="{{ route('contact') }}">Contact</a>
        </div>
        <div class="footer-meta">
          <p>© 2026 DashCoffee</p>
          <div class="legal-links">
  <a href="#privacy" data-dialog="privacyDialog">Privacy Policy</a>
  <a href="#terms" data-dialog="termsDialog">Terms</a>
</div>
        </div>
      </div>
    </footer>

    {{-- Footer pop-ups: Privacy Policy and Terms (plain sample text - edit to match how the shop really works) --}}
    <dialog class="legal-dialog" id="privacyDialog" aria-labelledby="privacyTitle">
      <form method="dialog" class="legal-close"><button aria-label="Close">&times;</button></form>
      <h2 id="privacyTitle">Privacy Policy</h2>
      <p class="legal-updated">Last updated: November 2026</p>
      <p>Dash Coffee respects your privacy. This page explains what information this website collects and how we use it.</p>

      <h3>What we collect</h3>
      <ul>
        <li>Messages you send through the Contact page (your name, email and message).</li>
        <li>For our staff only: account details used to log in to the staff system.</li>
        <li>In-store orders are recorded by our staff (items, total and payment method) for sales and inventory records. Customers do not need an account.</li>
      </ul>

      <h3>How we use it</h3>
      <p>We use your messages only to reply to you and improve our products and service. Order records are used for receipts, inventory and sales reports.</p>

      <h3>What we do not do</h3>
      <p>We do not sell your information or share it with advertisers. This website does not take online payments.</p>

      <h3>Questions</h3>
      <p>If you want a message of yours removed, contact us through the Contact page and we will take care of it.</p>
    </dialog>

    <dialog class="legal-dialog" id="termsDialog" aria-labelledby="termsTitle">
      <form method="dialog" class="legal-close"><button aria-label="Close">&times;</button></form>
      <h2 id="termsTitle">Terms of Use</h2>
      <p class="legal-updated">Last updated: November 2026</p>
      <p>By using this website you agree to the terms below.</p>

      <h3>Menu and prices</h3>
      <p>Products, prices and availability shown on this website may change without notice. Pictures are for illustration and the actual product may look slightly different.</p>

      <h3>Ordering</h3>
      <p>Orders are made in our store. The website is for information only and does not accept online orders or payments.</p>

      <h3>Messages you send us</h3>
      <p>Please keep messages respectful and truthful. We may ignore messages that are abusive or spam.</p>

      <h3>Our content</h3>
      <p>The Dash Coffee name, logo, photos and text belong to Dash Coffee. Please do not copy or reuse them without permission.</p>

      <h3>Staff area</h3>
      <p>The staff system is for authorized Dash Coffee staff only. Trying to access it without permission is not allowed.</p>

      <h3>Changes</h3>
      <p>We may update these terms from time to time. Continuing to use the website means you accept the updated terms.</p>
    </dialog>

    @stack('scripts')

    <script>
      const navToggle = document.getElementById('navToggle');
      const navLinks = document.getElementById('navLinks');
      
      function setNav(open) {
        navLinks.classList.toggle('open', open);
        navToggle.classList.toggle('open', open);
        navToggle.setAttribute('aria-expanded', open);
      }

      navToggle.addEventListener('click', () => setNav(!navLinks.classList.contains('open')));

      document.addEventListener('click', (event) => {
  if (event.target.closest('.site-footer')) return;
  
  if (!navLinks.contains(event.target) && !navToggle.contains(event.target)) setNav(false);
});

        document.addEventListener('keydown', (event) => {
          if (event.key === 'Escape') setNav(false);
        });
    </script>

    <script>
  const revealItems = document.querySelectorAll(
    '.section-heading, .product-card, .review-card, .section-cta, .about-copy, .about-image-wrap, .menu-card, .value-card, .info-card'
  );

  revealItems.forEach((el, i) => {
    el.classList.add('reveal');
    el.style.transitionDelay = (i % 4) * 0.1 + 's';
  });

  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.15 });

  revealItems.forEach((el) => observer.observe(el));
</script>

    <script>
      // Footer pop-ups (Privacy Policy / Terms)
      document.querySelectorAll('[data-dialog]').forEach((link) => {
        link.addEventListener('click', (event) => {
          event.preventDefault();
          document.getElementById(link.dataset.dialog).showModal();
        });
      });
      document.querySelectorAll('.legal-dialog').forEach((dialog) => {
        dialog.addEventListener('click', (event) => {
          if (event.target === dialog) dialog.close();   // click outside the box closes it
        });
      });
    </script>
  </body>
</html>