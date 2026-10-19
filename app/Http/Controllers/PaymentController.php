<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\RentalPayment;
use App\Models\BusinessSequence;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PaymentController extends Controller
{
    // ---------------------- 1. View / List Details ---------------------
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'invoices'); // invoices or rentals

        $query = Payment::with(['customer', 'invoice'])->latest('payment_date');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('payment_number', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('full_name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  })
                  ->orWhereHas('invoice', function ($iq) use ($search) {
                      $iq->where('invoice_number', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('method') && $request->method !== 'ALL') {
            $query->where('payment_method', $request->method);
        }

        $payments = $query->paginate(15)->withQueryString();

        // Calculate KPI totals
        $totalCollected = Payment::where('status', 'COMPLETED')->sum('amount');
        $thisMonthCollected = Payment::where('status', 'COMPLETED')
            ->whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->sum('amount');
        $todayCollected = Payment::where('status', 'COMPLETED')
            ->whereDate('payment_date', now()->today())
            ->sum('amount');
        $pendingInvoicesCount = Invoice::whereIn('status', ['ISSUED', 'PARTIALLY_PAID', 'OVERDUE'])->count();

        // Pending invoices for modal selector
        $pendingInvoices = Invoice::where('balance_amount', '>', 0)
            ->where('status', '!=', 'CANCELLED')
            ->with('customer')
            ->orderBy('invoice_date', 'desc')
            ->get();

        // Rental payments query with filtering and safe order by paid_date / created_at
        $rentalQuery = RentalPayment::with(['rental.customer', 'rental.asset.product'])->latest('paid_date');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $rentalQuery->where(function ($q) use ($search) {
                $q->where('payment_number', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%")
                  ->orWhere('billing_month', 'like', "%{$search}%")
                  ->orWhereHas('rental', function ($rq) use ($search) {
                      $rq->where('rental_number', 'like', "%{$search}%")
                         ->orWhere('product_name', 'like', "%{$search}%")
                         ->orWhereHas('customer', function ($cq) use ($search) {
                             $cq->where('full_name', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                         });
                  });
            });
        }

        if ($request->filled('method') && $request->method !== 'ALL') {
            $rentalQuery->where('payment_method', $request->method);
        }

        $rentalPayments = $rentalQuery->paginate(15, ['*'], 'rental_page')->withQueryString();

        // Active rentals for modal selector
        $activeRentals = \App\Models\Rental::where('status', 'ACTIVE')
            ->with(['customer', 'asset.product'])
            ->orderBy('rental_number', 'desc')
            ->get();

        $totalRentalCollected = RentalPayment::where('status', 'PAID')->sum('amount');

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $payments->items(),
                'pagination' => [
                    'page' => $payments->currentPage(),
                    'total' => $payments->total(),
                ],
                'stats' => [
                    'totalCollected' => $totalCollected,
                    'thisMonthCollected' => $thisMonthCollected,
                    'todayCollected' => $todayCollected,
                    'pendingInvoicesCount' => $pendingInvoicesCount,
                    'totalRentalCollected' => $totalRentalCollected,
                ],
            ]);
        }

        return view('payments.index', compact(
            'payments',
            'rentalPayments',
            'activeRentals',
            'tab',
            'totalCollected',
            'thisMonthCollected',
            'todayCollected',
            'pendingInvoicesCount',
            'pendingInvoices',
            'totalRentalCollected'
        ));
    }

    // ---------------------- 2. Insert Action ---------------------
    public function store(Request $request, $id = null)
    {
        if ($request->has('rental_id')) {
            return $this->storeRentalPayment($request);
        }

        if ($id && !$request->has('invoice_id')) {
            $request->merge(['invoice_id' => $id]);
        }

        $validated = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'amount' => 'required|numeric|min:1',
            'payment_date' => 'required|date',
            'payment_mode' => 'required|string',
            'next_due_date' => 'nullable|date',
            'transaction_reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            // Explicit input extraction (Senior Blueprint)
            $invId          = $validated['invoice_id'];
            $amount         = (float) $validated['amount'];
            $paymentDate    = $validated['payment_date'];
            $paymentMode    = $validated['payment_mode'];
            $nextDueDate    = $validated['next_due_date'] ?? null;
            $transactionRef = $validated['transaction_reference'] ?? null;
            $notesInput     = $validated['notes'] ?? '';

            $invoice = Invoice::findOrFail($invId);

            if ($amount > $invoice->balance_amount) {
                return response()->json([
                    'success' => false,
                    'message' => "Payment amount (₹{$amount}) cannot exceed current invoice balance (₹{$invoice->balance_amount}).",
                ], 422);
            }

            $receiptNumber = BusinessSequence::nextNumber('PAYMENT');

            $newPaid = $invoice->paid_amount + $amount;
            $newBalance = max(0, $invoice->total_amount - $newPaid);
            $newStatus = $newBalance <= 0 ? 'PAID' : 'PARTIALLY_PAID';

            $invoiceUpdate = [
                'paid_amount' => $newPaid,
                'balance_amount' => $newBalance,
                'status' => $newStatus,
            ];

            // If customer makes a partial payment and specifies next due date, update invoice due_date
            if ($newBalance > 0 && !empty($nextDueDate)) {
                $invoiceUpdate['due_date'] = $nextDueDate;
            }

            $invoice->update($invoiceUpdate);

            $notes = $notesInput;
            if ($newBalance > 0 && !empty($nextDueDate)) {
                $formattedDueDate = Carbon::parse($nextDueDate)->format('d M Y');
                $dueNote = "Next due date for remaining balance (₹" . number_format($newBalance, 2) . "): {$formattedDueDate}";
                $notes = !empty($notes) ? "{$notes} | {$dueNote}" : $dueNote;
            }

            $payment = Payment::create([
                'id' => (string) Str::uuid(),
                'payment_number' => $receiptNumber,
                'customer_id' => $invoice->customer_id,
                'invoice_id' => $invoice->id,
                'amount' => $amount,
                'payment_date' => $paymentDate,
                'payment_method' => $paymentMode,
                'status' => 'COMPLETED',
                'reference_number' => $transactionRef,
                'notes' => $notes ?: null,
                'created_by' => auth()->id(),
            ]);

            AuditLog::create([
                'id' => (string) Str::uuid(),
                'entity_type' => 'PAYMENT',
                'entity_id' => $payment->id,
                'action' => 'CREATE',
                'user_id' => auth()->id() ?? 'system',
                'performed_by' => auth()->user()->name ?? 'Staff',
                'changes' => json_encode([
                    'receipt_number' => $receiptNumber, 
                    'amount' => $amount, 
                    'remaining_balance' => $newBalance,
                    'next_due_date' => $newBalance > 0 ? $nextDueDate : null,
                    'invoice' => $invoice->invoice_number,
                ]),
            ]);

            $successMessage = "Payment of ₹" . number_format($amount, 2) . " recorded. Receipt # {$receiptNumber}";
            if ($newBalance > 0 && !empty($nextDueDate)) {
                $formattedDueDate = Carbon::parse($nextDueDate)->format('d M Y');
                $successMessage .= ". Next due date for ₹" . number_format($newBalance, 2) . " set to {$formattedDueDate}.";
            }

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $successMessage,
                    'data' => $payment,
                ]);
            }

            return redirect()->back()
                ->with('success', $successMessage)
                ->with('msgb', $successMessage);
        });
    }

    // ---------------------- 2b. Insert Rental Payment Action ---------------------
    public function storeRentalPayment(Request $request)
    {
        $validated = $request->validate([
            'rental_id' => 'required|exists:rentals,id',
            'amount' => 'required|numeric|min:1',
            'billing_month' => 'required|string',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
            'transaction_reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $rental = \App\Models\Rental::with('customer')->findOrFail($validated['rental_id']);
        $paymentNumber = BusinessSequence::nextNumber('RENTAL_PAYMENT', 'RPAY');
        $paidDate = Carbon::parse($validated['payment_date']);
        $billingMonth = $validated['billing_month'];

        $billingDay = $rental->billing_day ?: 1;
        try {
            $monthStart = Carbon::parse($billingMonth . '-01');
            $dueDay = min($billingDay, $monthStart->daysInMonth);
            $dueDate = Carbon::create($monthStart->year, $monthStart->month, $dueDay);
        } catch (\Exception $e) {
            $dueDate = $paidDate;
        }

        $rentalPayment = RentalPayment::create([
            'id' => (string) Str::uuid(),
            'payment_number' => $paymentNumber,
            'rental_id' => $rental->id,
            'billing_month' => $billingMonth,
            'amount' => $validated['amount'],
            'due_date' => $dueDate,
            'paid_date' => $paidDate,
            'payment_method' => $validated['payment_method'],
            'reference_number' => $validated['transaction_reference'] ?? null,
            'status' => 'PAID',
            'notes' => $validated['notes'] ?? null,
        ]);

        AuditLog::create([
            'id' => (string) Str::uuid(),
            'entity_type' => 'RENTAL_PAYMENT',
            'entity_id' => $rentalPayment->id,
            'action' => 'CREATE',
            'user_id' => auth()->id() ?? 'system',
            'performed_by' => auth()->user()->name ?? 'Staff',
            'changes' => json_encode([
                'payment_number' => $paymentNumber,
                'rental_number' => $rental->rental_number,
                'customer' => $rental->customer->full_name ?? 'N/A',
                'amount' => $rentalPayment->amount,
                'billing_month' => $billingMonth,
            ]),
        ]);

        $msg = "Rental payment {$paymentNumber} of ₹" . number_format($rentalPayment->amount, 2) . " recorded successfully.";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'data' => $rentalPayment,
            ]);
        }

        return redirect()->route('payments.index', ['tab' => 'rentals'])
            ->with('success', $msg)
            ->with('msgb', $msg);
    }

    // ---------------------- 3. View Single / Show Details ---------------------
    public function show($id)
    {
        $payment = Payment::with(['customer.addresses', 'invoice'])->find($id);
        if (!$payment) {
            $rentalPayment = RentalPayment::with(['rental.customer.addresses', 'rental.asset.product'])->findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $rentalPayment->id,
                    'payment_number' => $rentalPayment->payment_number ?? ('RPAY-' . substr($rentalPayment->id, 0, 8)),
                    'payment_date' => $rentalPayment->paid_date ? $rentalPayment->paid_date->toIso8601String() : $rentalPayment->created_at->toIso8601String(),
                    'amount' => $rentalPayment->amount,
                    'payment_method' => $rentalPayment->payment_method ?? 'CASH',
                    'reference_number' => $rentalPayment->reference_number ?? '—',
                    'status' => $rentalPayment->status,
                    'customer' => $rentalPayment->rental->customer ?? null,
                    'invoice' => [
                        'invoice_number' => 'Rental: ' . ($rentalPayment->rental->rental_number ?? 'Agreement') . ' (' . $rentalPayment->billing_month . ')',
                    ],
                    'notes' => $rentalPayment->notes,
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $payment,
        ]);
    }

    // ---------------------- 4. Cancel / Void Action ---------------------
    public function cancel(Request $request, $id)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        return DB::transaction(function () use ($id, $validated) {
            $payment = Payment::with('invoice')->findOrFail($id);

            if ($payment->status === 'CANCELLED') {
                return response()->json(['success' => false, 'message' => 'Payment is already cancelled.'], 422);
            }

            $payment->update([
                'status' => 'CANCELLED',
                'notes' => ($payment->notes ? $payment->notes . "\n" : '') . "Cancelled on " . now()->toDateTimeString() . ". Reason: " . $validated['reason'],
            ]);

            if ($payment->invoice) {
                $invoice = $payment->invoice;
                $newPaid = max(0, $invoice->paid_amount - $payment->amount);
                $newBalance = max(0, $invoice->total_amount - $newPaid);
                $newStatus = $newPaid <= 0 ? 'ISSUED' : 'PARTIALLY_PAID';

                $invoice->update([
                    'paid_amount' => $newPaid,
                    'balance_amount' => $newBalance,
                    'status' => $newStatus,
                ]);
            }

            AuditLog::create([
                'id' => (string) Str::uuid(),
                'entity_type' => 'PAYMENT',
                'entity_id' => $payment->id,
                'action' => 'CANCEL',
                'user_id' => auth()->id() ?? 'system',
                'performed_by' => auth()->user()->name ?? 'Staff',
                'changes' => json_encode(['reason' => $validated['reason']]),
            ]);

            return response()->json([
                'success' => true,
                'message' => "Payment {$payment->payment_number} cancelled and invoice balance adjusted.",
            ]);
        });
    }
}
