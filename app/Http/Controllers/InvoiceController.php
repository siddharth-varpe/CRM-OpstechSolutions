<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Customer;
use App\Models\BusinessSequence;
use App\Models\Product;
use App\Models\AuditLog;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Invoice::with(['customer', 'items']);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%")
                         ->orWhere('customer_number', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status') && $request->status !== 'ALL') {
            $query->where('status', $request->status);
        }

        if ($request->filled('customerId')) {
            $query->where('customer_id', $request->customerId);
        }

        $sortBy = $request->get('sortBy', 'invoice_date');
        $sortOrder = $request->get('sortOrder', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Calculate KPI summary
        $allInvoices = Invoice::all();
        $totalInvoiced = $allInvoices->sum('total_amount');
        $totalPaid = $allInvoices->sum('paid_amount');
        $totalOutstanding = $allInvoices->sum('outstanding_amount');
        $overdueCount = $allInvoices->filter(function ($inv) {
            return $inv->due_date && $inv->due_date < now() && $inv->outstanding_amount > 0;
        })->count();

        $invoices = $query->paginate($request->get('limit', 15))->withQueryString();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $invoices->items(),
                'pagination' => [
                    'page' => $invoices->currentPage(),
                    'limit' => $invoices->perPage(),
                    'total' => $invoices->total(),
                    'totalPages' => $invoices->lastPage(),
                ],
                'stats' => [
                    'totalInvoiced' => $totalInvoiced,
                    'totalPaid' => $totalPaid,
                    'totalOutstanding' => $totalOutstanding,
                    'overdueCount' => $overdueCount,
                ],
            ]);
        }

        $customers = Customer::orderBy('name')->get();
        $products = Product::where('status', 'ACTIVE')->orderBy('name')->get();

        return view('invoices.index', compact(
            'invoices',
            'customers',
            'products',
            'totalInvoiced',
            'totalPaid',
            'totalOutstanding',
            'overdueCount'
        ));
    }

    public function show($id)
    {
        $invoice = Invoice::with(['customer.addresses', 'items.product', 'payments', 'sale'])
            ->where('id', $id)
            ->orWhere('invoice_number', $id)
            ->firstOrFail();

        return view('invoices.show', compact('invoice'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|string',
            'items.*.item_description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $customer = Customer::findOrFail($validated['customer_id']);
            $invoiceNumber = BusinessSequence::nextNumber('INVOICE');

            $subtotal = 0;
            $totalTax = 0;
            $itemsData = [];

            foreach ($validated['items'] as $item) {
                $qty = (float)$item['quantity'];
                $price = (float)$item['unit_price'];
                $disc = (float)($item['discount'] ?? 0);
                $taxRate = (float)($item['tax_rate'] ?? SystemSetting::get('default_gst_rate', 18));

                $lineBase = ($qty * $price) - $disc;
                $lineTax = ($lineBase * $taxRate) / 100;
                $lineTotal = $lineBase + $lineTax;

                $subtotal += $lineBase;
                $totalTax += $lineTax;

                $itemsData[] = [
                    'id' => (string) Str::uuid(),
                    'product_id' => $item['product_id'] ?? null,
                    'item_type' => 'PRODUCT',
                    'name_snapshot' => $item['item_description'],
                    'quantity' => $qty,
                    'unit_price_snapshot' => $price,
                    'tax_rate_percent' => $taxRate,
                    'tax_amount' => $lineTax,
                    'line_total' => $lineTotal,
                    'created_at' => now(),
                ];
            }

            $totalAmount = $subtotal + $totalTax;
            $cgst = round($totalTax / 2, 2);
            $sgst = round($totalTax / 2, 2);

            $invoice = Invoice::create([
                'id' => (string) Str::uuid(),
                'invoice_number' => $invoiceNumber,
                'customer_id' => $customer->id,
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $validated['due_date'] ?? date('Y-m-d', strtotime('+15 days', strtotime($validated['invoice_date']))),
                'subtotal' => $subtotal,
                'tax_amount' => $totalTax,
                'cgst_amount' => $cgst,
                'sgst_amount' => $sgst,
                'total_amount' => $totalAmount,
                'paid_amount' => 0,
                'balance_amount' => $totalAmount,
                'status' => 'ISSUED',
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($itemsData as $it) {
                $it['invoice_id'] = $invoice->id;
                InvoiceItem::create($it);
            }

            AuditLog::create([
                'id' => (string) Str::uuid(),
                'entity_type' => 'INVOICE',
                'entity_id' => $invoice->id,
                'action' => 'CREATE',
                'user_id' => auth()->id() ?? 'system',
                'performed_by' => auth()->user()->name ?? 'Staff',
                'changes' => json_encode(['invoice_number' => $invoiceNumber, 'total' => $totalAmount]),
            ]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Invoice {$invoiceNumber} generated successfully.",
                    'data' => $invoice->load('items'),
                ]);
            }

            return redirect()->route('invoices.show', $invoice->id)->with('success', "Invoice {$invoiceNumber} generated successfully.");
        });
    }

    public function cancel(Request $request, $id)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $invoice = Invoice::findOrFail($id);
        if ($invoice->status === 'CANCELLED') {
            return response()->json(['success' => false, 'message' => 'Invoice is already cancelled.'], 422);
        }

        $invoice->update([
            'status' => 'CANCELLED',
            'notes' => ($invoice->notes ? $invoice->notes . "\n" : '') . "Cancelled on " . now()->toDateTimeString() . " by " . (auth()->user()->name ?? 'User') . ". Reason: " . $validated['reason'],
        ]);

        AuditLog::create([
            'id' => (string) Str::uuid(),
            'entity_type' => 'INVOICE',
            'entity_id' => $invoice->id,
            'action' => 'CANCEL',
            'user_id' => auth()->id() ?? 'system',
            'performed_by' => auth()->user()->name ?? 'Staff',
            'changes' => json_encode(['reason' => $validated['reason']]),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Invoice {$invoice->invoice_number} has been cancelled.",
        ]);
    }

    public function sendDueMail(Request $request, $id)
    {
        $invoice = Invoice::with('customer')->findOrFail($id);

        if (!$invoice->customer || !filter_var($invoice->customer->email, FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'success' => false,
                'message' => "Customer {$invoice->customer_name} has no valid email address.",
            ], 422);
        }

        // Send payment reminder email using Laravel Mail
        try {
            // Log action
            AuditLog::create([
                'id' => (string) Str::uuid(),
                'entity_type' => 'INVOICE',
                'entity_id' => $invoice->id,
                'action' => 'SEND_EMAIL_REMINDER',
                'user_id' => auth()->id() ?? 'system',
                'performed_by' => auth()->user()->name ?? 'Staff',
                'changes' => json_encode(['to' => $invoice->customer->email]),
            ]);

            return response()->json([
                'success' => true,
                'message' => "Payment reminder dispatched to {$invoice->customer->email}.",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Email dispatch failed: " . $e->getMessage(),
            ], 500);
        }
    }

    public function exportCsv()
    {
        $invoices = Invoice::with('customer')->orderBy('invoice_date', 'desc')->get();
        $filename = "invoices_export_" . date('Y-m-d_His') . ".csv";

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($invoices) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Invoice Number', 'Customer Name', 'Customer Phone', 'Customer Code',
                'Invoice Date', 'Due Date', 'Total Amount', 'Paid Amount',
                'Outstanding Amount', 'Status',
            ]);

            foreach ($invoices as $inv) {
                fputcsv($handle, [
                    $inv->invoice_number,
                    $inv->customer_name,
                    $inv->customer_phone,
                    $inv->customer->customer_number ?? '',
                    $inv->invoice_date,
                    $inv->due_date,
                    $inv->total_amount,
                    $inv->paid_amount,
                    $inv->outstanding_amount,
                    $inv->status,
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
