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
            <button onclick="openRecordRentalPaymentModal()" class="btn-primary bg-cyan-600 hover:bg-cyan-700 flex items-center gap-2">
                <i data-lucide="wallet" class="w-4 h-4"></i>
                <span>Record Payment</span>
            </button>
            <button onclick="openCreateRentalModal()" class="btn-primary flex items-center gap-2">
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
                        <th class="py-3.5 px-4">Product / Machine</th>
                        <th class="py-3.5 px-4 text-right">Monthly Rent (₹)</th>
                        <th class="py-3.5 px-4 text-right">Security Deposit (₹)</th>
                        <th class="py-3.5 px-4 text-center">Billing Day</th>
                        <th class="py-3.5 px-4 text-center">Status</th>
                        <th class="py-3.5 px-4 text-right">Actions</th>
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
                                    {{ $r->product_name ?? ($r->asset->custom_name ?? ($r->asset->product->name ?? 'Equipment / Unit')) }}
                                </div>
                                @if($r->asset && $r->asset->serial_number)
                                    <div class="text-[11px] text-slate-500 mt-0.5 font-mono">
                                        S/N: {{ $r->asset->serial_number }}
                                    </div>
                                @endif
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
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $r->status === 'ACTIVE' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($r->status === 'RETURNED' ? 'bg-slate-100 text-slate-600 border border-slate-300' : ($r->status === 'OVERDUE' ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-amber-50 text-amber-700 border border-amber-200')) }}">
                                    {{ $r->status }}
                                </span>
                            </td>

                            <td class="py-3.5 px-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <!-- 1. Send WhatsApp message for payment notification -->
                                    @if($r->customer && $r->customer->phone)
                                        <button type="button"
                                                onclick="sendRentalPaymentWhatsApp('{{ addslashes($r->customer->name ?? 'Customer') }}', '{{ $r->customer->phone }}', '{{ $r->rental_number }}', '{{ addslashes($r->product_name ?? 'Equipment') }}', '{{ $r->monthly_fee }}', '{{ $r->billing_day }}')"
                                                class="btn-outline py-1 px-2.5 text-xs inline-flex items-center gap-1.5 text-emerald-700 border-emerald-300 hover:bg-emerald-50 hover:border-emerald-400"
                                                title="Send WhatsApp Payment Notification">
                                            <i data-lucide="message-circle" class="w-3.5 h-3.5 text-emerald-600"></i>
                                            <span class="hidden xl:inline font-medium">WhatsApp</span>
                                        </button>
                                    @endif

                                    <!-- 2. Record Rental Payment -->
                                    @if($r->status !== 'RETURNED')
                                        <button type="button"
                                                onclick="openRecordRentalPaymentModalForRental('{{ $r->id }}', '{{ $r->rental_number }}', '{{ addslashes($r->customer->name ?? 'Customer') }}', '{{ $r->monthly_fee }}')"
                                                class="btn-outline py-1 px-2.5 text-xs inline-flex items-center gap-1.5 text-cyan-700 border-cyan-300 hover:bg-cyan-50 hover:border-cyan-400"
                                                title="Record Rental Payment">
                                            <i data-lucide="wallet" class="w-3.5 h-3.5 text-cyan-600"></i>
                                            <span class="hidden xl:inline font-medium">Record Payment</span>
                                        </button>
                                    @endif

                                    <!-- 3. Edit Rental -->
                                    <button type="button"
                                            onclick="openEditRentalModal('{{ $r->id }}')"
                                            class="btn-outline py-1 px-2.5 text-xs inline-flex items-center gap-1.5 text-slate-700 border-slate-300 hover:bg-slate-50"
                                            title="Edit Rental Agreement">
                                        <i data-lucide="pencil" class="w-3.5 h-3.5 text-slate-500"></i>
                                        <span class="hidden xl:inline font-medium">Edit</span>
                                    </button>

                                    <!-- 3. Return machine as the rental is over -->
                                    @if($r->status !== 'RETURNED')
                                        <button type="button"
                                                onclick="openReturnRentalModal('{{ $r->id }}', '{{ $r->rental_number }}', '{{ addslashes($r->customer->name ?? 'Customer') }}', '{{ addslashes($r->product_name ?? 'Equipment') }}', '{{ $r->deposit_amount }}')"
                                                class="btn-outline py-1 px-2.5 text-xs inline-flex items-center gap-1.5 text-amber-700 border-amber-300 hover:bg-amber-50 hover:border-amber-400"
                                                title="Return Machine / Rental Over">
                                            <i data-lucide="corner-down-left" class="w-3.5 h-3.5 text-amber-600"></i>
                                            <span class="hidden xl:inline font-medium">Return Machine</span>
                                        </button>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded text-[11px] font-semibold text-slate-500 bg-slate-100 border border-slate-200" title="Machine has been returned">
                                            <i data-lucide="check-check" class="w-3 h-3 text-slate-400"></i>
                                            <span class="hidden xl:inline">Returned</span>
                                        </span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-slate-500">
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

                <!-- Search Bar for Customer -->
                <div class="relative mb-2">
                    <input type="text"
                           id="rentalCustomerSearch"
                           placeholder="Search customer by name, phone, or customer #..."
                           autocomplete="off"
                           class="form-input text-sm pl-9 pr-8"
                           aria-label="Search customer">
                    <div class="absolute left-3 top-2.5 text-slate-400 pointer-events-none">
                        <i data-lucide="search" class="w-4 h-4"></i>
                    </div>
                    <button type="button"
                            id="btnClearRentalCustomerSearch"
                            class="hidden absolute right-2.5 top-2.5 text-slate-400 hover:text-slate-600 focus:outline-none"
                            title="Clear search">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>

                <select name="customer_id" id="rentalCustomerSelect" required class="form-input text-sm">
                    <option value="">Select Customer</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}"
                                data-search="{{ strtolower(($c->full_name ?? $c->name) . ' ' . $c->phone . ' ' . ($c->customer_number ?? '') . ' ' . ($c->company_name ?? '')) }}">
                            {{ $c->full_name ?? $c->name }} ({{ $c->phone }}){{ $c->company_name ? ' - ' . $c->company_name : ($c->customer_number ? ' [' . $c->customer_number . ']' : '') }}
                        </option>
                    @endforeach
                </select>
                <div id="rentalCustomerSearchHelp" class="text-[11px] text-slate-500 mt-1 hidden"></div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Product Name <span class="text-rose-500">*</span></label>
                <input type="text" name="product_name" required placeholder="Enter product name (e.g. RO Water Purifier)" class="form-input text-sm">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Monthly Rent (₹) <span class="text-rose-500">*</span></label>
                    <input type="number" name="monthly_fee" min="1" step="0.01" required placeholder="500" class="form-input text-sm font-mono font-bold">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Security Deposit (₹)</label>
                    <input type="number" name="deposit_amount" min="0" step="0.01" placeholder="1500" class="form-input text-sm font-mono">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Agreement Start Date <span class="text-rose-500">*</span></label>
                    <input type="date" name="start_date" required class="form-input text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Monthly Billing Day (1-31) <span class="text-rose-500">*</span></label>
                    <input type="number" name="billing_day" min="1" max="31" required placeholder="1" class="form-input text-sm font-mono">
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

<!-- Modal: Edit Rental Agreement -->
<div id="modalEditRental" class="modal-backdrop hidden">
    <div class="modal-box max-w-lg">
        <div class="flex items-center justify-between pb-4 border-b border-slate-200">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center">
                    <i data-lucide="pencil" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 text-lg flex items-center gap-2">
                        <span>Edit Rental</span>
                        <span id="editRentalNumberBadge" class="text-xs px-2 py-0.5 rounded-full bg-sky-100 text-sky-800 font-mono font-bold"></span>
                    </h3>
                    <p class="text-xs text-slate-500">Modify rental terms, rates, status and schedule</p>
                </div>
            </div>
            <button onclick="CRM.closeModal('modalEditRental')" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form id="formEditRental" method="POST" class="mt-4 space-y-4">
            @csrf
            @method('PUT')
            <input type="hidden" id="editRentalId" name="rental_id">

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Customer <span class="text-rose-500">*</span></label>

                <!-- Search Bar for Customer in Edit Modal -->
                <div class="relative mb-2">
                    <input type="text"
                           id="editRentalCustomerSearch"
                           placeholder="Search customer by name, phone, or customer #..."
                           autocomplete="off"
                           class="form-input text-sm pl-9 pr-8"
                           aria-label="Search customer">
                    <div class="absolute left-3 top-2.5 text-slate-400 pointer-events-none">
                        <i data-lucide="search" class="w-4 h-4"></i>
                    </div>
                    <button type="button"
                            id="btnClearEditRentalCustomerSearch"
                            class="hidden absolute right-2.5 top-2.5 text-slate-400 hover:text-slate-600 focus:outline-none"
                            title="Clear search">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>

                <select name="customer_id" id="editCustomerId" required class="form-input text-sm">
                    <option value="">Select Customer</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}"
                                data-search="{{ strtolower(($c->full_name ?? $c->name) . ' ' . $c->phone . ' ' . ($c->customer_number ?? '') . ' ' . ($c->company_name ?? '')) }}">
                            {{ $c->full_name ?? $c->name }} ({{ $c->phone }}){{ $c->company_name ? ' - ' . $c->company_name : ($c->customer_number ? ' [' . $c->customer_number . ']' : '') }}
                        </option>
                    @endforeach
                </select>
                <div id="editRentalCustomerSearchHelp" class="text-[11px] text-slate-500 mt-1 hidden"></div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Product Name <span class="text-rose-500">*</span></label>
                <input type="text" name="product_name" id="editProductName" required placeholder="Enter product name (e.g. RO Water Purifier)" class="form-input text-sm">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Monthly Rent (₹) <span class="text-rose-500">*</span></label>
                    <input type="number" name="monthly_fee" id="editMonthlyFee" min="1" step="0.01" required placeholder="500" class="form-input text-sm font-mono font-bold">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Security Deposit (₹)</label>
                    <input type="number" name="deposit_amount" id="editDepositAmount" min="0" step="0.01" placeholder="1500" class="form-input text-sm font-mono">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Agreement Start Date <span class="text-rose-500">*</span></label>
                    <input type="date" name="start_date" id="editStartDate" required class="form-input text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Agreement End Date</label>
                    <input type="date" name="end_date" id="editEndDate" class="form-input text-sm">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Monthly Billing Day (1-31) <span class="text-rose-500">*</span></label>
                    <input type="number" name="billing_day" id="editBillingDay" min="1" max="31" required placeholder="1" class="form-input text-sm font-mono">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Status <span class="text-rose-500">*</span></label>
                    <select name="status" id="editStatus" required class="form-input text-xs">
                        <option value="ACTIVE">ACTIVE</option>
                        <option value="PAYMENT_DUE">PAYMENT_DUE</option>
                        <option value="OVERDUE">OVERDUE</option>
                        <option value="SUSPENDED">SUSPENDED</option>
                        <option value="RETURNED">RETURNED</option>
                        <option value="COMPLETED">COMPLETED</option>
                        <option value="CANCELLED">CANCELLED</option>
                        <option value="TERMINATED">TERMINATED</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Terms &amp; Notes</label>
                <textarea name="notes" id="editNotes" rows="2" placeholder="Maintenance included, notes..." class="form-input text-xs"></textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200">
                <button type="button" onclick="CRM.closeModal('modalEditRental')" class="btn-outline">Cancel</button>
                <button type="submit" class="btn-primary flex items-center gap-2">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span>Save Changes</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Return Machine (Rental Over) -->
