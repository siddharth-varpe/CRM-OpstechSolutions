<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\InventoryPurchase;
use App\Models\InventorySale;
use App\Models\Customer;
use App\Models\BusinessSequence;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'items'); // items, purchases, sales

        $itemsQuery = InventoryItem::query();
        if ($request->filled('search')) {
            $search = trim($request->search);
            $itemsQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('part_number', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%")
                  ->orWhere('brand', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category') && $request->category !== 'ALL') {
            $itemsQuery->where('category', $request->category);
        }

        $items = $itemsQuery->orderBy('name')->paginate(15, ['*'], 'items_page')->withQueryString();

        $purchases = InventoryPurchase::with('item')->latest('purchase_date')->paginate(15, ['*'], 'purchases_page');
        $sales = InventorySale::with(['item', 'customer'])->latest('sale_date')->paginate(15, ['*'], 'sales_page');

        // Statistics
        $allItems = InventoryItem::all();
        $totalItemsCount = $allItems->count();
        $totalStockValue = $allItems->sum(function ($it) {
            return $it->current_stock * $it->purchase_price;
        });
        $lowStockCount = $allItems->filter(function ($it) {
            return $it->isLowStock();
        })->count();
        $totalPurchasesValue = InventoryPurchase::sum('total_amount');

        $customers = Customer::where('status', 'ACTIVE')->orderBy('full_name')->get();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'items' => $items,
                'stats' => [
                    'totalItems' => $totalItemsCount,
                    'stockValue' => $totalStockValue,
                    'lowStockCount' => $lowStockCount,
                    'purchasesValue' => $totalPurchasesValue,
                ],
            ]);
        }

        return view('inventory.index', compact(
            'items',
            'purchases',
            'sales',
            'tab',
            'totalItemsCount',
            'totalStockValue',
            'lowStockCount',
            'totalPurchasesValue',
            'customers'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'brand' => 'nullable|string|max:100',
            'part_number' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'current_stock' => 'required|integer|min:0',
            'min_stock_level' => 'required|integer|min:0',
        ]);

        $item = InventoryItem::create([
            'id' => (string) Str::uuid(),
            'name' => $validated['name'],
            'category' => $validated['category'],
            'brand' => $validated['brand'] ?? null,
            'part_number' => $validated['part_number'] ?? null,
            'description' => $validated['description'] ?? null,
            'purchase_price' => $validated['purchase_price'],
            'selling_price' => $validated['selling_price'],
            'current_stock' => $validated['current_stock'],
            'min_stock_level' => $validated['min_stock_level'],
            'status' => 'ACTIVE',
        ]);

        AuditLog::create([
            'id' => (string) Str::uuid(),
            'entity_type' => 'INVENTORY_ITEM',
            'entity_id' => $item->id,
            'action' => 'CREATE',
            'user_id' => auth()->id() ?? 'system',
            'performed_by' => auth()->user()->name ?? 'Staff',
            'changes' => json_encode(['name' => $item->name, 'stock' => $item->current_stock]),
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Item '{$item->name}' added to inventory.",
                'data' => $item,
            ]);
        }

        return redirect()->back()->with('success', "Item '{$item->name}' added to inventory.");
    }

    public function recordPurchase(Request $request)
    {
        $validated = $request->validate([
            'item_id' => 'required|exists:inventory_items,id',
            'supplier_name' => 'required|string|max:255',
            'purchase_date' => 'required|date',
            'quantity' => 'required|integer|min:1',
            'purchase_price_per_unit' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $item = InventoryItem::findOrFail($validated['item_id']);
            $qty = (int) $validated['quantity'];
            $price = (float) $validated['purchase_price_per_unit'];
            $total = $qty * $price;

            $purchaseNumber = 'PUR-' . date('Y') . '-' . str_pad((string)(InventoryPurchase::count() + 1), 4, '0', STR_PAD_LEFT);

            $purchase = InventoryPurchase::create([
                'id' => (string) Str::uuid(),
                'purchase_number' => $purchaseNumber,
                'item_id' => $item->id,
                'supplier_name' => $validated['supplier_name'],
                'purchase_date' => $validated['purchase_date'],
                'quantity' => $qty,
                'remaining_quantity' => $qty,
                'purchase_price_per_unit' => $price,
                'total_amount' => $total,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Update item current stock and average purchase price
            $item->increment('current_stock', $qty);
            $item->update(['purchase_price' => $price]);

            AuditLog::create([
                'id' => (string) Str::uuid(),
                'entity_type' => 'INVENTORY_PURCHASE',
                'entity_id' => $purchase->id,
                'action' => 'CREATE',
                'user_id' => auth()->id() ?? 'system',
                'performed_by' => auth()->user()->name ?? 'Staff',
                'changes' => json_encode(['item' => $item->name, 'quantity' => $qty, 'total' => $total]),
            ]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Purchase of {$qty} units recorded for {$item->name}.",
                    'data' => $purchase,
                ]);
            }

            return redirect()->back()->with('success', "Stock purchase recorded successfully.");
        });
    }

    public function recordSale(Request $request)
    {
        $validated = $request->validate([
            'item_id' => 'required|exists:inventory_items,id',
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'nullable|string|max:255',
            'sale_date' => 'required|date',
            'quantity' => 'required|integer|min:1',
            'selling_price_per_unit' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $item = InventoryItem::findOrFail($validated['item_id']);
            $qty = (int) $validated['quantity'];

            if ($qty > $item->current_stock) {
                return response()->json([
                    'success' => false,
                    'message' => "Insufficient stock. Available: {$item->current_stock}, Requested: {$qty}.",
                ], 422);
            }

            $sellPrice = (float) $validated['selling_price_per_unit'];
            $costPrice = (float) $item->purchase_price;

            $totalSale = $qty * $sellPrice;
            $totalCost = $qty * $costPrice;
            $profit = $totalSale - $totalCost;

            $saleNumber = 'DISP-' . date('Y') . '-' . str_pad((string)(InventorySale::count() + 1), 4, '0', STR_PAD_LEFT);

            $custName = $validated['customer_name'] ?? null;
            if (!$custName && !empty($validated['customer_id'])) {
                $cust = Customer::find($validated['customer_id']);
                $custName = $cust ? $cust->name : 'Walk-in';
            }

            $sale = InventorySale::create([
                'id' => (string) Str::uuid(),
                'sale_number' => $saleNumber,
                'item_id' => $item->id,
                'customer_id' => $validated['customer_id'] ?? null,
                'customer_name' => $custName ?? 'Direct Sale',
                'customer_phone' => null,
                'sale_date' => $validated['sale_date'],
                'quantity' => $qty,
                'selling_price_per_unit' => $sellPrice,
                'purchase_cost_per_unit' => $costPrice,
                'total_sale_amount' => $totalSale,
                'total_cost_amount' => $totalCost,
                'profit' => $profit,
                'payment_status' => 'PAID',
                'notes' => $validated['notes'] ?? null,
            ]);

            $item->decrement('current_stock', $qty);

            AuditLog::create([
                'id' => (string) Str::uuid(),
                'entity_type' => 'INVENTORY_SALE',
                'entity_id' => $sale->id,
                'action' => 'CREATE',
                'user_id' => auth()->id() ?? 'system',
                'performed_by' => auth()->user()->name ?? 'Staff',
                'changes' => json_encode(['item' => $item->name, 'quantity' => $qty, 'total' => $totalSale]),
            ]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Sale of {$qty} units dispatched. Stock updated.",
                    'data' => $sale,
                ]);
            }

            return redirect()->back()->with('success', "Stock dispatch recorded successfully.");
        });
    }
}
