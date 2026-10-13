<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — Enterprises CRM</title>
    
    <!-- Official Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS (Play CDN with Authoritative CRM Theme) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              workspace: '#F4F6F8',
              surface: '#FFFFFF',
              sidebar: {
                DEFAULT: '#00152B',
                navy: '#00152B',
                active: '#0284C7',
                hover: 'rgba(255, 255, 255, 0.08)',
                darker: '#000F1F',
              },
              primary: {
                50: '#F0F9FF',
                100: '#E0F2FE',
                200: '#BAE6FD',
                300: '#7DD3FC',
                400: '#38BDF8',
                500: '#0EA5E9',
                600: '#0284C7',
                700: '#0369A1',
                800: '#075985',
                900: '#0C4A6E',
                950: '#082F49',
              },
              brand: {
                50: '#F0FDFA',
                100: '#CCFBF1',
                500: '#14B8A6',
                600: '#0D9488',
                700: '#0F766E',
              }
            },
            fontFamily: {
              sans: ['Inter', 'system-ui', 'sans-serif'],
              display: ['Plus Jakarta Sans', 'Inter', 'sans-serif'],
            },
            boxShadow: {
              'card': '0 1px 2px rgba(15, 23, 42, 0.04)',
              'elevated': '0 4px 16px -2px rgba(15, 23, 42, 0.06), 0 2px 6px -1px rgba(15, 23, 42, 0.04)',
              'dropdown': '0 10px 25px -5px rgba(15, 23, 42, 0.10), 0 8px 10px -6px rgba(15, 23, 42, 0.06)',
            }
          }
        }
      }
    </script>
    
    <!-- Leaflet Maps CSS -->
    <link rel="stylesheet" href="{{ asset('css/leaflet.css') }}">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    
    <!-- jQuery, Lucide Icons, Leaflet Maps, Universal CRM Helpers -->
    <script src="{{ asset('js/jquery.min.js') }}"></script>
    <script src="{{ asset('js/lucide.min.js') }}"></script>
    <script src="{{ asset('js/leaflet.js') }}"></script>
    <script src="{{ asset('js/crm-app.js') }}"></script>
    @stack('styles')