<div id="modalReturnRental" class="modal-backdrop hidden">
    <div class="modal-box max-w-lg">
        <div class="flex items-center justify-between pb-4 border-b border-slate-200">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <i data-lucide="corner-down-left" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 text-lg">Return Machine</h3>
                    <p class="text-xs text-slate-500">Rental period over: record return date and deposit refund</p>
                </div>
            </div>
            <button onclick="CRM.closeModal('modalReturnRental')" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form id="formReturnRental" method="POST" class="mt-4 space-y-4">
            @csrf

            <!-- Rental Details Summary Box -->
            <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 text-xs space-y-2">
                <div class="flex justify-between items-center">
                    <span class="text-slate-500 font-medium">Rental Agreement:</span>
                    <span id="returnRentalNumberDisp" class="font-mono font-bold text-slate-900">—</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-500 font-medium">Customer:</span>
                    <span id="returnCustomerDisp" class="font-semibold text-slate-900">—</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-500 font-medium">Machine / Product:</span>
                    <span id="returnProductDisp" class="font-semibold text-slate-900">—</span>
                </div>
                <div class="flex justify-between items-center pt-1 border-t border-slate-200">
                    <span class="text-slate-500 font-medium">Security Deposit Held:</span>
                    <span id="returnDepositDisp" class="font-mono font-bold text-emerald-700">₹0.00</span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Return Date <span class="text-rose-500">*</span></label>
                    <input type="date" name="return_date" id="returnDateInput" value="{{ date('Y-m-d') }}" required class="form-input text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Refund Security Deposit (₹)</label>
                    <input type="number" name="refund_amount" id="returnRefundAmountInput" min="0" step="0.01" class="form-input text-sm font-mono font-bold">
                    <p class="text-[10px] text-slate-400 mt-0.5">Deposit amount returned to customer</p>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Condition &amp; Return Notes</label>
                <textarea name="notes" id="returnNotesInput" rows="2" placeholder="Machine received in good condition, filters inspected..." class="form-input text-xs"></textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200">
                <button type="button" onclick="CRM.closeModal('modalReturnRental')" class="btn-outline">Cancel</button>
                <button type="submit" class="btn-primary bg-amber-600 hover:bg-amber-700 flex items-center gap-2">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span>Confirm Machine Return</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Record Rental Payment -->
