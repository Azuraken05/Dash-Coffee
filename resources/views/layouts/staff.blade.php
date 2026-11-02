<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="robots" content="noindex, nofollow" />
  <title>@yield('title', 'Staff') | DashCoffee</title>
      <link rel="icon" type="image/png" href="{{ asset('assets/dashcoffeelogo.png') }}" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:wght@400;500;600;700&family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="{{ asset('css/styles.css') }}" />
  <link rel="stylesheet" href="{{ asset('css/staff.css') }}" />
  <link rel="stylesheet" href="{{ asset('css/pos.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/mobile.css') }}" />
  <link rel="stylesheet" href="{{ asset('css/mobile-card.css') }}" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
  <style>
    /* Hamburger button: stays put on screen while the page scrolls */
    .pos-toggle { position:fixed !important; top:16px !important; bottom:auto !important;
                  margin:0 !important; transform:none !important; z-index:1001 !important; }
    .pos-toggle:not(.open):not([aria-expanded="true"]) { left:16px !important; }
  </style>

</style>
</head>

<body class="staff-body">
  @if (session('just_logged_in'))
    <div class="welcome-overlay">
      <div class="welcome-box">
        <img src="{{ asset('assets/dashcoffee-logo.jpg') }}" alt="DashCoffee logo" />
        <h2>Welcome, {{ auth()->user()->name }}</h2>
        <p>Opening your dashboard...</p>
      </div>
    </div>
  @endif

  @php $owner = auth()->user()->role === 'owner'; @endphp
  <div class="pos">
    <button class="pos-toggle" id="posToggle" aria-label="Toggle menu" aria-expanded="false">
      <span></span><span></span><span></span>
    </button>
    <div class="pos-backdrop" id="posBackdrop"></div>
    <aside class="pos-side">
      <a href="{{ route('staff.dashboard') }}" class="pos-brand">
        <img src="{{ asset('assets/dashcoffee-logowhite.png') }}" alt="DashCoffee logo" />
        <div class="pos-brand-text">
          <span>DASH COFFEE</span>
        </div>
      </a>
      <div class="pos-user">
        <span class="pos-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
        <span>{{ auth()->user()->name }}</span>
      </div>
      <nav class="pos-nav" aria-label="Staff navigation">
        <div class="pos-group">
          <button type="button" class="pos-group-label">Main <i class="bi bi-chevron-down"></i></button>
          <div class="pos-group-items">
            <a href="{{ route('staff.dashboard') }}"
              class="{{ request()->routeIs('staff.dashboard') ? 'active' : '' }}">
              <img src="{{ asset('assets/layout-dashboard.svg') }}" alt="" class="pos-icon"> Dashboard</a>
          </div>
        </div>

        <div class="pos-group">
          <button type="button" class="pos-group-label">Orders <i class="bi bi-chevron-down"></i></button>
          <div class="pos-group-items">
            <a href="{{ route('staff.orders') }}" class="{{ request()->routeIs('staff.orders') ? 'active' : '' }}">
              <img src="{{ asset('assets/calculator.svg') }}" alt="" class="pos-icon"> Order Entry</a>
            <a href="{{ route('staff.history') }}" class="{{ request()->routeIs('staff.history','staff.receipt') ? 'active' : '' }}">
              <img src="{{ asset('assets/clipboard-clock.svg') }}" alt="" class="pos-icon"> Order History</a>
          </div>
        </div>

        <div class="pos-group">
          <button type="button" class="pos-group-label">Menu &amp; Stock <i class="bi bi-chevron-down"></i></button>
          <div class="pos-group-items">
            @if ($owner)
              <a href="{{ route('staff.menu') }}" class="{{ request()->routeIs('staff.menu') ? 'active' : '' }}">
                <img src="{{ asset('assets/file-pen.svg') }}" alt="" class="pos-icon"> Manage Menu</a>
            @endif
            <a href="{{ route('staff.inventory') }}"
              class="{{ request()->routeIs('staff.inventory', 'staff.inv-reports') ? 'active' : '' }}">
              <img src="{{ asset('assets/package.svg') }}" alt="" class="pos-icon"> Inventory</a>
          </div>
        </div>

        @if ($owner)
          <div class="pos-group">
            <button type="button" class="pos-group-label">Analytics <i class="bi bi-chevron-down"></i></button>
            <div class="pos-group-items">
              <a href="{{ route('staff.sales') }}" class="{{ request()->routeIs('staff.sales') ? 'active' : '' }}">
                <img src="{{ asset('assets/chart-column-big.svg') }}" alt="" class="pos-icon"> Sales Report</a>
            </div>
          </div>

          <div class="pos-group">
            <button type="button" class="pos-group-label">Administration <i class="bi bi-chevron-down"></i></button>
            <div class="pos-group-items">
              <a href="{{ route('staff.accounts') }}"
                class="{{ request()->routeIs('staff.accounts') ? 'active' : '' }}">
                <img src="{{ asset('assets/folder-users.svg') }}" alt="" class="pos-icon"> Staff</a>
            </div>
          </div>
        @endif
      </nav>
      <form method="POST" action="{{ route('logout') }}" class="pos-logout">
        @csrf
        <button type="submit">Log Out</button>
      </form>
    </aside>
    <main class="pos-main">@yield('content')</main>
  </div>
  <script src="{{ asset('js/pos.js') }}"></script>
  <script>
    (function () {
      var side = document.querySelector('.pos-side');
      var toggle = document.getElementById('posToggle');
      var backdrop = document.getElementById('posBackdrop');
      if (!side || !toggle || !backdrop) return;

      function isOpen() {
        return side.classList.contains('open');
      }

      function setOpen(open) {
        side.classList.toggle('open', open);
        toggle.classList.toggle('open', open);
        backdrop.classList.toggle('open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      }

      toggle.addEventListener('click', function (e) {
        e.stopPropagation();
        if (!e.isTrusted) return;
        setOpen(!isOpen());
      });

      backdrop.addEventListener('click', function () {
        setOpen(false);
      });

      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && isOpen()) setOpen(false);
      });

      side.addEventListener('click', function (e) {
        if (e.target.closest('a[href]')) setOpen(false);
      });

      setOpen(false);
      window.addEventListener('pageshow', function () {
        setOpen(false);
      });
    })();
  </script>
</body>
</html>