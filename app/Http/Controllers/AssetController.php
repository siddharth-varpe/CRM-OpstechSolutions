<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CustomerAsset;
use App\Models\Warranty;
use App\Models\ServiceSchedule;
use App\Models\BusinessSequence;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AssetController extends Controller
{
    // ---------------------- 1. Insert Action ---------------------
    /**
     * Store new customer asset and automatically mint warranty & service schedules
     */
    public function store(Request $request, $customerId = null)
    {
        if ($customerId && !$request->has('customer_id')) {
            $request->merge(['customer_id' => $customerId]);
        }
        if (!$request->has('purchase_date')) {
            $request->merge(['purchase_date' => $request->input('installation_date', now()->format('Y-m-d'))]);
        }
        if (!$request->has('initial_warranty_months')) {
            $request->merge(['initial_warranty_months' => $request->input('warranty_duration_months', 12)]);
        }

        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'product_id' => 'required|exists:products,id',
            'custom_name' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:100',
            'purchase_date' => 'required|date',
            'initial_warranty_months' => 'nullable|integer|min:0|max:120',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $assetNumber = BusinessSequence::nextNumber('ASSET');

            // Explicit input extraction (Senior Blueprint)
            $custId         = $validated['customer_id'];
            $prodId         = $validated['product_id'];
            $customName     = $validated['custom_name'] ?? null;
            $serialNumber   = $validated['serial_number'] ?? null;
            $warrantyMonths = (int) ($validated['initial_warranty_months'] ?? 12);
            $purchaseDate   = Carbon::parse($validated['purchase_date']);

            $asset = CustomerAsset::create([
                'asset_number'            => $assetNumber,
                'customer_id'             => $custId,
                'product_id'              => $prodId,
                'asset_type'              => 'RO_MACHINE',
                'custom_name'             => $customName,
                'serial_number'           => $serialNumber,
                'purchase_date'           => $purchaseDate,
                'initial_warranty_months' => $warrantyMonths,
                'service_interval_months' => 3,
                'status'                  => 'ACTIVE',
            ]);

            // Automatically generate Warranty contract if months > 0
            if ($warrantyMonths > 0) {
                $warrantyNumber = BusinessSequence::nextNumber('WARRANTY');
                $endDate = $purchaseDate->copy()->addMonths($warrantyMonths);

                $warranty = Warranty::create([
                    'warranty_number' => $warrantyNumber,
                    'customer_id'     => $asset->customer_id,
                    'asset_id'        => $asset->id,
                    'warranty_type'   => 'STANDARD_MACHINE',
                    'start_date'      => $purchaseDate,
                    'end_date'        => $endDate,
                    'duration_months' => $warrantyMonths,
                    'status'          => 'ACTIVE',
                    'terms'           => 'Standard warranty covering internal electrical booster pump, SMPS, and electronic valves.',
                ]);

                // Generate quarterly periodic maintenance schedules (e.g. 4 schedules for 12 months)
                $intervalMonths = 3;
                $numSchedules = max(1, (int) floor($warrantyMonths / $intervalMonths));

                for ($i = 1; $i <= $numSchedules; $i++) {
                    $plannedDate = $purchaseDate->copy()->addMonths($i * $intervalMonths);
                    ServiceSchedule::create([
                        'customer_id'     => $asset->customer_id,
                        'asset_id'        => $asset->id,
                        'warranty_id'     => $warranty->id,
                        'schedule_index'  => $i,
                        'total_schedules' => $numSchedules,
                        'planned_date'    => $plannedDate,
                        'target_month'    => $plannedDate->format('Y-m'),
                        'status'          => 'PENDING',
                    ]);
                }
            }

            AuditLog::log('CREATE', 'ASSET', $asset->id, null, ['serial' => $asset->serial_number]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Machine asset {$asset->asset_number} registered with automated warranty schedules.",
                    'reload'  => true,
                ]);
            }

            return back()
                ->with('success', "Machine asset {$asset->asset_number} registered.")
                ->with('msgb', "Machine asset {$asset->asset_number} registered.");
        });
    }
}