<div id="modalRecordRentalPayment" class="modal-backdrop hidden">
    <div class="modal-box max-w-lg">
        <div class="flex items-center justify-between pb-4 border-b border-slate-200">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-cyan-50 text-cyan-600 flex items-center justify-center">
                    <i data-lucide="repeat" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 text-lg">Record Rental Payment</h3>
                    <p class="text-xs text-slate-500">Record recurring monthly rental fee collection with receipt</p>
                </div>
            </div>
            <button onclick="CRM.closeModal('modalRecordRentalPayment')" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form id="formRecordRentalPayment" action="{{ route('payments.rentals.store') }}" method="POST" class="mt-4 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Select Rental Contract <span class="text-rose-500">*</span></label>
                <select name="rental_id" id="rentalSelect" required class="form-input text-sm">
                    <option value="">Select Rental Agreement</option>
                    @foreach($activeRentals as $ar)
                        <option value="{{ $ar->id }}" data-fee="{{ $ar->monthly_fee ?? 0 }}" data-customer="{{ $ar->customer->name ?? '' }}">
                            {{ $ar->rental_number }} - {{ $ar->customer->name ?? 'Customer' }} ({{ $ar->product_name ?? 'Equipment' }}) - Monthly: ₹{{ number_format($ar->monthly_fee ?? 0, 2) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Billing Month <span class="text-rose-500">*</span></label>
                    <input type="month" name="billing_month" id="rentalBillingMonth" value="{{ date('Y-m') }}" required class="form-input text-sm font-medium">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Amount (₹) <span class="text-rose-500">*</span></label>
                    <input type="number" name="amount" id="rentalAmountInput" min="1" step="0.01" required placeholder="0.00" class="form-input text-sm font-mono font-bold">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Payment Date <span class="text-rose-500">*</span></label>
                    <input type="date" name="payment_date" id="rentalPaymentDate" value="{{ date('Y-m-d') }}" required class="form-input text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Payment Mode <span class="text-rose-500">*</span></label>
                    <select name="payment_method" id="rentalPaymentMethod" required class="form-input text-xs">
                        <option value="UPI">UPI (Google Pay / PhonePe / Paytm)</option>
                        <option value="CASH">Cash</option>
                        <option value="BANK_TRANSFER">Bank Transfer (NEFT/IMPS)</option>
                        <option value="CHEQUE">Cheque</option>
                        <option value="CARD">Debit / Credit Card</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Reference / UTR #</label>
                <input type="text" name="transaction_reference" id="rentalTxnRef" placeholder="e.g. UPI Ref / Cheque No." class="form-input text-xs">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Notes</label>
                <textarea name="notes" id="rentalPaymentNotes" rows="2" placeholder="Rental payment remarks..." class="form-input text-xs"></textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200">
                <button type="button" onclick="CRM.closeModal('modalRecordRentalPayment')" class="btn-outline">Cancel</button>
                <button type="submit" class="btn-primary bg-cyan-600 hover:bg-cyan-700 flex items-center gap-2">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span>Record Rental Payment</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let createCustomerFilter;
let editCustomerFilter;

function setupCustomerSearchFilter(searchInputId, clearBtnId, selectId, helpId) {
    const $search = $('#' + searchInputId);
    const $clear = $('#' + clearBtnId);
    const $select = $('#' + selectId);
    const $help = $('#' + helpId);

    const optionsList = [];
    $select.find('option').each(function() {
        const val = $(this).val();
        optionsList.push({
            value: val,
            text: $(this).text(),
            search: ($(this).data('search') || $(this).text()).toString().toLowerCase(),
            isPlaceholder: (val === '')
        });
    });

    function filterOptions(query) {
        const currentVal = $select.val();
        query = (query || '').trim().toLowerCase();

        if (query.length > 0) {
            $clear.removeClass('hidden');
        } else {
            $clear.addClass('hidden');
            $help.addClass('hidden').html('');
        }

        const placeholder = optionsList.find(o => o.isPlaceholder) || { value: '', text: 'Select Customer' };

        if (!query) {
            $select.empty();
            $select.append(new Option(placeholder.text, placeholder.value));
            optionsList.filter(o => !o.isPlaceholder).forEach(function(opt) {
                const isSelected = (opt.value === currentVal && currentVal !== '');
                $select.append(new Option(opt.text, opt.value, isSelected, isSelected));
            });
            if (currentVal) {
                $select.val(currentVal);
            }
            return;
        }

        const matches = optionsList.filter(function(opt) {
            return !opt.isPlaceholder && opt.search.includes(query);
        });

        $select.empty();

        if (matches.length === 0) {
            $select.append(new Option('No customer matches "' + query + '"', '', true, true));
            $help.removeClass('hidden').html(
                '<span class="text-rose-500 font-medium">No customer found matching "' + $('<div>').text(query).html() + '"</span>'
            );
        } else {
            $select.append(new Option('-- Select Customer (' + matches.length + ' matching) --', ''));
            let matchedPrev = false;
            matches.forEach(function(opt) {
                const isSelected = (opt.value === currentVal && currentVal !== '');
                if (isSelected) matchedPrev = true;
                $select.append(new Option(opt.text, opt.value, isSelected, isSelected));
            });

            if (matches.length === 1) {
                $select.val(matches[0].value);
            } else if (!matchedPrev) {
                $select.val('');
            }

            $help.removeClass('hidden').html(
                '<span class="text-slate-500">Found ' + matches.length + ' matching customer' + (matches.length > 1 ? 's' : '') + '</span>'
            );
        }
    }

    $search.on('input', function() {
        filterOptions($(this).val());
    });

    $clear.on('click', function() {
        $search.val('');
        filterOptions('');
        $search.focus();
    });

    $search.on('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            $select.focus();
        }
    });

    return {
        reset: function() {
            $search.val('');
            filterOptions('');
        }
    };
}

