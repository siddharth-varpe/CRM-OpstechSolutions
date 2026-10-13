<?php

namespace App\Http\Controllers;

use App\Models\Rental;
use App\Models\RentalPayment;
use App\Models\Customer;
use App\Models\CustomerAsset;
use App\Models\BusinessSequence;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RentalController extends Controller
{
    public function index(Request $request)
    {
        $query = Rental::with(['customer', 'asset.product', 'payments']);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('rental_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('full_name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status') && $request->status !== 'ALL') {
            $query->where('status', $request->status);
        }

        $rentals = $query->latest('start_date')->paginate(15)->withQueryString();

        $allRentals = Rental::all();
        $activeRentalsCount = $allRentals->where('status', 'ACTIVE')->count();
        $totalMonthlyRevenue = $allRentals->where('status', 'ACTIVE')->sum('monthly_fee');
        $totalDepositsHeld = $allRentals->where('status', 'ACTIVE')->sum('deposit_amount');

        $customers = Customer::where('status', 'ACTIVE')->orderBy('full_name')->get();
        $assets = CustomerAsset::with('product')->get();

        return view('rentals.index', compact(
            'rentals',
            'customers',
            'assets',
            'activeRentalsCount',
            'totalMonthlyRevenue',
            'totalDepositsHeld'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'asset_id' => 'required|exists:customer_assets,id',
            'start_date' => 'required|date',
            'monthly_fee' => 'required|numeric|min:1',
            'deposit_amount' => 'nullable|numeric|min:0',
            'billing_day' => 'required|integer|min:1|max:31',
            'notes' => 'nullable|string',
        ]);

        $rentalNumber = BusinessSequence::nextNumber('RENTAL');

        $rental = Rental::create([
            'id' => (string) Str::uuid(),
            'rental_number' => $rentalNumber,
            'customer_id' => $validated['customer_id'],
            'asset_id' => $validated['asset_id'],
            'start_date' => $validated['start_date'],
            'monthly_fee' => $validated['monthly_fee'],
            'deposit_amount' => $validated['deposit_amount'] ?? 0,
            'billing_day' => $validated['billing_day'],
            'status' => 'ACTIVE',
            'billing_frequency' => 'MONTHLY',
            'notes' => $validated['notes'] ?? null,
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

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Rental agreement {$rentalNumber} created.",
                'data' => $rental,
            ]);
        }

        return redirect()->back()->with('success', "Rental agreement {$rentalNumber} created.");
    }
}
