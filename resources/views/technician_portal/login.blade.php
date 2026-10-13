<!DOCTYPE html>
<html lang="en" class="h-full bg-[#F4F6F8]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Technician Login — Enterprises CRM</title>
    
    <!-- Official Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS with Authoritative CRM Theme -->
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
              },
              primary: {
                50: '#F0F9FF',
                500: '#0EA5E9',
                600: '#0284C7',
                700: '#0369A1',
                800: '#075985',
              }
            },
            fontFamily: {
              sans: ['Inter', 'system-ui', 'sans-serif'],
              display: ['Plus Jakarta Sans', 'Inter', 'sans-serif'],
              mono: ['JetBrains Mono', 'monospace'],
            },
            boxShadow: {
              'elevated': '0 4px 16px -2px rgba(15, 23, 42, 0.06), 0 2px 6px -1px rgba(15, 23, 42, 0.04)',
            }
          }
        }
      }
    </script>
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    
    <script src="{{ asset('js/jquery.min.js') }}"></script>
    <script src="{{ asset('js/lucide.min.js') }}"></script>
    <script src="{{ asset('js/crm-app.js') }}"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-display { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
    </style>
</head>
<body class="min-h-screen bg-workspace flex flex-col justify-center py-8 px-4 sm:px-6 lg:px-8 text-slate-800 antialiased select-none">
    <div id="toast-container" class="fixed top-5 right-5 z-50 flex flex-col gap-2 max-w-sm w-full pointer-events-none"></div>

    <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
        <!-- Logo and Branding Header in Admin Theme -->
        <div class="inline-flex items-center justify-center gap-3 mb-3">
            <div class="w-12 h-12 rounded-2xl bg-white border border-slate-200/90 shadow-sm flex items-center justify-center p-1.5">
                <img src="{{ asset('assets/crm-logo.png') }}" alt="Enterprises CRM Logo" class="w-full h-full object-contain">
            </div>
            <div class="text-left">
                <span class="block text-sm font-display font-extrabold tracking-wider text-slate-900 uppercase leading-tight">ENTERPRISES</span>
                <span class="block text-[10px] font-bold text-sky-600 tracking-widest uppercase font-mono">CRM</span>
            </div>
        </div>

        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-sky-50 border border-sky-200 text-sky-700 text-xs font-semibold mb-2">
            <i data-lucide="wrench" class="w-3.5 h-3.5"></i>
            <span>Field Technician Portal</span>
        </div>
        <h1 class="text-xl sm:text-2xl font-display font-extrabold text-slate-900 tracking-tight">Technician PIN Access</h1>
        <p class="text-xs text-slate-500 mt-1">Enter your registered mobile number and 4-digit security PIN</p>
    </div>

    <div class="mt-6 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white border border-slate-200/90 rounded-2xl shadow-elevated p-6 sm:p-8 space-y-6">
            
            <form id="formTechPinLogin" action="{{ route('tech_portal.authenticate') }}" method="POST" class="space-y-4">
                @csrf

                <!-- Technician Selection / Mobile -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Select Technician</label>
                        <button type="button" id="btnToggleManualPhone" class="text-xs font-semibold text-sky-600 hover:text-sky-700 underline underline-offset-2">Custom number</button>
                    </div>

                    <div id="selectTechWrapper">
                        <select name="phone" id="selectTechnician" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-3 text-sm text-slate-800 font-medium focus:outline-none focus:bg-white focus:border-sky-500 focus:ring-1 focus:ring-sky-500 transition">
                            @foreach($technicians as $t)
                                <option value="{{ $t->phone }}">
                                    {{ $t->full_name }} ({{ $t->phone }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div id="manualPhoneWrapper" class="hidden">
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-sm font-semibold text-slate-400 pointer-events-none">+91</span>
                            <input type="tel" id="inputCustomPhone" maxlength="10" placeholder="9822012345" class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-12 pr-3.5 py-3 text-sm text-slate-800 font-mono placeholder:text-slate-400 focus:outline-none focus:bg-white focus:border-sky-500 focus:ring-1 focus:ring-sky-500">
                        </div>
                    </div>
                </div>

                <!-- 4-Digit Security PIN -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Security PIN</label>
                        <span class="text-[11px] text-slate-400 font-mono">4-digit code</span>
                    </div>
                    <input type="password" 
                           name="pin" 
                           id="inputPin" 
                           value="1234" 
                           maxlength="6" 
                           required 
                           placeholder="••••" 
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-center text-xl font-mono font-bold text-slate-900 tracking-widest placeholder:text-slate-400 focus:outline-none focus:bg-white focus:border-sky-500 focus:ring-1 focus:ring-sky-500 transition">
                </div>

                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80 text-xs text-slate-500 flex items-start gap-2">
                    <i data-lucide="info" class="w-4 h-4 text-sky-600 shrink-0 mt-0.5"></i>
                    <span>Default initial PIN is <strong>1234</strong>. Contact administrator if you need a PIN reset.</span>
                </div>

                <button type="submit" id="btnSubmitPin" class="w-full bg-sky-600 hover:bg-sky-700 active:bg-sky-800 text-white font-bold py-3.5 px-4 rounded-xl text-sm transition flex items-center justify-center gap-2 shadow-sm cursor-pointer">
                    <i data-lucide="log-in" class="w-4 h-4"></i>
                    <span id="btnSubmitPinText">Sign In to Technician Portal</span>
                </button>
            </form>

            <div class="pt-4 border-t border-slate-100 text-center">
                <a href="{{ route('dashboard') }}" class="text-xs font-medium text-slate-500 hover:text-slate-800 transition inline-flex items-center gap-1.5">
                    <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                    <span>Return to Main Desktop CRM</span>
                </a>
            </div>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        lucide.createIcons();

        // Toggle custom phone number entry
        $('#btnToggleManualPhone').on('click', function() {
            const isManualHidden = $('#manualPhoneWrapper').hasClass('hidden');
            if (isManualHidden) {
                $('#selectTechWrapper').addClass('hidden');
                $('#selectTechnician').prop('disabled', true);
                $('#manualPhoneWrapper').removeClass('hidden');
                $('#inputCustomPhone').prop('disabled', false).attr('name', 'phone').focus();
                $(this).text('Select from list');
            } else {
                $('#manualPhoneWrapper').addClass('hidden');
                $('#inputCustomPhone').prop('disabled', true).removeAttr('name').val('');
                $('#selectTechWrapper').removeClass('hidden');
                $('#selectTechnician').prop('disabled', false);
                $(this).text('Custom number');
            }
        });

        // AJAX Form Submission
        CRM.handleAjaxForm('#formTechPinLogin', function(res) {
            CRM.toast(res.message || 'Authenticated successfully!', 'success');
            setTimeout(() => window.location.href = res.redirect || "{{ route('tech_portal.dashboard') }}", 400);
        });
    });
    </script>
</body>
</html>
