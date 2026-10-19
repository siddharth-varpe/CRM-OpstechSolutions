<?php

namespace App\Http\Controllers;

use App\Models\Warranty;
use App\Models\WarrantyEvent;
use App\Models\Customer;
use App\Models\CustomerAsset;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WarrantyController extends Controller
{
    // ---------------------- 1. View / List Details ---------------------
    public function index(Request $request)
    {
        $query = Warranty::with(['customer', 'asset.product']);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('warranty_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('full_name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  })
                  ->orWhereHas('asset', function ($aq) use ($search) {
                      $aq->where('serial_number', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status') && $request->status !== 'ALL') {
            $query->where('status', $request->status);
        }

        $warranties = $query->latest('start_date')->paginate(15)->withQueryString();

        $allWarranties = Warranty::all();
        $totalActive = $allWarranties->where('status', 'ACTIVE')->count();
        $totalExpired = $allWarranties->where('status', 'EXPIRED')->count();
        $expiringSoon = $allWarranties->filter(function ($w) {
            return $w->status === 'ACTIVE' && $w->end_date && $w->end_date <= now()->addDays(30);
        })->count();

        return view('warranties.index', compact('warranties', 'totalActive', 'totalExpired', 'expiringSoon'));
    }

    // ---------------------- 2. View Single / Show Details ---------------------
    public function show($id)
    {
        $warranty = Warranty::with(['customer.addresses', 'asset.product', 'events', 'schedules.service'])
            ->where('id', $id)
            ->orWhere('warranty_number', $id)
            ->firstOrFail();

        return view('warranties.show', compact('warranty'));
    }

    // ---------------------- 3. Record Warranty Event Action ---------------------
    public function recordEvent(Request $request, $id)
    {
        $validated = $request->validate([
            'event_type' => 'required|string',
            'description' => 'required|string',
            'resolution' => 'nullable|string',
        ]);

        $warranty = Warranty::findOrFail($id);

        $event = WarrantyEvent::create([
            'id' => (string) Str::uuid(),
            'warranty_id' => $warranty->id,
            'event_type' => $validated['event_type'],
            'event_date' => now(),
            'description' => $validated['description'],
            'resolution' => $validated['resolution'] ?? null,
            'logged_by' => auth()->id(),
        ]);

        AuditLog::create([
            'id' => (string) Str::uuid(),
            'entity_type' => 'WARRANTY_EVENT',
            'entity_id' => $event->id,
            'action' => 'CREATE',
            'user_id' => auth()->id() ?? 'system',
            'performed_by' => auth()->user()->name ?? 'Staff',
            'changes' => json_encode(['warranty' => $warranty->warranty_number, 'event' => $validated['event_type']]),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Warranty claim/event recorded successfully.',
        ]);
    }
}
