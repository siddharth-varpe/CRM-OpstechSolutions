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

        $rentalPayments = RentalPayment::with(['rental.customer'])->latest('payment_date')->paginate(15, ['*'], 'rental_page');

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
                ],
            ]);
        }

        return view('payments.index', compact(
            'payments',
            'rentalPayments',
            'tab',
            'totalCollected',
            'thisMonthCollected',
            'todayCollected',
            'pendingInvoicesCount',
            'pendingInvoices'
        ));
    }

    public function store(Request $request, $id = null)
    {
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
            $invoice = Invoice::findOrFail($validated['invoice_id']);
            $amount = (float) $validated['amount'];

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
            if ($newBalance > 0 && !empty($validated['next_due_date'])) {
                $invoiceUpdate['due_date'] = $validated['next_due_date'];
            }

            $invoice->update($invoiceUpdate);

            $notes = $validated['notes'] ?? '';
            if ($newBalance > 0 && !empty($validated['next_due_date'])) {
                $formattedDueDate = Carbon::parse($validated['next_due_date'])->format('d M Y');
                $dueNote = "Next due date for remaining balance (₹" . number_format($newBalance, 2) . "): {$formattedDueDate}";
                $notes = !empty($notes) ? "{$notes} | {$dueNote}" : $dueNote;
            }

            $payment = Payment::create([
                'id' => (string) Str::uuid(),
                'payment_number' => $receiptNumber,
                'customer_id' => $invoice->customer_id,
                'invoice_id' => $invoice->id,
                'amount' => $amount,
                'payment_date' => $validated['payment_date'],
                'payment_method' => $validated['payment_mode'],
                'status' => 'COMPLETED',
                'reference_number' => $validated['transaction_reference'] ?? null,
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
                    'next_due_date' => $newBalance > 0 ? ($validated['next_due_date'] ?? null) : null,
                    'invoice' => $invoice->invoice_number,
                ]),
            ]);

            $successMessage = "Payment of ₹" . number_format($amount, 2) . " recorded. Receipt # {$receiptNumber}";
            if ($newBalance > 0 && !empty($validated['next_due_date'])) {
                $formattedDueDate = Carbon::parse($validated['next_due_date'])->format('d M Y');
                $successMessage .= ". Next due date for ₹" . number_format($newBalance, 2) . " set to {$formattedDueDate}.";
            }

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $successMessage,
                    'data' => $payment,
                ]);
            }

            return redirect()->back()->with('success', $successMessage);
        });
    }

    public function show($id)
    {
        $payment = Payment::with(['customer.addresses', 'invoice'])->findOrFail($id);
        return response()->json([
            'success' => true,
            'data' => $payment,
        ]);
    }

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
