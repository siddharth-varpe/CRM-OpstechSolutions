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
use App\Models\SystemSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SaleController extends Controller
{
    // ---------------------- 1. View / List Details ---------------------
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

        $customers = Customer::where('status', 'ACTIVE')->orderBy('full_name')->get();
        $products = Product::where('is_active', true)->get();
        $catalogProducts = $products->map(function ($p) {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'price' => (float) $p->unit_price,
                'sku' => $p->sku,
            ];
        })->values();

        return view('sales.index', [
            'sales' => $sales,
            'status' => $status,
            'search' => $search,
            'customers' => $customers,
            'products' => $products,
            'catalogProducts' => $catalogProducts,
        ]);
    }

    // ---------------------- 2. View Single / Show Details ---------------------
    /**
     * Show single sales order
     */
    public function show($id)
    {
        $sale = Sale::with(['customer', 'items.product', 'invoices'])->findOrFail($id);
        return view('sales.show', ['sale' => $sale]);
    }

    // ---------------------- 3. Insert Action ---------------------
    /**
     * Store new sales order with GST calculation & automatic invoice creation.
     * Supports both catalogue product selection and custom free-text product names.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'product_id' => 'nullable',
            'product_name' => 'nullable|string|max:255',
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'serial_number' => 'nullable|string|max:100',
            'apply_gst' => 'nullable',
            'tax_rate' => 'nullable',
            'custom_tax_rate' => 'nullable|numeric|min:0|max:100',
            'auto_invoice' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        $productName = trim((string) ($request->input('product_name') ?? ''));
        $productId = $request->input('product_id');

        if (empty($productName) && empty($productId)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'product_name' => 'Please provide a product name.',
            ]);
        }

        return DB::transaction(function () use ($validated, $request, $productName, $productId) {
            $customer = Customer::findOrFail($validated['customer_id']);

            $product = null;
            if (!empty($productId)) {
                $product = Product::find($productId);
            }

            if (!$product && !empty($productName)) {
                $product = Product::where('name', $productName)->first();
            }

            $effectiveProductName = !empty($productName) ? $productName : ($product ? $product->name : 'Custom Product');
            $quantity = (int) $validated['quantity'];
            $unitPrice = (float) $validated['unit_price'];
            $discount = (float) ($validated['discount_amount'] ?? 0);

            // Determine optional GST rate (0% by default)
            $applyGst = false;
            if ($request->has('apply_gst')) {
                $raw = $request->input('apply_gst');
                if ($raw === '1' || $raw === 1 || $raw === true || $raw === 'true' || $raw === 'on' || $raw === 'yes') {
                    $applyGst = true;
                }
            } elseif ($request->filled('tax_rate') && (float) $request->input('tax_rate') > 0) {
                $applyGst = true;
            }

            if (!$applyGst) {
                $taxRate = 0.00;
            } else {
                if ($request->filled('tax_rate')) {
                    $taxRateInput = $request->input('tax_rate');
                    if ($taxRateInput === 'custom' && $request->filled('custom_tax_rate')) {
                        $taxRate = (float) $request->input('custom_tax_rate');
                    } elseif (is_numeric($taxRateInput)) {
                        $taxRate = (float) $taxRateInput;
                    } else {
                        $taxRate = (float) SystemSetting::get('default_gst_rate', 0.00);
                    }
                } elseif ($request->filled('custom_tax_rate')) {
                    $taxRate = (float) $request->input('custom_tax_rate');
                } else {
                    $taxRate = (float) SystemSetting::get('default_gst_rate', 0.00);
                }
            }

            $taxRate = max(0.00, round($taxRate, 2));

            // Auto-create product if it doesn't exist in master catalogue
            if (!$product) {
                $cleanSlug = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', substr($effectiveProductName, 0, 8)));
                $skuBase = !empty($cleanSlug) ? 'PRD-' . $cleanSlug : 'PRD-' . strtoupper(Str::random(6));
                $sku = $skuBase;
                $counter = 1;
                while (Product::where('sku', $sku)->exists()) {
                    $sku = $skuBase . '-' . $counter++;
                }

                $product = Product::create([
                    'sku' => $sku,
                    'name' => $effectiveProductName,
                    'product_type' => 'RO_MACHINE',
                    'brand' => 'Enterprises CRM',
                    'model' => 'Custom',
                    'unit_price' => $unitPrice,
                    'tax_rate_percent' => $taxRate > 0 ? $taxRate : 0.00,
                    'default_warranty_months' => 12,
                    'default_service_interval_months' => 6,
                    'is_active' => true,
                ]);
            }

            $subtotal = ($quantity * $unitPrice) - $discount;
            $taxAmount = $taxRate > 0 ? round(($subtotal * $taxRate) / 100, 2) : 0.00;
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
                'product_name_snapshot' => $effectiveProductName,
                'sku_snapshot' => $product->sku,
                'quantity' => $quantity,
                'unit_price_snapshot' => $unitPrice,
                'discount_amount' => $discount,
                'tax_rate_percent' => $taxRate,
                'tax_amount' => $taxAmount,
                'line_total' => $totalAmount,
                'warranty_months' => $product->default_warranty_months ?? 12,
                'service_interval_months' => $product->default_service_interval_months ?? 6,
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
                'name_snapshot' => $effectiveProductName,
                'quantity' => $quantity,
                'unit_price_snapshot' => $unitPrice,
                'discount_amount' => $discount,
                'tax_rate_percent' => $taxRate,
                'tax_amount' => $taxAmount,
                'line_total' => $totalAmount,
            ]);

            AuditLog::log('CREATE', 'SALE', $sale->id, null, [
                'number' => $sale->sale_number, 
                'amount' => $totalAmount,
                'product' => $effectiveProductName
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Sale {$sale->sale_number} and Invoice {$invoice->invoice_number} created successfully.",
                    'redirect' => route('invoices.show', $invoice->id),
                ]);
            }

            return redirect()->route('invoices.show', $invoice->id)
                ->with('success', 'Sale order and tax invoice generated.')
                ->with('msgb', 'Sale order and tax invoice generated.');
        });
    }
}
