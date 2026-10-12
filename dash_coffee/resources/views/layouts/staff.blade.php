<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="robots" content="noindex, nofollow" />
  <title>@yield('title', 'Staff') | DashCoffee</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:wght@400;500;600;700&family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="{{ asset('css/styles.css') }}" />
  <link rel="stylesheet" href="{{ asset('css/staff.css') }}" />
  <link rel="stylesheet" href="{{ asset('css/pos.css') }}" />
</head>

<body class="staff-body">
  @if (session('just_logged_in'))
    <div class="welcome-overlay">
      <div class="welcome-box">
        <img src="{{ asset('images/dashcoffee-logo.jpg') }}" alt="DashCoffee logo" />
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
        <img src="{{ asset('images/dashcoffee-logo.jpg') }}" alt="DashCoffee logo" />
        <span>DASH COFFEE</span>
      </a>
      <div class="pos-user">
        <span class="pos-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
        <span>{{ auth()->user()->name }}</span>
      </div>
      <nav class="pos-nav" aria-label="Staff navigation">
        <a href="{{ route('staff.dashboard') }}"
          class="{{ request()->routeIs('staff.dashboard') ? 'active' : '' }}">Dashboard</a>
        <a href="{{ route('staff.orders') }}" class="{{ request()->routeIs('staff.orders') ? 'active' : '' }}">Order
          Entry</a>
        <a href="{{ route('staff.history') }}" class="{{ request()->routeIs('staff.history','staff.receipt') ? 'active' : '' }}">Order History</a>
        @if ($owner)
          <a href="{{ route('staff.menu') }}" class="{{ request()->routeIs('staff.menu') ? 'active' : '' }}">Manage
            Menu</a>
        @endif
          <a href="{{ route('staff.inventory') }}"
          class="{{ request()->routeIs('staff.inventory', 'staff.inv-reports') ? 'active' : '' }}">Inventory</a>
        @if ($owner)
          <a href="{{ route('staff.sales') }}" class="{{ request()->routeIs('staff.sales') ? 'active' : '' }}">Sales
            Report</a>
          <a href="{{ route('staff.accounts') }}"
            class="{{ request()->routeIs('staff.accounts') ? 'active' : '' }}">Staff</a>
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
</body>

</html>