$(document).ready(function() {
    createCustomerFilter = setupCustomerSearchFilter(
        'rentalCustomerSearch',
        'btnClearRentalCustomerSearch',
        'rentalCustomerSelect',
        'rentalCustomerSearchHelp'
    );

    editCustomerFilter = setupCustomerSearchFilter(
        'editRentalCustomerSearch',
        'btnClearEditRentalCustomerSearch',
        'editCustomerId',
        'editRentalCustomerSearchHelp'
    );

    CRM.handleAjaxForm('#formCreateRental', function(res) {
        CRM.toast(res.message || 'Rental agreement created!');
        setTimeout(() => location.reload(), 600);
    });

    CRM.handleAjaxForm('#formEditRental', function(res) {
        CRM.toast(res.message || 'Rental agreement updated!');
        setTimeout(() => location.reload(), 600);
    });

    CRM.handleAjaxForm('#formReturnRental', function(res) {
        CRM.toast(res.message || 'Machine marked as returned!');
        setTimeout(() => location.reload(), 600);
    });

    $('#rentalSelect').on('change', function() {
        const fee = $(this).find(':selected').data('fee');
        if (fee && parseFloat(fee) > 0) {
            $('#rentalAmountInput').val(parseFloat(fee).toFixed(2));
        }
    });

    CRM.handleAjaxForm('#formRecordRentalPayment', function(res) {
        CRM.toast(res.message || 'Rental payment recorded successfully!');
        setTimeout(() => {
            window.location.href = "{{ route('payments.index', ['tab' => 'rentals']) }}";
        }, 600);
    });
});

