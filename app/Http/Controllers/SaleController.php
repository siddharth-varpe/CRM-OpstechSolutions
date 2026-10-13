<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Customer;
use App\Models\Product;
use App\Models\CustomerAsset;
use App\Models\BusinessSequence;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SaleController extends Controller
{
    /**
     * List all sales orders
     */
    public function index(Request $request)
    {
        $status = $request->input('status', 'ALL');
        $search = trim((string) $request->input('search', ''));

        $query = Sale::with(['customer', 'items.product'])->latest();

        if ($status !== 'ALL') {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('sale_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('full_name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        $sales = $query->paginate(10)->withQueryString();

        $customers = Customer::where('status', 'ACTIVE')->get();
        $products = Product::where('is_active', true)->get();

        return view('sales.index', [
            'sales' => $sales,
            'status' => $status,
            'search' => $search,
            'customers' => $customers,
            'products' => $products,
        ]);
    }

    /**
     * Show single sales order
     */
    public function show($id)
    {
        $sale = Sale::with(['customer', 'items.product', 'invoices'])->findOrFail($id);
        return view('sales.show', ['sale' => $sale]);
    }

    /**
     * Store new sales order with GST calculation & automatic invoice creation
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'serial_number' => 'nullable|string|max:100',
            'auto_invoice' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $customer = Customer::findOrFail($validated['customer_id']);
            $product = Product::findOrFail($validated['product_id']);

            $quantity = (int) $validated['quantity'];
            $unitPrice = (float) $validated['unit_price'];
            $discount = (float) ($validated['discount_amount'] ?? 0);

            $subtotal = ($quantity * $unitPrice) - $discount;
            $taxRate = 18.00; // 18% standard GST
            $taxAmount = round(($subtotal * $taxRate) / 100, 2);
            $totalAmount = $subtotal + $taxAmount;

            $saleNumber = BusinessSequence::nextNumber('SALE');

            $sale = Sale::create([
                'sale_number' => $saleNumber,
                'customer_id' => $customer->id,
                'sale_date' => now(),
                'status' => 'COMPLETED',
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            SaleItem::create([
                'sale_id' => $sale->id,
                'product_id' => $product->id,
                'product_name_snapshot' => $product->name,
                'sku_snapshot' => $product->sku,
                'quantity' => $quantity,
                'unit_price_snapshot' => $unitPrice,
                'discount_amount' => $discount,
                'tax_rate_percent' => $taxRate,
                'tax_amount' => $taxAmount,
                'line_total' => $totalAmount,
                'warranty_months' => $product->default_warranty_months,
                'service_interval_months' => $product->default_service_interval_months,
                'serial_number' => $validated['serial_number'] ?? null,
            ]);

            // Auto-mint GST Invoice
            $invoiceNumber = BusinessSequence::nextNumber('INVOICE');
            $cgst = round($taxAmount / 2, 2);
            $sgst = round($taxAmount / 2, 2);

            $invoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'customer_id' => $customer->id,
                'sale_id' => $sale->id,
                'invoice_date' => now(),
                'due_date' => now()->addDays(15),
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'tax_amount' => $taxAmount,
                'cgst_amount' => $cgst,
                'sgst_amount' => $sgst,
                'igst_amount' => 0.00,
                'total_amount' => $totalAmount,
                'paid_amount' => 0.00,
                'balance_amount' => $totalAmount,
                'status' => 'ISSUED',
                'created_by' => auth()->id(),
            ]);

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'product_id' => $product->id,
                'item_type' => 'PRODUCT',
                'name_snapshot' => $product->name,
                'quantity' => $quantity,
                'unit_price_snapshot' => $unitPrice,
                'discount_amount' => $discount,
                'tax_rate_percent' => $taxRate,
                'tax_amount' => $taxAmount,
                'line_total' => $totalAmount,
            ]);

            AuditLog::log('CREATE', 'SALE', $sale->id, null, ['number' => $sale->sale_number, 'amount' => $totalAmount]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Sale {$sale->sale_number} and Invoice {$invoice->invoice_number} created successfully.",
                    'redirect' => route('invoices.show', $invoice->id),
                ]);
            }

            return redirect()->route('invoices.show', $invoice->id)->with('success', 'Sale order and tax invoice generated.');
        });
    }
}
