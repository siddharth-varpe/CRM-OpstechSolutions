<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\JobCard;
use App\Models\Customer;
use App\Models\CustomerAsset;
use App\Models\Technician;
use App\Models\Warranty;
use App\Models\BusinessSequence;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Service::with(['customer', 'asset.product', 'technician', 'jobCard']);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('service_number', 'like', "%{$search}%")
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

        if ($request->filled('technician_id')) {
            $query->where('technician_id', $request->technician_id);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        $services = $query->orderBy('scheduled_date', 'desc')->paginate(15)->withQueryString();

        // Statistics
        $allServices = Service::all();
        $totalScheduled = $allServices->whereIn('status', ['SCHEDULED', 'ASSIGNED'])->count();
        $totalInProgress = $allServices->where('status', 'IN_PROGRESS')->count();
        $totalCompleted = $allServices->where('status', 'COMPLETED')->count();
        $totalOverdue = $allServices->filter(function ($s) {
            return $s->scheduled_date < now() && !in_array($s->status, ['COMPLETED', 'CANCELLED']);
        })->count();

        $technicians = Technician::where('status', 'ACTIVE')->orderBy('full_name')->get();
        $customers = Customer::where('status', 'ACTIVE')->orderBy('full_name')->get();
        $assets = CustomerAsset::with(['customer', 'product', 'activeWarranty'])->get();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $services->items(),
                'pagination' => [
                    'page' => $services->currentPage(),
                    'total' => $services->total(),
                ],
                'stats' => [
                    'scheduled' => $totalScheduled,
                    'in_progress' => $totalInProgress,
                    'completed' => $totalCompleted,
                    'overdue' => $totalOverdue,
                ],
            ]);
        }

        $heatmapData = $this->getHeatmapMonthData(now()->year, now()->month);

        return view('services.index', compact(
            'services',
            'technicians',
            'customers',
            'assets',
            'totalScheduled',
            'totalInProgress',
            'totalCompleted',
            'totalOverdue',
            'heatmapData'
        ));
    }

    /**
     * AJAX endpoint to fetch service schedule counts and details for a given month
     */
    public function calendarHeatmap(Request $request)
    {
        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);

        $data = $this->getHeatmapMonthData($year, $month);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Helper to compute month heatmap counts and detailed service lists
     */
    private function getHeatmapMonthData(int $year, int $month): array
    {
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $services = Service::with(['customer', 'technician', 'asset.product'])
            ->whereBetween('scheduled_date', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->orderBy('scheduled_date')
            ->get();

        $byDate = [];
        foreach ($services as $s) {
            $dateKey = Carbon::parse($s->scheduled_date)->format('Y-m-d');
            if (!isset($byDate[$dateKey])) {
                $byDate[$dateKey] = [];
            }
            $byDate[$dateKey][] = [
                'id' => $s->id,
                'service_number' => $s->service_number,
                'service_type' => $s->service_type,
                'formatted_type' => ucwords(str_replace('_', ' ', strtolower($s->service_type))),
                'status' => $s->status,
                'priority' => $s->priority,
                'scheduled_date' => $s->scheduled_date ? $s->scheduled_date->format('Y-m-d') : null,
                'formatted_date' => $s->scheduled_date ? $s->scheduled_date->format('d M, Y') : '',
                'scheduled_time_slot' => $s->scheduled_time_slot ?? 'Flexible Slot',
                'customer_id' => $s->customer_id,
                'customer_name' => $s->customer ? $s->customer->name : 'Direct Customer',
                'customer_phone' => $s->customer ? $s->customer->phone : null,
                'asset_name' => ($s->asset && !empty($s->asset->custom_name)) ? $s->asset->custom_name : ($s->asset && $s->asset->product ? $s->asset->product->name : 'Water Purifier'),
                'serial_number' => $s->asset ? $s->asset->serial_number : null,
                'technician_name' => $s->technician ? $s->technician->full_name : null,
                'technician_phone' => $s->technician ? $s->technician->phone : null,
                'url' => route('services.show', $s->id),
            ];
        }

        $counts = [];
        foreach ($byDate as $dateStr => $items) {
            $counts[$dateStr] = count($items);
        }

        return [
            'year' => $year,
            'month' => $month,
            'month_name' => $startDate->format('F Y'),
            'days_in_month' => $startDate->daysInMonth,
            'first_day_of_week' => $startDate->dayOfWeekIso, // 1 = Mon, 7 = Sun
            'counts' => $counts,
            'services' => $byDate,
        ];
    }

    public function create(Request $request)
    {
        $customers = Customer::where('status', 'ACTIVE')->orderBy('full_name')->get();
        $technicians = Technician::where('status', 'ACTIVE')->orderBy('full_name')->get();
        $assets = CustomerAsset::with(['customer', 'product', 'activeWarranty'])->get();

        $selectedCustomerId = $request->query('customer_id');
        $selectedAssetId = $request->query('asset_id');

        if ($selectedAssetId && !$selectedCustomerId) {
            $foundAsset = $assets->firstWhere('id', $selectedAssetId);
            if ($foundAsset) {
                $selectedCustomerId = $foundAsset->customer_id;
            }
        }

        $prefill = [
            'customer_id' => $selectedCustomerId,
            'asset_id' => $selectedAssetId,
            'warranty_id' => $request->query('warranty_id'),
            'service_type' => $request->query('service_type', 'PERIODIC_MAINTENANCE'),
            'priority' => $request->query('priority', 'NORMAL'),
            'scheduled_date' => $request->query('scheduled_date', date('Y-m-d')),
            'scheduled_time_slot' => $request->query('scheduled_time_slot', '10:00 AM - 01:00 PM'),
            'customer_notes' => $request->query('notes', ''),
        ];

        return view('services.create', compact('customers', 'technicians', 'assets', 'prefill'));
    }

    public function customerAssets($id)
    {
        $assets = CustomerAsset::with(['product', 'activeWarranty'])
            ->where('customer_id', $id)
            ->get()
            ->map(function ($asset) {
                $hasWarranty = !empty($asset->activeWarranty);
                $name = !empty($asset->custom_name) ? $asset->custom_name : ($asset->product->name ?? 'Equipment / Unit');
                $sn = (!empty($asset->serial_number) && $asset->serial_number !== 'N/A') ? " (S/N: {$asset->serial_number})" : '';
                $displayName = $name . $sn;

                return [
                    'id' => $asset->id,
                    'asset_number' => $asset->asset_number,
                    'serial_number' => $asset->serial_number ?? 'N/A',
                    'product_name' => $asset->product->name ?? 'Equipment / Unit',
                    'custom_name' => $asset->custom_name,
                    'display_name' => $displayName,
                    'purchase_date' => $asset->purchase_date ? $asset->purchase_date->format('d M, Y') : 'N/A',
                    'has_warranty' => $hasWarranty,
                    'warranty_id' => $hasWarranty ? $asset->activeWarranty->id : null,
                    'warranty_end' => $hasWarranty ? $asset->activeWarranty->end_date->format('d M, Y') : null,
                    'warranty_number' => $hasWarranty ? $asset->activeWarranty->warranty_number : null,
                ];
            });

        return response()->json([
            'success' => true,
            'count' => $assets->count(),
            'assets' => $assets
        ]);
    }

    public function show($id)
    {
        $service = Service::with(['customer.addresses', 'asset.product', 'technician', 'jobCard', 'warranty'])
            ->where('id', $id)
            ->orWhere('service_number', $id)
            ->firstOrFail();

        $technicians = Technician::where('status', 'ACTIVE')->get();

        return view('services.show', compact('service', 'technicians'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'asset_id' => 'nullable|required_without:custom_asset_name|exists:customer_assets,id',
            'custom_asset_name' => 'nullable|required_without:asset_id|string|max:255',
            'custom_asset_serial' => 'nullable|string|max:100',
            'service_type' => 'required|string',
            'service_location' => 'nullable|string',
            'service_classification' => 'nullable|string',
            'scheduled_date' => 'required|date',
            'scheduled_time_slot' => 'nullable|string',
            'priority' => 'nullable|string',
            'technician_id' => 'nullable|exists:technicians,id',
            'warranty_id' => 'nullable|exists:warranties,id',
            'customer_notes' => 'nullable|string',
            'internal_notes' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $serviceNumber = BusinessSequence::nextNumber('SERVICE');
            $jobCardNumber = BusinessSequence::nextNumber('JOBCARD');

            $status = !empty($validated['technician_id']) ? 'ASSIGNED' : 'SCHEDULED';

            $assetId = $validated['asset_id'] ?? null;
            $customAssetName = trim($request->input('custom_asset_name', ''));

            if (!empty($customAssetName)) {
                $defaultProduct = \App\Models\Product::where('product_type', 'RO_MACHINE')->first()
                    ?? \App\Models\Product::first();

                if (!$defaultProduct) {
                    $defaultProduct = \App\Models\Product::create([
                        'id' => (string) Str::uuid(),
                        'name' => 'Equipment / Asset',
                        'sku' => 'PROD-EQUIP-GENERIC',
                        'product_type' => 'RO_MACHINE',
                        'base_price' => 0,
                        'is_active' => true,
                    ]);
                }

                $newAsset = CustomerAsset::create([
                    'id' => (string) Str::uuid(),
                    'asset_number' => BusinessSequence::nextNumber('ASSET'),
                    'customer_id' => $validated['customer_id'],
                    'product_id' => $defaultProduct->id,
                    'asset_type' => 'RO_MACHINE',
                    'custom_name' => $customAssetName,
                    'serial_number' => $request->input('custom_asset_serial') ?: ('SN-' . strtoupper(Str::random(6))),
                    'purchase_date' => now(),
                    'initial_warranty_months' => 12,
                    'service_interval_months' => 3,
                    'status' => 'ACTIVE',
                ]);

                $assetId = $newAsset->id;
            }

            // Auto-detect or associate warranty
            $warrantyId = $validated['warranty_id'] ?? null;
            if (!$warrantyId && $assetId) {
                $asset = CustomerAsset::with('activeWarranty')->find($assetId);
                if ($asset && $asset->activeWarranty) {
                    $warrantyId = $asset->activeWarranty->id;
                }
            }

            $classification = $validated['service_classification'] ?? ($warrantyId ? 'WARRANTY' : 'GENERAL');

            $service = Service::create([
                'id' => (string) Str::uuid(),
                'service_number' => $serviceNumber,
                'customer_id' => $validated['customer_id'],
                'asset_id' => $assetId,
                'warranty_id' => $warrantyId,
                'technician_id' => $validated['technician_id'] ?? null,
                'service_type' => $validated['service_type'],
                'service_location' => $validated['service_location'] ?? 'DOORSTEP',
                'service_classification' => $classification,
                'scheduled_date' => $validated['scheduled_date'],
                'scheduled_time_slot' => $validated['scheduled_time_slot'] ?? '10:00 AM - 01:00 PM',
                'status' => $status,
                'priority' => $validated['priority'] ?? 'NORMAL',
                'customer_notes' => $validated['customer_notes'] ?? null,
                'internal_notes' => $validated['internal_notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            JobCard::create([
                'id' => (string) Str::uuid(),
                'job_card_number' => $jobCardNumber,
                'service_id' => $service->id,
                'customer_id' => $service->customer_id,
                'asset_id' => $service->asset_id,
                'technician_id' => $service->technician_id,
                'problem_reported' => $validated['customer_notes'] ?? $validated['service_type'],
                'status' => $status,
                'labor_charges' => 0.00,
                'parts_charges' => 0.00,
                'total_charges' => 0.00,
            ]);

            AuditLog::log('CREATE', 'SERVICE', $service->id, null, [
                'service_number' => $serviceNumber,
                'job_card_number' => $jobCardNumber,
                'customer_id' => $service->customer_id,
                'asset_id' => $service->asset_id,
            ]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Service {$serviceNumber} scheduled with Job Card {$jobCardNumber}.",
                    'data' => $service,
                    'redirect' => route('services.show', $service->id),
                ]);
            }

            return redirect()->route('services.show', $service->id)->with('success', "Service {$serviceNumber} scheduled successfully.");
        });
    }

    public function assignTechnician(Request $request, $id)
    {
        $validated = $request->validate([
            'technician_id' => 'required|exists:technicians,id',
        ]);

        $service = Service::findOrFail($id);
        $service->update([
            'technician_id' => $validated['technician_id'],
            'status' => 'ASSIGNED',
        ]);

        if ($service->jobCard) {
            $service->jobCard->update([
                'technician_id' => $validated['technician_id'],
                'status' => 'ASSIGNED',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => "Technician assigned to service {$service->service_number}.",
        ]);
    }

    public function complete(Request $request, $id)
    {
        $validated = $request->validate([
            'diagnosis' => 'required|string',
            'work_performed' => 'required|string',
            'labor_charges' => 'nullable|numeric|min:0',
            'parts_charges' => 'nullable|numeric|min:0',
            'parts_replaced' => 'nullable|string',
            'technician_notes' => 'nullable|string',
            'customer_remarks' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated, $id) {
            $service = Service::with('jobCard')->findOrFail($id);
            $service->update([
                'status' => 'COMPLETED',
                'completed_at' => now(),
            ]);

            $labor = (float)($validated['labor_charges'] ?? 0);
            $parts = (float)($validated['parts_charges'] ?? 0);
            $total = $labor + $parts;

            if ($service->jobCard) {
                $service->jobCard->update([
                    'diagnosis' => $validated['diagnosis'],
                    'work_performed' => $validated['work_performed'],
                    'labor_charges' => $labor,
                    'parts_charges' => $parts,
                    'total_charges' => $total,
                    'technician_notes' => $validated['technician_notes'] ?? null,
                    'customer_remarks' => $validated['customer_remarks'] ?? null,
                    'completed_at' => now(),
                    'status' => 'COMPLETED',
                ]);
            }

            // Update asset last service date
            if ($service->asset) {
                $service->asset->update([
                    'last_service_date' => now(),
                    'next_service_due_date' => now()->addMonths(3),
                ]);
            }

            AuditLog::create([
                'id' => (string) Str::uuid(),
                'entity_type' => 'SERVICE',
                'entity_id' => $service->id,
                'action' => 'COMPLETE',
                'user_id' => auth()->id() ?? 'system',
                'performed_by' => auth()->user()->name ?? 'Staff',
                'changes' => json_encode(['status' => 'COMPLETED', 'total_charges' => $total]),
            ]);

            return response()->json([
                'success' => true,
                'message' => "Service {$service->service_number} marked as completed.",
            ]);
        });
    }
}
