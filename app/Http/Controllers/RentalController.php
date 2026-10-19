<?php

namespace App\Http\Controllers;

use App\Models\Rental;
use App\Models\RentalPayment;
use App\Models\Customer;
use App\Models\CustomerAsset;
use App\Models\BusinessSequence;
use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RentalController extends Controller
{
    /* 1. View / List Details */
    public function index(Request $request)
    {
        $search = $request->filled('search') ? trim((string) $request->input('search')) : null;
        $status = $request->filled('status') ? trim((string) $request->input('status')) : null;

        $query = Rental::with(['customer', 'asset.product', 'payments']);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('rental_number', 'like', "%{$search}%")
                  ->orWhere('product_name', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('full_name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        if (!empty($status) && $status !== 'ALL') {
            $query->where('status', $status);
        }

        $rentals = $query->latest('start_date')->paginate(15)->withQueryString();

        $allRentals = Rental::all();
        $activeRentalsCount = $allRentals->where('status', 'ACTIVE')->count();
        $totalMonthlyRevenue = $allRentals->where('status', 'ACTIVE')->sum('monthly_fee');
        $totalDepositsHeld = $allRentals->where('status', 'ACTIVE')->sum('deposit_amount');

        $customers = Customer::where('status', 'ACTIVE')->orderBy('full_name')->get();
        $assets = CustomerAsset::with('product')->get();
        $activeRentals = Rental::where('status', 'ACTIVE')
            ->with(['customer', 'asset.product'])
            ->orderBy('rental_number', 'desc')
            ->get();

        return view('rentals.index', compact(
            'rentals',
            'activeRentals',
            'customers',
            'assets',
            'activeRentalsCount',
            'totalMonthlyRevenue',
            'totalDepositsHeld'
        ));
    }

    /* 2. Show Details (JSON for Edit Modal) */
    public function show($id)
    {
        $rental = Rental::with(['customer', 'asset.product'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $rental->id,
                'rental_number' => $rental->rental_number,
                'customer_id' => $rental->customer_id,
                'customer_name' => $rental->customer->name ?? '',
                'customer_phone' => $rental->customer->phone ?? '',
                'product_name' => $rental->product_name ?? ($rental->asset->custom_name ?? ($rental->asset->product->name ?? '')),
                'monthly_fee' => (float) $rental->monthly_fee,
                'deposit_amount' => (float) $rental->deposit_amount,
                'billing_day' => (int) $rental->billing_day,
                'start_date' => $rental->start_date ? $rental->start_date->format('Y-m-d') : '',
                'end_date' => $rental->end_date ? $rental->end_date->format('Y-m-d') : '',
                'status' => $rental->status,
                'notes' => $rental->notes ?? '',
            ]
        ]);
    }

    /* 3. Insert Action */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'product_name' => 'nullable|string|max:255',
            'asset_id' => 'nullable|exists:customer_assets,id',
            'start_date' => 'required|date',
            'monthly_fee' => 'required|numeric|min:1',
            'deposit_amount' => 'nullable|numeric|min:0',
            'billing_day' => 'required|integer|min:1|max:31',
            'notes' => 'nullable|string',
        ]);

        $productName = trim((string) $request->input('product_name', ''));
        $assetId = $request->input('asset_id');

        if (empty($productName) && empty($assetId)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'product_name' => 'Please enter a product name to give on rent.',
            ]);
        }

        $customerId = $validated['customer_id'];
        $startDate = $validated['start_date'];
        $monthlyFee = $validated['monthly_fee'];
        $depositAmount = $validated['deposit_amount'] ?? 0;
        $billingDay = $validated['billing_day'];
        $notes = $validated['notes'] ?? null;

        $rentalNumber = BusinessSequence::nextNumber('RENTAL');

        $rental = Rental::create([
            'id' => (string) Str::uuid(),
            'rental_number' => $rentalNumber,
            'customer_id' => $customerId,
            'asset_id' => $assetId ?: null,
            'product_name' => $productName ?: null,
            'start_date' => $startDate,
            'monthly_fee' => $monthlyFee,
            'deposit_amount' => $depositAmount,
            'billing_day' => $billingDay,
            'status' => 'ACTIVE',
            'billing_frequency' => 'MONTHLY',
            'notes' => $notes,
        ]);

        AuditLog::create([
            'id' => (string) Str::uuid(),
            'entity_type' => 'RENTAL',
            'entity_id' => $rental->id,
            'action' => 'CREATE',
            'user_id' => auth()->id() ?? 'system',
            'performed_by' => auth()->user()->name ?? 'Staff',
            'changes' => json_encode(['rental_number' => $rentalNumber, 'monthly_fee' => $rental->monthly_fee]),
        ]);

        $msg = "Rental agreement {$rentalNumber} created.";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'data' => $rental,
            ]);
        }

        return redirect()->back()
            ->with('success', $msg)
            ->with('msgb', $msg);
    }

    /* 4. Update Action */
    public function update(Request $request, $id)
    {
        $rental = Rental::findOrFail($id);

        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'product_name' => 'required|string|max:255',
            'monthly_fee' => 'required|numeric|min:1',
            'deposit_amount' => 'nullable|numeric|min:0',
            'billing_day' => 'required|integer|min:1|max:31',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
            'status' => 'required|string|in:ACTIVE,PAYMENT_DUE,OVERDUE,SUSPENDED,RETURNED,COMPLETED,CANCELLED,TERMINATED',
            'notes' => 'nullable|string',
        ]);

        $rental->update([
            'customer_id' => $validated['customer_id'],
            'product_name' => $validated['product_name'],
            'monthly_fee' => $validated['monthly_fee'],
            'deposit_amount' => $validated['deposit_amount'] ?? 0,
            'billing_day' => $validated['billing_day'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'] ?: null,
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
        ]);

        AuditLog::create([
            'id' => (string) Str::uuid(),
            'entity_type' => 'RENTAL',
            'entity_id' => $rental->id,
            'action' => 'UPDATE',
            'user_id' => auth()->id() ?? 'system',
            'performed_by' => auth()->user()->name ?? 'Staff',
            'changes' => json_encode($rental->getChanges()),
        ]);

        $msg = "Rental agreement {$rental->rental_number} updated successfully.";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'data' => $rental,
            ]);
        }

        return redirect()->back()
            ->with('success', $msg)
            ->with('msgb', $msg);
    }

    /* 5. Return Machine Action */
    public function returnMachine(Request $request, $id)
    {
        $rental = Rental::with('customer')->findOrFail($id);

        $validated = $request->validate([
            'return_date' => 'required|date',
            'refund_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $returnDate = $validated['return_date'];
        $refundAmount = isset($validated['refund_amount']) ? (float) $validated['refund_amount'] : null;
        $returnNotes = $validated['notes'] ?? null;

        $currentNotes = $rental->notes ? trim($rental->notes) : '';
        $appendNote = "Machine returned on " . Carbon::parse($returnDate)->format('d M Y') . ".";
        if ($refundAmount !== null) {
            $appendNote .= " Deposit refund: ₹" . number_format($refundAmount, 2) . ".";
        }
        if ($returnNotes) {
            $appendNote .= " Remarks: {$returnNotes}";
        }

        $newNotes = !empty($currentNotes) ? "{$currentNotes}\n{$appendNote}" : $appendNote;

        $rental->update([
            'status' => 'RETURNED',
            'end_date' => $returnDate,
            'notes' => $newNotes,
        ]);

        AuditLog::create([
            'id' => (string) Str::uuid(),
            'entity_type' => 'RENTAL',
            'entity_id' => $rental->id,
            'action' => 'RETURN_MACHINE',
            'user_id' => auth()->id() ?? 'system',
            'performed_by' => auth()->user()->name ?? 'Staff',
            'changes' => json_encode([
                'status' => 'RETURNED',
                'return_date' => $returnDate,
                'refund_amount' => $refundAmount,
                'notes' => $appendNote,
            ]),
        ]);

        $msg = "Machine for rental {$rental->rental_number} has been marked as returned.";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'data' => $rental,
            ]);
        }

        return redirect()->back()
            ->with('success', $msg)
            ->with('msgb', $msg);
    }
}
