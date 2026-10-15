<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta
      name="description"
      content="DashCoffee is a modern coffee and comfort food destination serving premium drinks, savory silog meals, and hearty snacks in a cozy setting."
    />
    <title>@yield('title', 'DashCoffee | Your Daily Coffee Moment')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:wght@400;500;600;700&family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}" />
  </head>
  <body>
    <header class="site-header">
      <nav class="navbar container" aria-label="Main navigation">
      <a href="{{ route('home') }}" 
        aria-label="DashCoffee brand" 
        class="brand {{ request()->routeIs('home') ? 'active' : '' }}" 
        style="text-decoration: none; color: inherit;">
          <img src="{{ asset('images/dashcoffee-logo.jpg') }}" alt="DashCoffee logo" class="brand-logo" />
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
            <a href="#">Privacy Policy</a>
            <a href="#">Terms</a>
          </div>
        </div>
      </div>
    </footer>

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
  </body>
</html>
