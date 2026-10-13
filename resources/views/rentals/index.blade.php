@extends('layouts.app')

@section('title', 'Equipment Rentals - Enterprises CRM')

@section('content')
<div class="space-y-6 max-w-[1600px] mx-auto pb-12 select-none">
    <!-- 1. Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center shadow-xs shrink-0">
                <i data-lucide="repeat" class="w-6 h-6"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Equipment Rentals</h1>
                <p class="text-xs sm:text-sm text-slate-500 font-normal">
                    Manage monthly equipment subscriptions, security deposits and recurring revenue
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2.5">
            <button onclick="CRM.openModal('modalCreateRental')" class="btn-primary flex items-center gap-2">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span>New Rental Contract</span>
            </button>
        </div>
    </div>

    <!-- 2. KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Active Subscriptions</span>
                <div class="w-8 h-8 rounded-lg bg-cyan-50 text-cyan-600 flex items-center justify-center">
                    <i data-lucide="check-circle" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3 text-2xl font-bold text-slate-900 font-mono">
                {{ $activeRentalsCount }}
            </div>
            <div class="mt-1 text-xs text-slate-500">Live active machine rentals</div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Monthly MRR</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <i data-lucide="trending-up" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3 text-2xl font-bold text-emerald-600 font-mono">
                ₹{{ number_format($totalMonthlyRevenue, 2) }}
            </div>
            <div class="mt-1 text-xs text-slate-500">Recurring monthly billing base</div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Deposits Held</span>
                <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center">
                    <i data-lucide="shield" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3 text-2xl font-bold text-purple-600 font-mono">
                ₹{{ number_format($totalDepositsHeld, 2) }}
            </div>
            <div class="mt-1 text-xs text-slate-500">Refundable security deposits</div>
        </div>
    </div>

    <!-- 3. Rentals Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/75 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        <th class="py-3.5 px-4">Rental #</th>
                        <th class="py-3.5 px-4">Customer</th>
                        <th class="py-3.5 px-4">Asset / Machine</th>
                        <th class="py-3.5 px-4 text-right">Monthly Rent (₹)</th>
                        <th class="py-3.5 px-4 text-right">Security Deposit (₹)</th>
                        <th class="py-3.5 px-4 text-center">Billing Day</th>
                        <th class="py-3.5 px-4 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($rentals as $r)
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="py-3.5 px-4 font-mono font-bold text-slate-900">
                                {{ $r->rental_number }}
                            </td>

                            <td class="py-3.5 px-4">
                                <div>
                                    <a href="{{ $r->customer_id ? route('customers.show', $r->customer_id) : '#' }}" class="font-semibold text-slate-900 hover:text-sky-600 flex items-center gap-1">
                                        <i data-lucide="user" class="w-3.5 h-3.5 text-slate-400"></i>
                                        {{ $r->customer->name ?? 'Direct Customer' }}
                                    </a>
                                    <div class="text-xs text-slate-500 mt-0.5 font-mono">
                                        {{ $r->customer->phone ?? 'N/A' }}
                                    </div>
                                </div>
                            </td>

                            <td class="py-3.5 px-4">
                                <div class="font-medium text-slate-800 text-xs">
                                    {{ $r->asset->custom_name ?? ($r->asset->product->name ?? 'Equipment / Unit') }}
                                </div>
                                <div class="text-[11px] text-slate-500 mt-0.5 font-mono">
                                    S/N: {{ $r->asset->serial_number ?? 'N/A' }}
                                </div>
                            </td>

                            <td class="py-3.5 px-4 text-right font-mono text-xs font-bold text-emerald-700">
                                ₹{{ number_format($r->monthly_fee, 2) }}
                            </td>

                            <td class="py-3.5 px-4 text-right font-mono text-xs text-slate-600">
                                ₹{{ number_format($r->deposit_amount, 2) }}
                            </td>

                            <td class="py-3.5 px-4 text-center font-mono text-xs">
                                {{ $r->billing_day }}th of month
                            </td>

                            <td class="py-3.5 px-4 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    {{ $r->status }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-500">
                                <div class="flex flex-col items-center justify-center">
                                    <i data-lucide="repeat" class="w-10 h-10 text-slate-300 mb-2"></i>
                                    <p class="font-medium">No active rental agreements</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($rentals->hasPages())
            <div class="p-4 border-t border-slate-200/80 bg-slate-50/50">
                {{ $rentals->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal: Create Rental Agreement -->
<div id="modalCreateRental" class="modal-backdrop hidden">
    <div class="modal-box max-w-lg">
        <div class="flex items-center justify-between pb-4 border-b border-slate-200">
            <h3 class="font-bold text-slate-900 text-lg flex items-center gap-2">
                <i data-lucide="repeat" class="w-5 h-5 text-cyan-600"></i>
                <span>New Rental Agreement</span>
            </h3>
            <button onclick="CRM.closeModal('modalCreateRental')" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form id="formCreateRental" action="{{ route('rentals.store') }}" method="POST" class="mt-4 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Customer <span class="text-rose-500">*</span></label>
                <select name="customer_id" required class="form-input text-sm">
                    <option value="">Select Customer</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->phone }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Equipment Machine / Asset <span class="text-rose-500">*</span></label>
                <select name="asset_id" required class="form-input text-sm">
                    <option value="">Select Asset</option>
                    @foreach($assets as $a)
                        <option value="{{ $a->id }}">{{ $a->custom_name ?? ($a->product->name ?? 'Equipment Unit') }} (S/N: {{ $a->serial_number }})</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Monthly Rent (₹) <span class="text-rose-500">*</span></label>
                    <input type="number" name="monthly_fee" value="500" min="1" step="0.01" required class="form-input text-sm font-mono font-bold">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Security Deposit (₹)</label>
                    <input type="number" name="deposit_amount" value="1500" min="0" step="0.01" class="form-input text-sm font-mono">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Agreement Start Date <span class="text-rose-500">*</span></label>
                    <input type="date" name="start_date" value="{{ date('Y-m-d') }}" required class="form-input text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Monthly Billing Day (1-31) <span class="text-rose-500">*</span></label>
                    <input type="number" name="billing_day" value="1" min="1" max="31" required class="form-input text-sm font-mono">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Terms &amp; Notes</label>
                <textarea name="notes" rows="2" placeholder="Maintenance included, minimum tenure 6 months..." class="form-input text-xs"></textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200">
                <button type="button" onclick="CRM.closeModal('modalCreateRental')" class="btn-outline">Cancel</button>
                <button type="submit" class="btn-primary flex items-center gap-2">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span>Create Agreement</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    CRM.handleAjaxForm('#formCreateRental', function(res) {
        CRM.toast(res.message || 'Rental agreement created!');
        setTimeout(() => location.reload(), 600);
    });
});
</script>
@endsection
