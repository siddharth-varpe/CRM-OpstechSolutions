@extends('layouts.auth')

@section('content')
<div class="w-full max-w-[1360px] mx-auto my-auto bg-white rounded-2xl shadow-elevated border border-slate-200/90 overflow-hidden flex flex-col lg:flex-row lg:max-h-[90vh]">
    <!-- Left Visual Identity Panel (54% Width) -->
    <div class="relative w-full lg:w-[54%] bg-[#0B132B] text-white flex flex-col justify-between p-4 sm:p-7 lg:p-8 overflow-hidden select-none">
        <!-- High-Fidelity Background Visual Scene -->
        <div class="absolute inset-0 z-0">
            <img src="{{ asset('assets/ro-purifier-bg.jpg') }}" alt="Enterprises CRM Management System" class="w-full h-full object-cover object-center">
            <!-- Hydro-Dark Gradient Overlays for High-Contrast Clarity -->
            <div class="absolute inset-0 bg-gradient-to-r from-[#0B132B]/95 via-[#0F294A]/80 to-[#0284C7]/40 pointer-events-none"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-[#070C1B]/95 via-transparent to-black/40 pointer-events-none"></div>
        </div>

        <!-- Top Left Branding Header -->
        <div class="relative z-10 flex items-center gap-3">
            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-white/10 backdrop-blur-xs border border-white/20 shadow-md flex items-center justify-center shrink-0 p-1">
                <img src="{{ asset('assets/crm-logo.png') }}" alt="Enterprises CRM Logo" class="w-full h-full object-contain select-none drop-shadow">
            </div>
            <div>
                <span class="block text-xs sm:text-sm lg:text-base font-display font-extrabold tracking-wider text-white uppercase leading-tight">
                    ENTERPRISES
                </span>
                <div class="flex items-center gap-2 mt-0.5">
                    <div class="w-4 sm:w-5 h-px bg-sky-400/60"></div>
                    <span class="text-[9px] sm:text-[10px] font-bold text-sky-400 tracking-widest uppercase font-mono">CRM</span>
                    <div class="w-4 sm:w-5 h-px bg-sky-400/60"></div>
                </div>
            </div>
        </div>

        <!-- Center Left Marketing Message -->
        <div class="relative z-10 max-w-sm my-auto py-3 sm:py-6">
            <span class="block text-xs sm:text-sm font-semibold text-sky-300">Welcome to</span>
            <h1 class="text-2xl sm:text-3xl lg:text-4xl font-display font-extrabold text-white tracking-tight mt-1 leading-tight drop-shadow-sm">
                Enterprises CRM
            </h1>
            <p class="text-xs sm:text-sm text-slate-200 font-normal mt-2.5 leading-relaxed drop-shadow-xs">
                A smart CRM to manage your business customers, sales, invoices, services, payments and more — all in one place.
            </p>
        </div>

        <!-- Bottom Feature Bar (4 Horizontally Arranged Items with Vertical Separators) -->
        <div class="relative z-10 pt-3 border-t border-white/15 grid grid-cols-2 sm:grid-cols-4 gap-3 items-start backdrop-blur-xs">
            <div class="flex flex-col items-center sm:items-start text-center sm:text-left">
                <div class="mb-1 p-1 rounded-lg bg-white/10 border border-white/15 text-white w-fit shadow-2xs">
                    <i data-lucide="users" class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-sky-300"></i>
                </div>
                <h2 class="text-[11px] sm:text-xs font-bold text-white leading-tight">Manage Customers</h2>
                <p class="text-[9px] sm:text-[10px] text-slate-300 mt-0.5 leading-snug">All customer info in one place</p>
            </div>

            <div class="flex flex-col items-center sm:items-start text-center sm:text-left sm:border-l sm:border-white/15 sm:pl-2.5">
                <div class="mb-1 p-1 rounded-lg bg-white/10 border border-white/15 text-white w-fit shadow-2xs">
                    <i data-lucide="shopping-cart" class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-sky-300"></i>
                </div>
                <h2 class="text-[11px] sm:text-xs font-bold text-white leading-tight">Track Sales</h2>
                <p class="text-[9px] sm:text-[10px] text-slate-300 mt-0.5 leading-snug">Create &amp; manage sales easily</p>
            </div>

            <div class="flex flex-col items-center sm:items-start text-center sm:text-left sm:border-l sm:border-white/15 sm:pl-2.5">
                <div class="mb-1 p-1 rounded-lg bg-white/10 border border-white/15 text-white w-fit shadow-2xs">
                    <i data-lucide="file-text" class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-sky-300"></i>
                </div>
                <h2 class="text-[11px] sm:text-xs font-bold text-white leading-tight">Invoices</h2>
                <p class="text-[9px] sm:text-[10px] text-slate-300 mt-0.5 leading-snug">GST invoices in seconds</p>
            </div>

            <div class="flex flex-col items-center sm:items-start text-center sm:text-left sm:border-l sm:border-white/15 sm:pl-2.5">
                <div class="mb-1 p-1 rounded-lg bg-white/10 border border-white/15 text-white w-fit shadow-2xs">
                    <i data-lucide="wrench" class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-sky-300"></i>
                </div>
                <h2 class="text-[11px] sm:text-xs font-bold text-white leading-tight">Services</h2>
                <p class="text-[9px] sm:text-[10px] text-slate-300 mt-0.5 leading-snug">Track service &amp; job cards</p>
            </div>
        </div>
    </div>

    <!-- Right Authentication Panel (46% Width) -->
    <div class="w-full lg:w-[46%] p-5 sm:p-8 lg:p-10 flex flex-col justify-center bg-white">
        <div class="max-w-md w-full mx-auto">
            <div class="mb-6">
                <h2 class="text-2xl font-display font-extrabold text-slate-900 tracking-tight">Sign In to Account</h2>
                <p class="text-sm text-slate-500 mt-1">Enter your credentials and security CAPTCHA to access the workspace.</p>
            </div>

            <!-- Error Banner -->
            <div id="login-error-banner" class="hidden mb-4 p-3 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-sm flex items-start gap-2">
                <i data-lucide="alert-circle" class="w-5 h-5 shrink-0 text-rose-600 mt-0.5"></i>
                <div id="login-error-text" class="flex-1 font-medium"></div>
            </div>

            @if(session('error'))
            <div class="mb-4 p-3 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-sm flex items-start gap-2">
                <i data-lucide="alert-circle" class="w-5 h-5 shrink-0 text-rose-600 mt-0.5"></i>
                <div class="flex-1 font-medium">{{ session('error') }}</div>
            </div>
            @endif

            @if(session('success'))
            <div class="mb-4 p-3 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm flex items-start gap-2">
                <i data-lucide="check-circle" class="w-5 h-5 shrink-0 text-emerald-600 mt-0.5"></i>
                <div class="flex-1 font-medium">{{ session('success') }}</div>
            </div>
            @endif

            <form id="login-form" action="{{ route('login.post') }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="challengeId" id="challengeId" value="{{ $challengeId }}">

                <!-- Username Input -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5" for="username">
                        Username
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <i data-lucide="user" class="w-5 h-5"></i>
                        </div>
                        <input
                            type="text"
                            name="username"
                            id="username"
                            value="{{ old('username', 'admin') }}"
                            required
                            placeholder="Enter username"
                            class="block w-full pl-10 pr-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-900 focus:ring-2 focus:ring-primary-500 focus:bg-white focus:outline-none transition-all duration-150"
                        />
                    </div>
                </div>

                <!-- Password Input -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider" for="password">
                            Password
                        </label>
                    </div>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <i data-lucide="lock" class="w-5 h-5"></i>
                        </div>
                        <input
                            type="password"
                            name="password"
                            id="password"
                            value="admin"
                            required
                            placeholder="Enter password"
                            class="block w-full pl-10 pr-10 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-900 focus:ring-2 focus:ring-primary-500 focus:bg-white focus:outline-none transition-all duration-150"
                        />
                        <button
                            type="button"
                            id="toggle-password"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none"
                        >
                            <i data-lucide="eye" id="eye-icon" class="w-5 h-5"></i>
                        </button>
                    </div>
                </div>

                <!-- CAPTCHA Section -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Security Verification
                    </label>
                    <div class="flex items-center gap-3 mb-2">
                        <div id="captcha-container" class="border border-slate-200 rounded-xl overflow-hidden shadow-2xs shrink-0 bg-white">
                            {!! $captchaSvg !!}
                        </div>
                        <button
                            type="button"
                            id="refresh-captcha"
                            title="Refresh challenge"
                            class="p-2.5 text-slate-600 hover:text-primary-600 hover:bg-slate-100 rounded-lg border border-slate-200 focus:outline-none transition-colors"
                        >
                            <i data-lucide="refresh-cw" class="w-5 h-5"></i>
                        </button>
                    </div>
                    <input
                        type="text"
                        name="captcha"
                        id="captcha"
                        required
                        autocomplete="off"
                        placeholder="Enter 5-character code above"
                        class="block w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-900 uppercase font-mono tracking-widest focus:ring-2 focus:ring-primary-500 focus:bg-white focus:outline-none transition-all"
                    />
                </div>

                <!-- Submit Button -->
                <button
                    type="submit"
                    id="submit-btn"
                    class="w-full mt-2 py-3 px-4 bg-primary-600 hover:bg-primary-700 text-white font-bold text-sm rounded-lg shadow-md hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-all duration-150 flex items-center justify-center gap-2"
                >
                    <span>Sign In</span>
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </button>

                <!-- Quick One-Click Login (Pre-fills and automatically signs in) -->
                <div class="pt-2 border-t border-slate-100 mt-4 flex items-center justify-between">
                    <button
                        type="button"
                        id="quick-login-btn"
                        class="text-xs font-semibold text-primary-600 hover:text-primary-800 flex items-center gap-1.5 focus:outline-none"
                    >
                        <i data-lucide="zap" class="w-3.5 h-3.5 text-amber-500"></i>
                        <span>One-Click Admin Demo Login</span>
                    </button>
                    <a href="{{ route('technician.login') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-700 flex items-center gap-1">
                        <i data-lucide="smartphone" class="w-3.5 h-3.5 text-slate-400"></i>
                        <span>Technician Portal</span>
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Password visibility toggle
    $('#toggle-password').on('click', function() {
        const $pw = $('#password');
        const isPw = $pw.attr('type') === 'password';
        $pw.attr('type', isPw ? 'text' : 'password');
        $('#eye-icon').attr('data-lucide', isPw ? 'eye-off' : 'eye');
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });

    // Refresh CAPTCHA challenge via AJAX
    $('#refresh-captcha').on('click', function() {
        const $btn = $(this);
        $btn.find('i').addClass('animate-spin');
        $.get("{{ route('captcha.get') }}", function(res) {
            $btn.find('i').removeClass('animate-spin');
            if (res.success && res.data) {
                $('#challengeId').val(res.data.challengeId);
                $('#captcha-container').html(res.data.svg);
                $('#captcha').val('').focus();
            }
        }).fail(function() {
            $btn.find('i').removeClass('animate-spin');
            window.showToast('Failed to refresh CAPTCHA', 'danger');
        });
    });

    // AJAX Login submission
    $('#login-form').on('submit', function(e) {
        e.preventDefault();
        const $form = $(this);
        const $btn = $('#submit-btn');
        const origText = $btn.html();
        
        $('#login-error-banner').addClass('hidden');
        $btn.prop('disabled', true).html('<span class="inline-block animate-spin mr-2">⟳</span> Signing in...');

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: $form.serialize(),
            success: function(res) {
                if (res.success) {
                    $btn.html('<span class="text-emerald-300">✓ Success!</span>');
                    window.showToast(res.message, 'success');
                    setTimeout(function() {
                        window.location.href = res.redirect || "{{ route('dashboard') }}";
                    }, 400);
                }
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html(origText);
                const msg = (xhr.responseJSON && xhr.responseJSON.message) 
                    ? xhr.responseJSON.message 
                    : 'Failed to authenticate. Please check your credentials.';
                $('#login-error-text').text(msg);
                $('#login-error-banner').removeClass('hidden');
                $('#refresh-captcha').click();
            }
        });
    });

    // One-Click Admin Demo Login
    $('#quick-login-btn').on('click', function() {
        $('#username').val('admin');
        $('#password').val('admin');
        $('#captcha').val('74KB9');
        $('#login-form').append('<input type="hidden" name="is_quick_login" value="1">');
        $('#login-form').submit();
    });
});
</script>
@endsection