</head>
<body class="min-h-screen bg-workspace flex flex-col font-sans relative antialiased select-none print:bg-white print:min-h-0 print:p-0">

    <!-- Global Authoritative Sidebar -->
    <aside id="global-sidebar" class="fixed top-0 bottom-0 left-0 h-screen z-40 bg-[#00152B] text-white flex flex-col justify-between py-3.5 select-none print:hidden border-r border-slate-800/80 rounded-r-2xl transition-all duration-200 ease-out shadow-2xl w-[68px] hover:w-[136px] md:hover:w-[136px] group">
        <!-- Top Branding Section -->
        <div class="flex flex-col items-center px-2 shrink-0">
            <div class="w-11 h-11 rounded-xl bg-white/10 backdrop-blur-xs flex items-center justify-center p-1 shadow-md mb-1.5 shrink-0 border border-white/20">
                <img src="{{ asset('assets/crm-logo.png') }}" alt="Enterprises CRM Logo" class="w-full h-full object-contain drop-shadow">
            </div>
            <div class="text-center px-1 overflow-hidden w-full hidden group-hover:block transition-all">
                <span class="block text-[10px] font-display font-extrabold tracking-wider text-white uppercase truncate">ENTERPRISES</span>
                <span class="block text-[8px] font-bold text-sky-400 tracking-widest uppercase font-mono">CRM</span>
            </div>
        </div>

        <!-- Navigation Items Container -->
        <nav class="flex-1 overflow-y-auto overflow-x-hidden my-3 px-2 space-y-1.5 scrollbar-none flex flex-col items-center group-hover:items-stretch">
            @php
                $currentRoute = Route::currentRouteName() ?? '';
                $navItems = [
                    ['key' => 'dashboard', 'route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'layout-dashboard', 'match' => 'dashboard*'],
                    ['key' => 'dues', 'route' => 'dues.index', 'label' => 'Dues', 'icon' => 'calendar-clock', 'match' => 'dues*'],
                    ['key' => 'customers', 'route' => 'customers.index', 'label' => 'Customers', 'icon' => 'users', 'match' => 'customers*'],
                    ['key' => 'sales', 'route' => 'sales.index', 'label' => 'Sales', 'icon' => 'shopping-cart', 'match' => 'sales*'],
                    ['key' => 'rent', 'route' => 'rentals.index', 'label' => 'Rent', 'icon' => 'repeat', 'match' => 'rentals*'],
                    ['key' => 'invoices', 'route' => 'invoices.index', 'label' => 'Invoices', 'icon' => 'file-text', 'match' => 'invoices*'],
                    ['key' => 'services', 'route' => 'services.index', 'label' => 'Services', 'icon' => 'wrench', 'match' => 'services*'],
                    ['key' => 'inventory', 'route' => 'inventory.index', 'label' => 'Inventory', 'icon' => 'package-search', 'match' => 'inventory*'],
                    ['key' => 'technicians', 'route' => 'technicians.index', 'label' => 'Technicians', 'icon' => 'user-cog', 'match' => 'technicians'],
                    ['key' => 'technicians-map', 'route' => 'technicians.map', 'label' => 'Live Map', 'icon' => 'navigation', 'match' => 'technicians/map*'],
                    ['key' => 'payments', 'route' => 'payments.index', 'label' => 'Payments', 'icon' => 'wallet-cards', 'match' => 'payments*'],
                    ['key' => 'reports', 'route' => 'reports.index', 'label' => 'Reports', 'icon' => 'pie-chart', 'match' => 'reports*'],
                    ['key' => 'settings', 'route' => 'settings.index', 'label' => 'Settings', 'icon' => 'settings', 'match' => 'settings*'],
                ];
            @endphp

            @foreach($navItems as $item)
                @php
                    $isActive = request()->is($item['match']);
                @endphp
                <a href="{{ Route::has($item['route']) ? route($item['route']) : url('/'.$item['key']) }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-all duration-150 {{ $isActive ? 'bg-primary-600 text-white shadow-md' : 'text-slate-300 hover:text-white hover:bg-white/10' }}"
                   title="{{ $item['label'] }}">
                    <i data-lucide="{{ $item['icon'] }}" class="w-5 h-5 shrink-0"></i>
                    <span class="truncate hidden group-hover:inline">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>

        <!-- Bottom Actions -->
        <div class="pt-2 border-t border-slate-800/80 px-2 space-y-1 shrink-0">
            <a href="{{ route('technician.login') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold text-slate-300 hover:text-white hover:bg-white/10 transition-colors"
               title="Technician Portal">
                <i data-lucide="smartphone" class="w-5 h-5 shrink-0 text-sky-400"></i>
                <span class="truncate hidden group-hover:inline">Tech Portal</span>
            </a>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit"
                        class="w-full flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold text-rose-400 hover:text-rose-300 hover:bg-rose-500/10 transition-colors text-left"
                        title="Sign Out">
                    <i data-lucide="log-out" class="w-5 h-5 shrink-0"></i>
                    <span class="truncate hidden group-hover:inline">Logout</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Application Shell -->
    <div class="flex-1 flex flex-col pl-[68px] transition-all duration-200 ease-out print:pl-0">
        <!-- Persistent Topbar Header -->
        <header class="h-16 bg-white border-b border-slate-200/90 px-4 lg:px-6 flex items-center justify-between sticky top-0 z-20 shadow-2xs print:hidden">
            <div class="flex items-center gap-3">
                <button type="button" id="mobile-menu-toggle" class="md:hidden p-2 text-slate-600 hover:text-slate-900 rounded-lg hover:bg-slate-100 focus:outline-none">
                    <i data-lucide="menu" class="w-5 h-5"></i>
                </button>
                <div class="flex flex-col min-w-0">
                    <span class="font-display font-extrabold text-slate-900 text-xs sm:text-sm tracking-tight block truncate uppercase">
                        {{ \App\Models\SystemSetting::get('company_name', 'ENTERPRISES CRM') }}
                    </span>
                    <span class="text-[9px] sm:text-[10px] text-slate-500 font-semibold uppercase tracking-wider hidden xs:block">
                        Commercial Service &amp; Sales Management
                    </span>
                </div>
            </div>

            <!-- Top Header Right Elements -->
            <div class="flex items-center gap-3">
                <!-- Live GPS Tracking Map Shortcut -->
                <a href="{{ route('technicians.map') }}" class="hidden sm:inline-flex items-center gap-2 px-3 py-1.5 text-xs font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-lg border border-emerald-200/80 transition-all shadow-2xs" title="View Real-Time Field Technician GPS Tracking">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <i data-lucide="navigation" class="w-3.5 h-3.5 text-emerald-600"></i>
                    <span>Live GPS Map</span>
                </a>

                <!-- Search Button (Command Palette Ctrl+K) -->
                <button type="button" id="search-trigger-btn" class="flex items-center gap-2 px-3 py-1.5 text-xs font-medium text-slate-500 bg-slate-100 hover:bg-slate-200 rounded-lg border border-slate-200/80 transition-colors">
                    <i data-lucide="search" class="w-4 h-4 text-slate-400"></i>
                    <span class="hidden sm:inline">Search CRM...</span>
                    <kbd class="hidden sm:inline px-1.5 py-0.5 text-[10px] font-mono bg-white rounded border border-slate-300 shadow-2xs">Ctrl K</kbd>
                </button>

                <!-- Notification Bell -->
                <div class="relative" id="notifications-wrapper">
                    <button type="button" id="notifications-toggle" class="relative p-2 text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-lg focus:outline-none transition-colors">
                        <i data-lucide="bell" class="w-5 h-5"></i>
                        @php
                            $unreadCount = \App\Models\Notification::where('is_read', false)->count();
                        @endphp
                        @if($unreadCount > 0)
                            <span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-rose-500 ring-2 ring-white animate-pulse"></span>
                        @endif
                    </button>
                    <!-- Notification Dropdown Panel -->
                    <div id="notifications-dropdown" class="hidden absolute right-0 mt-2 w-80 sm:w-96 bg-white rounded-xl shadow-dropdown border border-slate-200 z-50 overflow-hidden">
                        <div class="p-3 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                            <span class="font-bold text-xs text-slate-800 uppercase tracking-wider">Notifications ({{ $unreadCount }})</span>
                            <span class="text-xs text-primary-600 hover:underline cursor-pointer" id="mark-all-read-btn">Mark all read</span>
                        </div>
                        <div class="max-h-80 overflow-y-auto divide-y divide-slate-100" id="notification-list">
                            @forelse(\App\Models\Notification::latest()->take(5)->get() as $n)
                                <div class="p-3 hover:bg-slate-50 transition-colors flex items-start gap-3">
                                    <div class="p-1.5 rounded-lg bg-sky-50 text-sky-600 shrink-0 mt-0.5">
                                        <i data-lucide="bell-ring" class="w-4 h-4"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-xs font-bold text-slate-900 truncate">{{ $n->title }}</h4>
                                        <p class="text-[11px] text-slate-600 mt-0.5 leading-snug">{{ $n->message }}</p>
                                        <span class="text-[9px] text-slate-400 mt-1 block">{{ $n->created_at->diffForHumans() }}</span>
                                    </div>
                                </div>
                            @empty
                                <div class="p-6 text-center text-xs text-slate-400">
                                    <i data-lucide="check" class="w-6 h-6 mx-auto mb-1 text-slate-300"></i>
                                    <span>All caught up! No new notifications.</span>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- User Profile Badge -->
                @auth
                <div class="flex items-center gap-2 pl-2 border-l border-slate-200">
                    <div class="w-8 h-8 rounded-full bg-primary-600 text-white flex items-center justify-center font-bold text-xs shadow-2xs">
                        {{ strtoupper(substr(auth()->user()->display_name, 0, 1)) }}
                    </div>
                    <div class="hidden sm:flex flex-col">
                        <span class="text-xs font-bold text-slate-800 leading-tight">{{ auth()->user()->display_name }}</span>
                        <span class="text-[10px] text-slate-500 font-semibold">{{ auth()->user()->role }}</span>
                    </div>
                </div>
                @endauth
            </div>
        </header>

        <!-- Main Viewport Body -->
        <main class="flex-1 p-4 lg:p-6 overflow-y-auto">
            @yield('content')
        </main>
    </div>

    <!-- Global Search Modal (Command Palette) -->
    <div id="search-modal" class="hidden fixed inset-0 z-50 modal-backdrop items-start justify-center pt-20 p-4">
        <div class="bg-white rounded-2xl shadow-modal border border-slate-200 max-w-2xl w-full overflow-hidden fade-in">
            <div class="p-3.5 border-b border-slate-100 flex items-center gap-3">
                <i data-lucide="search" class="w-5 h-5 text-slate-400 shrink-0"></i>
                <input type="text" id="global-search-input" placeholder="Type customer name, phone, serial number, invoice or service number..." class="w-full text-sm font-medium text-slate-900 placeholder-slate-400 focus:outline-none bg-transparent">
                <button type="button" class="text-xs text-slate-400 hover:text-slate-600" onclick="closeModal('search-modal')">
                    <kbd class="px-1.5 py-0.5 bg-slate-100 rounded border border-slate-200">ESC</kbd>
                </button>
            </div>
            <div id="search-results-box" class="max-h-96 overflow-y-auto p-3 text-xs text-slate-500 divide-y divide-slate-100">
                <div class="p-4 text-center text-slate-400">Search customers, machines, serial numbers, invoices &amp; job cards</div>
            </div>
        </div>
    </div>

    <!-- Universal Toasts Container -->
    <div id="toast-container" class="fixed bottom-5 right-5 z-50 flex flex-col gap-2 max-w-sm w-full pointer-events-none"></div>

    <script>
    $(document).ready(function() {
        if (typeof lucide !== 'undefined') lucide.createIcons();

        // Notification dropdown toggle
        $('#notifications-toggle').on('click', function(e) {
            e.stopPropagation();
            $('#notifications-dropdown').toggleClass('hidden');
        });

        $(document).on('click', function(e) {
            if (!$(e.target).closest('#notifications-wrapper').length) {
                $('#notifications-dropdown').addClass('hidden');
            }
        });

        // Command Palette Keyboard Trigger (Ctrl+K or Cmd+K)
        $(document).on('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                openModal('search-modal');
                $('#global-search-input').focus();
            }
            if (e.key === 'Escape') {
                closeModal('search-modal');
            }
        });

        $('#search-trigger-btn').on('click', function() {
            openModal('search-modal');
            $('#global-search-input').focus();
        });

        // Global Search Debounced Input
        let searchTimer = null;
        $('#global-search-input').on('input', function() {
            clearTimeout(searchTimer);
            const query = $(this).val().trim();
            if (query.length < 2) {
                $('#search-results-box').html('<div class="p-4 text-center text-slate-400">Type at least 2 characters...</div>');
                return;
            }

            searchTimer = setTimeout(function() {
                $.get("{{ route('search.global') }}", { q: query }, function(res) {
                    let html = '';
                    if (res && res.results && res.results.length > 0) {
                        res.results.forEach(function(item) {
                            html += `
                                <a href="${item.url}" class="p-3 hover:bg-slate-50 block rounded-lg transition-colors">
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold text-slate-900">${item.title}</span>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-100 text-slate-600">${item.type}</span>
                                    </div>
                                    <p class="text-slate-500 mt-0.5 text-xs">${item.subtitle || ''}</p>
                                </a>
                            `;
                        });
                    } else {
                        html = '<div class="p-6 text-center text-slate-400">No matching records found for "' + query + '"</div>';
                    }
                    $('#search-results-box').html(html);
                });
            }, 250);
        });
    });
    </script>
    @stack('scripts')
</body>
</html>