function openRecordRentalPaymentModal() {
    $('#rentalSelect').val('');
    $('#rentalAmountInput').val('');
    $('#rentalBillingMonth').val(new Date().toISOString().slice(0, 7));
    $('#rentalPaymentDate').val(new Date().toISOString().slice(0, 10));
    $('#rentalTxnRef').val('');
    $('#rentalPaymentNotes').val('');
    CRM.openModal('modalRecordRentalPayment');
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function openRecordRentalPaymentModalForRental(rentalId, rentalNumber, customerName, monthlyFee) {
    $('#rentalSelect').val(rentalId);
    const fee = parseFloat(monthlyFee) || 0;
    if (fee > 0) {
        $('#rentalAmountInput').val(fee.toFixed(2));
    }
    $('#rentalBillingMonth').val(new Date().toISOString().slice(0, 7));
    $('#rentalPaymentDate').val(new Date().toISOString().slice(0, 10));
    $('#rentalTxnRef').val('');
    $('#rentalPaymentNotes').val('');
    CRM.openModal('modalRecordRentalPayment');
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function openCreateRentalModal() {
    if (createCustomerFilter) {
        createCustomerFilter.reset();
    }
    CRM.openModal('modalCreateRental');
    if (typeof lucide !== 'undefined') lucide.createIcons();
    setTimeout(() => $('#rentalCustomerSearch').focus(), 150);
}

function sendRentalPaymentWhatsApp(name, phone, rentalNumber, productName, monthlyFee, billingDay) {
    if (!phone) {
        CRM.toast('Customer has no phone number recorded.', 'error');
        return;
    }
    const cleanPhone = phone.replace(/[^0-9]/g, '');
    const fullPhone = cleanPhone.length === 10 ? '91' + cleanPhone : cleanPhone;
    const company = "{{ \App\Models\SystemSetting::get('company_name', 'Enterprises CRM') }}";
    const formattedFee = parseFloat(monthlyFee).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const msg = encodeURIComponent(
        `Dear ${name},\n\n` +
        `Greetings from *${company}*.\n\n` +
        `This is a payment notification regarding your rental agreement *${rentalNumber}* for *${productName}*.\n\n` +
        `• Monthly Rent: *₹${formattedFee}*\n` +
        `• Billing Schedule: *${billingDay}th of each month*\n\n` +
        `Kindly clear your rental payment to ensure uninterrupted service.\n\n` +
        `Thank you for choosing us!`
    );
    window.open(`https://wa.me/${fullPhone}?text=${msg}`, '_blank');
}

function openEditRentalModal(rentalId) {
    $.get('/rentals/' + rentalId, function(res) {
        if (res.success && res.data) {
            const r = res.data;
            if (editCustomerFilter) {
                editCustomerFilter.reset();
            }
            $('#editRentalId').val(r.id);
            $('#editRentalNumberBadge').text(r.rental_number);
            $('#editCustomerId').val(r.customer_id);
            $('#editProductName').val(r.product_name);
            $('#editMonthlyFee').val(r.monthly_fee);
            $('#editDepositAmount').val(r.deposit_amount);
            $('#editStartDate').val(r.start_date);
            $('#editEndDate').val(r.end_date);
            $('#editBillingDay').val(r.billing_day);
            $('#editStatus').val(r.status);
            $('#editNotes').val(r.notes);

            $('#formEditRental').attr('action', '/rentals/' + r.id);
            CRM.openModal('modalEditRental');
            if (typeof lucide !== 'undefined') lucide.createIcons();
            setTimeout(() => $('#editRentalCustomerSearch').focus(), 150);
        } else {
            CRM.toast('Unable to load rental details.', 'error');
        }
    }).fail(function() {
        CRM.toast('Failed to fetch rental record.', 'error');
    });
}

function openReturnRentalModal(rentalId, rentalNumber, customerName, productName, depositAmount) {
    $('#returnRentalNumberDisp').text(rentalNumber);
    $('#returnCustomerDisp').text(customerName);
    $('#returnProductDisp').text(productName);
    const deposit = parseFloat(depositAmount) || 0;
    $('#returnDepositDisp').text('₹' + deposit.toLocaleString('en-IN', { minimumFractionDigits: 2 }));
    $('#returnRefundAmountInput').val(deposit.toFixed(2));
    $('#returnDateInput').val(new Date().toISOString().slice(0, 10));
    $('#returnNotesInput').val('');

    $('#formReturnRental').attr('action', '/rentals/' + rentalId + '/return');
    CRM.openModal('modalReturnRental');
    if (typeof lucide !== 'undefined') lucide.createIcons();
}
</script>
@endsection
