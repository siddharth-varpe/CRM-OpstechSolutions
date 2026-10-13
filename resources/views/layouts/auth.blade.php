<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login — Enterprises CRM</title>
    
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
                DEFAULT: '#0B132B',
                navy: '#0B132B',
                active: '#0284C7',
                hover: 'rgba(255, 255, 255, 0.07)',
                darker: '#070C1B',
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
                950: '#082F49',
              },
              brand: {
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
              'elevated': '0 4px 16px -2px rgba(15, 23, 42, 0.06), 0 2px 6px -1px rgba(15, 23, 42, 0.04)',
            }
          }
        }
      }
    </script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    
    <!-- jQuery & Lucide Icons -->
    <script src="{{ asset('js/jquery.min.js') }}"></script>
    <script src="{{ asset('js/lucide.min.js') }}"></script>
    <script src="{{ asset('js/crm-app.js') }}"></script>
</head>
<body class="min-h-screen min-h-[100dvh] w-full bg-[#F4F6F8] flex flex-col justify-between p-3 sm:p-4 lg:p-6 select-none overflow-y-auto antialiased">
    @yield('content')
    
    <!-- Footer -->
    <footer class="pt-4 pb-2 text-center text-xs text-slate-500 font-medium">
        <span>&copy; {{ date('Y') }} Enterprises CRM. All Rights Reserved. Commercial Water Purification SaaS.</span>
    </footer>

    <script>
      $(document).ready(function() {
        if (typeof lucide !== 'undefined') lucide.createIcons();
      });
    </script>
</body>
</html>
