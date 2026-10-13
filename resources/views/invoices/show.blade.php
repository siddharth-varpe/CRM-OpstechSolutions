@extends('layouts.app')

@section('title', 'Invoice ' . $invoice->invoice_number . ' - Enterprises CRM')

@section('content')
<div class="space-y-6 max-w-5xl mx-auto pb-16">
    <!-- Action Toolbar (hidden on print) -->
    <div class="no-print flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs">
        <div class="flex items-center gap-3">
            <a href="{{ route('invoices.index') }}" class="btn-outline flex items-center gap-2">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Back</span>
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="font-bold text-slate-900 text-lg font-mono">{{ $invoice->invoice_number }}</h2>
                    @php
                        $badgeColors = [
                            'PAID' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                            'ISSUED' => 'bg-sky-50 text-sky-700 border-sky-200',
                            'PARTIAL' => 'bg-amber-50 text-amber-700 border-amber-200',
                            'OVERDUE' => 'bg-rose-50 text-rose-700 border-rose-200',
                            'CANCELLED' => 'bg-slate-100 text-slate-600 border-slate-200',
                        ];
                        $badge = $badgeColors[$invoice->status] ?? 'bg-slate-50 text-slate-700 border-slate-200';
                    @endphp
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold border {{ $badge }}">
                        {{ $invoice->status }}
                    </span>
                </div>
                <div class="text-xs text-slate-500 mt-0.5">
                    Customer: <span class="font-semibold text-slate-700">{{ $invoice->customer_name }}</span>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button onclick="window.print()" class="btn-primary flex items-center gap-2">
                <i data-lucide="printer" class="w-4 h-4"></i>
                <span>Print Invoice</span>
            </button>

            <button onclick="handleWhatsAppShare()" class="btn-outline text-emerald-700 border-emerald-300 hover:bg-emerald-50 flex items-center gap-2">
                <i data-lucide="message-circle" class="w-4 h-4 text-emerald-600"></i>
                <span>WhatsApp</span>
            </button>

            <button onclick="handleSendDueMail()" class="btn-outline flex items-center gap-2">
                <i data-lucide="mail" class="w-4 h-4 text-slate-500"></i>
                <span>Email</span>
            </button>

            @if($invoice->status !== 'PAID' && $invoice->status !== 'CANCELLED')
                <button onclick="CRM.openModal('modalRecordPayment')" class="btn-outline text-sky-700 border-sky-300 hover:bg-sky-50 flex items-center gap-2">
                    <i data-lucide="credit-card" class="w-4 h-4 text-sky-600"></i>
                    <span>Record Payment</span>
                </button>
            @endif

            @if($invoice->status !== 'CANCELLED' && $invoice->paid_amount == 0)
                <button onclick="CRM.openModal('modalCancelInvoice')" class="btn-outline text-rose-700 border-rose-300 hover:bg-rose-50 flex items-center gap-2">
                    <i data-lucide="x-circle" class="w-4 h-4 text-rose-600"></i>
                    <span>Cancel</span>
                </button>
            @endif
        </div>
    </div>

    <!-- Official Printable GST Invoice Card -->
    <div id="printableInvoice" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-8 sm:p-10 font-sans text-slate-800">
        <!-- Top Company & Invoice Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start gap-6 border-b border-slate-200 pb-8">
            <div class="flex items-start gap-4">
                <img src="/assets/crm-logo.png" alt="Enterprises CRM Logo" class="h-16 w-auto object-contain">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight uppercase">{{ \App\Models\SystemSetting::get('company_name', 'ENTERPRISES CRM') }}</h1>
                    <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                        Industrial Equipment, Maintenance &amp; Engineering Solutions<br>
                        {{ \App\Models\SystemSetting::get('company_address', 'Plot No. 42, Industrial Area, Phase II, Pune - 411019, MH, India') }}<br>
                        <strong>GSTIN:</strong> {{ \App\Models\SystemSetting::get('company_gstin', \App\Models\SystemSetting::get('gst_number', '27AABCS1429B1Z')) }} | <strong>Phone:</strong> {{ \App\Models\SystemSetting::get('company_phone', \App\Models\SystemSetting::get('support_phone', '+91 98765 43210')) }}<br>
                        <strong>Email:</strong> {{ \App\Models\SystemSetting::get('company_email', \App\Models\SystemSetting::get('support_email', 'billing@enterprisescrm.com')) }}
                    </p>
                </div>
            </div>

            <div class="text-left sm:text-right">
                <div class="inline-block bg-sky-50 border border-sky-200 text-sky-800 text-xs font-bold uppercase tracking-widest px-3 py-1 rounded-md mb-2">
                    TAX INVOICE
                </div>
                <div class="text-xl font-bold font-mono text-slate-900">{{ $invoice->invoice_number }}</div>
                <div class="text-xs text-slate-500 mt-1 font-mono">
                    <strong>Date:</strong> {{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d F Y') }}
                </div>
                <div class="text-xs text-slate-500 font-mono">
                    <strong>Due Date:</strong> {{ \Carbon\Carbon::parse($invoice->due_date)->format('d F Y') }}
                </div>
            </div>
        </div>

        <!-- Billing Details Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-8 py-6 border-b border-slate-200 text-xs">
            <div>
                <span class="font-bold text-slate-500 uppercase tracking-wider block mb-1">Billed To (Customer):</span>
                <div class="text-base font-bold text-slate-900">{{ $invoice->customer_name }}</div>
                <div class="text-slate-600 mt-1 leading-relaxed">
                    @if($invoice->customer && $invoice->customer->addresses->isNotEmpty())
                        @php $addr = $invoice->customer->addresses->first(); @endphp
                        {{ $addr->address_line1 }}<br>
                        @if($addr->address_line2){{ $addr->address_line2 }}<br>@endif
                        {{ $addr->city }}, {{ $addr->state }} - {{ $addr->pincode }}<br>
                    @endif
                    <strong>Phone:</strong> {{ $invoice->customer_phone ?? 'N/A' }}<br>
                    @if($invoice->customer && $invoice->customer->email)
                        <strong>Email:</strong> {{ $invoice->customer->email }}<br>
                    @endif
                    @if($invoice->customer && $invoice->customer->customer_number)
                        <strong>Cust ID:</strong> {{ $invoice->customer->customer_number }}
                    @endif
                </div>
            </div>

            <div class="sm:text-right">
                <span class="font-bold text-slate-500 uppercase tracking-wider block mb-1">Payment &amp; Terms:</span>
                <div class="text-slate-600 space-y-1">
                    <div><strong>Status:</strong> <span class="font-bold text-slate-900">{{ $invoice->status }}</span></div>
                    <div><strong>Payment Method:</strong> UPI / Net Banking / Cash</div>
                    <div><strong>Place of Supply:</strong> Maharashtra (27)</div>
                    @if($invoice->sale)
                        <div><strong>Order Ref:</strong> {{ $invoice->sale->sale_number }}</div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Line Items Table -->
        <div class="py-6 border-b border-slate-200 overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-700 font-bold uppercase">
                        <th class="py-3 px-3 w-10 text-center">#</th>
                        <th class="py-3 px-3">Item Description</th>
                        <th class="py-3 px-3 text-center">Qty</th>
                        <th class="py-3 px-3 text-right">Unit Price</th>
                        <th class="py-3 px-3 text-right">Taxable Val</th>
                        <th class="py-3 px-3 text-right">GST %</th>
                        <th class="py-3 px-3 text-right">Total (₹)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($invoice->items as $idx => $it)
                        @php
                            $taxRate = isset($it->tax_rate_percent) ? (float)$it->tax_rate_percent : ($it->tax_rate ?? \App\Models\SystemSetting::get('default_gst_rate', 0));
                            $taxableVal = $it->quantity * $it->unit_price;
                            $taxVal = ($taxableVal * $taxRate) / 100;
                            $totalVal = $taxableVal + $taxVal;
                        @endphp
                        <tr>
                            <td class="py-3.5 px-3 text-center text-slate-400 font-mono">{{ $idx + 1 }}</td>
                            <td class="py-3.5 px-3">
                                <div class="font-bold text-slate-900">{{ $it->item_description }}</div>
                                @if($it->product)
                                    <div class="text-[11px] text-slate-500 mt-0.5">SKU: {{ $it->product->sku }} • {{ $it->product->model_number }}</div>
                                @endif
                            </td>
                            <td class="py-3.5 px-3 text-center font-mono font-semibold">{{ $it->quantity }}</td>
                            <td class="py-3.5 px-3 text-right font-mono">₹{{ number_format($it->unit_price, 2) }}</td>
                            <td class="py-3.5 px-3 text-right font-mono">₹{{ number_format($taxableVal, 2) }}</td>
                            <td class="py-3.5 px-3 text-right font-mono text-slate-600">{{ $taxRate > 0 ? $taxRate . '%' : '0%' }}</td>
                            <td class="py-3.5 px-3 text-right font-mono font-bold text-slate-900">₹{{ number_format($it->total_amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-4 text-center text-slate-400">No items listed.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Calculations Summary & In Words -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 py-6 border-b border-slate-200 text-xs">
            <div>
                <span class="font-bold text-slate-500 uppercase tracking-wider block mb-1">Invoice Notes:</span>
                <p class="text-slate-600 italic">
                    {{ $invoice->notes ?? 'Thank you for your business. Warranty is valid as per manufacturer guidelines from the date of invoice.' }}
                </p>
            </div>

            <div class="space-y-2">
                <div class="flex justify-between text-slate-600">
                    <span>Taxable Subtotal:</span>
                    <span class="font-mono font-semibold">₹{{ number_format($invoice->subtotal, 2) }}</span>
                </div>
                @if((float)$invoice->tax_amount > 0)
                    <div class="flex justify-between text-slate-600">
                        <span>CGST:</span>
                        <span class="font-mono font-semibold">₹{{ number_format($invoice->tax_amount / 2, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>SGST:</span>
                        <span class="font-mono font-semibold">₹{{ number_format($invoice->tax_amount / 2, 2) }}</span>
                    </div>
                @else
                    <div class="flex justify-between text-slate-500 italic">
                        <span>GST / Tax:</span>
                        <span class="font-mono font-semibold">₹0.00 (Exempt / Nil Rated)</span>
                    </div>
                @endif
                <div class="flex justify-between text-base font-bold text-slate-900 border-t border-slate-200 pt-2">
                    <span>Total Amount (INR):</span>
                    <span class="font-mono text-sky-700 text-lg">₹{{ number_format($invoice->total_amount, 2) }}</span>
                </div>
                <div class="flex justify-between text-xs font-semibold text-emerald-700">
                    <span>Paid Amount:</span>
                    <span class="font-mono">₹{{ number_format($invoice->paid_amount, 2) }}</span>
                </div>
                <div class="flex justify-between text-xs font-bold text-amber-700 border-t border-dashed border-slate-200 pt-1">
                    <span>Balance Due:</span>
                    <span class="font-mono">₹{{ number_format($invoice->outstanding_amount, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Lower Section: Bank Details, QR, Terms, Signatory (Dynamic from System Settings) -->
        <div class="mt-8 border border-slate-300 rounded-xl overflow-hidden grid grid-cols-1 md:grid-cols-4 divide-y md:divide-y-0 md:divide-x divide-slate-300 bg-white text-xs">
            <!-- 1. Bank Details -->
            <div class="p-5 flex flex-col justify-between">
                <div>
                    <h4 class="font-bold text-slate-900 text-sm mb-3">Bank Details</h4>
                    <div class="space-y-1.5 text-slate-700">
                        <div><span class="font-bold text-slate-900">Bank:</span> {{ \App\Models\SystemSetting::get('bank_name', 'State Bank of India') }}</div>
                        <div><span class="font-bold text-slate-900">Name:</span> {{ \App\Models\SystemSetting::get('company_name', 'Enterprises CRM') }}</div>
                        <div><span class="font-bold text-slate-900">A/c No:</span> <span class="font-mono font-semibold">{{ \App\Models\SystemSetting::get('bank_account_number', '30998877665') }}</span></div>
                        <div><span class="font-bold text-slate-900">IFSC:</span> <span class="font-mono font-semibold uppercase">{{ \App\Models\SystemSetting::get('bank_ifsc', 'SBIN0001234') }}</span></div>
                        <div><span class="font-bold text-slate-900">Branch:</span> {{ \App\Models\SystemSetting::get('bank_branch', 'Main Branch, Pune') }}</div>
                    </div>
                </div>
            </div>

            <!-- 2. Payment QR Code -->
            <div class="p-5 flex flex-col items-center justify-between text-center">
                <div>
                    <h4 class="font-bold text-slate-900 text-sm mb-1">Payment QR Code</h4>
                    <p class="text-[11px] text-slate-500 mb-2">Scan &amp; Pay (UPI)</p>
                    <div class="inline-block p-2 bg-white border border-slate-200 rounded-lg shadow-2xs mb-2">
                        <svg class="w-24 h-24" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect width="100" height="100" fill="white"/>
                            <rect x="8" y="8" width="28" height="28" stroke="#0f172a" stroke-width="4" fill="none" rx="3"/>
                            <rect x="15" y="15" width="14" height="14" fill="#0f172a" rx="1"/>
                            <rect x="64" y="8" width="28" height="28" stroke="#0f172a" stroke-width="4" fill="none" rx="3"/>
                            <rect x="71" y="15" width="14" height="14" fill="#0f172a" rx="1"/>
                            <rect x="8" y="64" width="28" height="28" stroke="#0f172a" stroke-width="4" fill="none" rx="3"/>
                            <rect x="15" y="71" width="14" height="14" fill="#0f172a" rx="1"/>
                            <rect x="42" y="12" width="6" height="6" fill="#0f172a"/>
                            <rect x="52" y="12" width="6" height="6" fill="#0f172a"/>
                            <rect x="42" y="24" width="6" height="6" fill="#0f172a"/>
                            <rect x="52" y="30" width="6" height="6" fill="#0f172a"/>
                            <rect x="12" y="44" width="6" height="6" fill="#0f172a"/>
                            <rect x="24" y="44" width="6" height="6" fill="#0f172a"/>
                            <rect x="34" y="44" width="6" height="6" fill="#0f172a"/>
                            <rect x="44" y="44" width="12" height="12" fill="#0ea5e9"/>
                            <rect x="62" y="44" width="6" height="6" fill="#0f172a"/>
                            <rect x="74" y="44" width="6" height="6" fill="#0f172a"/>
                            <rect x="84" y="44" width="6" height="6" fill="#0f172a"/>
                            <rect x="42" y="62" width="6" height="6" fill="#0f172a"/>
                            <rect x="52" y="72" width="6" height="6" fill="#0f172a"/>
                            <rect x="42" y="82" width="6" height="6" fill="#0f172a"/>
                            <rect x="62" y="62" width="6" height="6" fill="#0f172a"/>
                            <rect x="74" y="72" width="6" height="6" fill="#0f172a"/>
                            <rect x="84" y="82" width="6" height="6" fill="#0f172a"/>
                        </svg>
                    </div>
                </div>
                <div class="font-mono text-[11px] font-bold text-slate-900 bg-slate-50 px-2 py-1 rounded border border-slate-200 truncate max-w-full">
                    UPI: {{ \App\Models\SystemSetting::get('upi_id', 'enterprises.crm@upi') }}
                </div>
            </div>

            <!-- 3. Terms and Conditions -->
            <div class="p-5 md:col-span-1">
                <h4 class="font-bold text-slate-900 text-sm mb-2">Terms and Conditions</h4>
                <div class="text-[11px] text-slate-600 space-y-1 leading-relaxed">
                    @php
                        $terms = \App\Models\SystemSetting::get('invoice_terms', "1) Goods once sold will not be taken back or exchanged.\n2) Warranty as per manufacturer terms and conditions.\n3) Service charges applicable for out-of-warranty services.\n4) Subject to local jurisdiction.\n5) Payment due immediately or as per agreed terms.");
                        $termsLines = array_filter(array_map('trim', explode("\n", $terms)));
                    @endphp
                    @foreach($termsLines as $line)
                        <div>{{ $line }}</div>
                    @endforeach
                </div>
            </div>

            <!-- 4. Authorised Signatory -->
            <div class="p-5 flex flex-col justify-between items-center text-center">
                <div class="w-full flex justify-center pt-2">
                    <svg class="w-36 h-14" viewBox="0 0 200 80" fill="none" stroke="#0f172a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M 20 55 C 15 25 30 10 50 8 C 68 6 78 22 74 40 C 70 54 52 58 40 57 C 62 55 86 35 96 18 C 104 8 116 8 122 16 C 127 26 124 44 112 55 M 85 28 C 102 20 120 22 136 32 C 146 40 156 42 170 42 M 112 53 C 136 52 174 48 195 45"/>
                    </svg>
                </div>
                <div class="border-t border-slate-300 w-full pt-2 mt-4">
                    <p class="text-[11px] text-slate-600 font-medium">Authorised Signatory For</p>
                    <p class="text-xs font-black text-slate-900 uppercase tracking-wider truncate mt-0.5">
                        {{ \App\Models\SystemSetting::get('company_name', 'ENTERPRISES CRM') }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment History Table (no-print) -->
    <div class="no-print bg-white rounded-2xl border border-slate-200 shadow-2xs p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-slate-900 text-base flex items-center gap-2">
                <i data-lucide="receipt" class="w-5 h-5 text-sky-600"></i>
                <span>Payment Records for this Invoice</span>
            </h3>
            @if($invoice->status !== 'PAID' && $invoice->status !== 'CANCELLED')
                <button onclick="CRM.openModal('modalRecordPayment')" class="btn-primary text-xs py-1.5 px-3 flex items-center gap-1.5">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    <span>Add Payment</span>
                </button>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase">
                    <tr>
                        <th class="p-3">Receipt #</th>
                        <th class="p-3">Payment Date</th>
                        <th class="p-3">Payment Mode</th>
                        <th class="p-3">Reference / Txn ID</th>
                        <th class="p-3 text-right">Amount (₹)</th>
                        <th class="p-3 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($invoice->payments as $p)
                        <tr class="hover:bg-slate-50/50">
                            <td class="p-3 font-mono font-bold text-slate-900">{{ $p->receipt_number }}</td>
                            <td class="p-3 font-mono text-slate-600">{{ \Carbon\Carbon::parse($p->payment_date)->format('d M Y') }}</td>
                            <td class="p-3 font-semibold text-slate-800">{{ $p->payment_mode }}</td>
                            <td class="p-3 font-mono text-slate-500">{{ $p->transaction_reference ?? '—' }}</td>
                            <td class="p-3 text-right font-mono font-bold text-emerald-700">₹{{ number_format($p->amount, 2) }}</td>
                            <td class="p-3 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    {{ $p->status }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-6 text-center text-slate-400">No payments recorded yet for this invoice.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Record Payment -->
<div id="modalRecordPayment" class="modal-backdrop hidden no-print">
    <div class="modal-box max-w-lg">
        <div class="flex items-center justify-between pb-4 border-b border-slate-200">
            <h3 class="font-bold text-slate-900 text-lg flex items-center gap-2">
                <i data-lucide="credit-card" class="w-5 h-5 text-emerald-600"></i>
                <span>Record Invoice Payment</span>
            </h3>
            <button onclick="CRM.closeModal('modalRecordPayment')" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form id="formRecordPayment" action="{{ route('payments.store') }}" method="POST" class="mt-4 space-y-4">
            @csrf
            <input type="hidden" name="invoice_id" value="{{ $invoice->id }}">
            <input type="hidden" name="customer_id" value="{{ $invoice->customer_id }}">

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Invoice Number</label>
                <input type="text" value="{{ $invoice->invoice_number }} (Due: ₹{{ number_format($invoice->outstanding_amount, 2) }})" readonly class="form-input bg-slate-50 text-slate-600 text-xs font-mono">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Payment Amount (₹) <span class="text-rose-500">*</span></label>
                    <input type="number" name="amount" value="{{ $invoice->outstanding_amount }}" max="{{ $invoice->outstanding_amount }}" min="1" step="0.01" required class="form-input text-sm font-mono font-bold">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Payment Date <span class="text-rose-500">*</span></label>
                    <input type="date" name="payment_date" value="{{ date('Y-m-d') }}" required class="form-input text-sm">
                </div>
            </div>

            <!-- Partial Payment: Next Due Date Option -->
            <div id="partialDueDateContainerInvoice" class="hidden p-3 bg-amber-50/90 rounded-xl border border-amber-200/90 space-y-2">
                <div class="flex items-center justify-between text-xs">
                    <span class="font-semibold text-amber-800 flex items-center gap-1.5">
                        <i data-lucide="calendar-clock" class="w-4 h-4 text-amber-600"></i>
                        Partial Payment Detected
                    </span>
                    <span class="font-mono font-bold text-amber-700" id="remainingBalanceDispInvoice">Remaining: ₹0.00</span>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                        Next Due Date for Remaining Amount
                    </label>
                    <input type="date" name="next_due_date" id="paymentNextDueDateInvoice" min="{{ date('Y-m-d') }}" class="form-input text-xs bg-white font-medium">
                    <p class="text-[11px] text-slate-500 mt-1">
                        Schedule the date on which the customer is meant to pay the remaining balance.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Payment Mode <span class="text-rose-500">*</span></label>
                    <select name="payment_mode" required class="form-input text-xs">
                        <option value="UPI">UPI (Google Pay / PhonePe / Paytm)</option>
                        <option value="CASH">Cash</option>
                        <option value="BANK_TRANSFER">Bank Transfer (NEFT/IMPS)</option>
                        <option value="CHEQUE">Cheque</option>
                        <option value="CARD">Debit / Credit Card</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Reference / UTR #</label>
                    <input type="text" name="transaction_reference" placeholder="e.g. UPI Ref / Cheque No." class="form-input text-xs">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Notes</label>
                <textarea name="notes" rows="2" placeholder="Payment remarks..." class="form-input text-xs"></textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                <button type="button" onclick="CRM.closeModal('modalRecordPayment')" class="btn-outline">Cancel</button>
                <button type="submit" class="btn-primary flex items-center gap-2">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span>Confirm Payment</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Cancel Invoice -->
<div id="modalCancelInvoice" class="modal-backdrop hidden no-print">
    <div class="modal-box max-w-md">
        <div class="flex items-center justify-between pb-4 border-b border-slate-200">
            <h3 class="font-bold text-rose-700 text-lg flex items-center gap-2">
                <i data-lucide="alert-triangle" class="w-5 h-5 text-rose-600"></i>
                <span>Cancel Invoice</span>
            </h3>
            <button onclick="CRM.closeModal('modalCancelInvoice')" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form id="formCancelInvoice" action="{{ route('invoices.cancel', $invoice->id) }}" method="POST" class="mt-4 space-y-4">
            @csrf
            <p class="text-xs text-slate-600">
                Are you sure you want to cancel invoice <strong class="font-mono text-slate-900">{{ $invoice->invoice_number }}</strong>? This action will mark it as cancelled and create an audit record.
            </p>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Reason for Cancellation <span class="text-rose-500">*</span></label>
                <textarea name="reason" rows="3" required placeholder="Specify why this invoice is being cancelled..." class="form-input text-xs"></textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200">
                <button type="button" onclick="CRM.closeModal('modalCancelInvoice')" class="btn-outline">Close</button>
                <button type="submit" class="btn-primary bg-rose-600 hover:bg-rose-700 text-white">Cancel Invoice</button>
            </div>
        </form>
    </div>
</div>

<script>
function handleWhatsAppShare() {
    const phone = "{{ $invoice->customer_phone }}";
    if (!phone) {
        CRM.toast('Customer phone number is missing.', 'error');
        return;
    }
    const cleanPhone = phone.replace(/[^0-9]/g, '');
    const fullPhone = cleanPhone.length === 10 ? '91' + cleanPhone : cleanPhone;
    
    const text = encodeURIComponent(
        `Dear {{ $invoice->customer_name }},\n\n` +
        `Here are the details of your Tax Invoice *{{ $invoice->invoice_number }}* from *{{ \App\Models\SystemSetting::get('company_name', 'Enterprises CRM') }}*:\n` +
        `• Total Amount: ₹{{ number_format($invoice->total_amount, 2) }}\n` +
        `• Paid Amount: ₹{{ number_format($invoice->paid_amount, 2) }}\n` +
        `• Balance Due: ₹{{ number_format($invoice->outstanding_amount, 2) }}\n` +
        `• Due Date: {{ \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') }}\n\n` +
        `You can pay via UPI to: {{ \App\Models\SystemSetting::get('upi_id', 'enterprises.crm@upi') }}\n\n` +
        `Thank you for choosing {{ \App\Models\SystemSetting::get('company_name', 'Enterprises CRM') }}!`
    );

    window.open(`https://wa.me/${fullPhone}?text=${text}`, '_blank');
}

function handleSendDueMail() {
    CRM.toast('Sending email reminder...', 'info');
    $.post("{{ route('invoices.due_mail', $invoice->id) }}", { _token: '{{ csrf_token() }}' }, function(res) {
        CRM.toast(res.message || 'Payment reminder sent successfully!');
    }).fail(function(xhr) {
        CRM.toast(xhr.responseJSON?.message || 'Failed to send email reminder.', 'error');
    });
}

$(document).ready(function() {
    const maxBalance = {{ (float) $invoice->outstanding_amount }};
    function checkInvoicePartialPayment() {
        const amount = parseFloat($('#formRecordPayment input[name="amount"]').val()) || 0;
        const remaining = maxBalance - amount;

        if (maxBalance > 0 && amount > 0 && remaining > 0) {
            $('#partialDueDateContainerInvoice').removeClass('hidden');
            $('#remainingBalanceDispInvoice').text('Remaining: ₹' + remaining.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            if (typeof lucide !== 'undefined') lucide.createIcons();
        } else {
            $('#partialDueDateContainerInvoice').addClass('hidden');
        }
    }

    $('#formRecordPayment input[name="amount"]').on('input change', function() {
        checkInvoicePartialPayment();
    });

    CRM.handleAjaxForm('#formRecordPayment', function(res) {
        CRM.toast(res.message || 'Payment recorded successfully!');
        setTimeout(() => location.reload(), 600);
    });

    CRM.handleAjaxForm('#formCancelInvoice', function(res) {
        CRM.toast(res.message || 'Invoice cancelled successfully!');
        setTimeout(() => location.reload(), 600);
    });
});
</script>
@endsection
