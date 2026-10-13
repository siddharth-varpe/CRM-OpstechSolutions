<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\CustomerCustomLabel;
use App\Models\BusinessSequence;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    /**
     * Display a listing of customers with filters and pagination
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $status = $request->input('status', 'ALL');
        $type = $request->input('type', 'ALL');
        $label = $request->input('label', 'ALL');
        $perPage = (int) $request->input('per_page', 10);

        $query = Customer::with(['primaryAddress', 'customLabel', 'assets', 'invoices'])
            ->whereNull('archived_at');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('customer_number', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($status !== 'ALL') {
            $query->where('status', $status);
        }

        if ($type !== 'ALL') {
            $query->where('customer_type', $type);
        }

        if ($label !== 'ALL') {
            $query->where('customer_label', $label);
        }

        // Export to CSV if requested
        if ($request->input('export') === 'csv') {
            return $this->exportCsv($query->get());
        }

        $customers = $query->latest()->paginate($perPage)->withQueryString();

        // Calculate summary cards
        $totalCount = Customer::whereNull('archived_at')->count();
        $activeCount = Customer::whereNull('archived_at')->where('status', 'ACTIVE')->count();
        $commercialCount = Customer::whereNull('archived_at')->where('customer_type', 'COMMERCIAL')->count();
        $individualCount = Customer::whereNull('archived_at')->where('customer_type', 'INDIVIDUAL')->count();

        $customLabels = CustomerCustomLabel::all();

        return view('customers.index', [
            'customers' => $customers,
            'search' => $search,
            'status' => $status,
            'type' => $type,
            'label' => $label,
            'stats' => [
                'total' => $totalCount,
                'active' => $activeCount,
                'commercial' => $commercialCount,
                'individual' => $individualCount,
            ],
            'customLabels' => $customLabels,
        ]);
    }

    /**
     * Display single customer profile details
     */
    public function show($id)
    {
        $customer = Customer::with([
            'addresses',
            'customLabel',
            'assets.product',
            'assets.warranties',
            'invoices.items',
            'invoices.payments',
            'services.technician',
            'payments.invoice',
            'rentals.asset',
        ])->findOrFail($id);

        return view('customers.show', [
            'customer' => $customer,
        ]);
    }

    /**
     * Store a newly created customer
     */
    public function store(Request $request)
    {
        if (!$request->has('customer_type')) {
            $request->merge(['customer_type' => 'INDIVIDUAL']);
        }
        if (!$request->has('status')) {
            $request->merge(['status' => 'ACTIVE']);
        }
        if (!$request->has('state')) {
            $request->merge(['state' => 'Maharashtra']);
        }
        if (!$request->has('postal_code') && $request->has('pincode')) {
            $request->merge(['postal_code' => $request->input('pincode')]);
        }

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'customer_type' => 'required|in:INDIVIDUAL,COMMERCIAL',
            'company_name' => 'nullable|string|max:255',
            'gst_number' => 'nullable|string|max:50',
            'status' => 'required|in:ACTIVE,INACTIVE',
            'customer_label' => 'nullable|in:GOOD,BAD',
            'custom_label_id' => 'nullable|exists:customer_custom_labels,id',
            'notes' => 'nullable|string',
            // Primary address fields
            'address_line1' => 'required|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'landmark' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $customerNumber = BusinessSequence::nextNumber('CUSTOMER');

            $customer = Customer::create([
                'customer_number' => $customerNumber,
                'full_name' => $validated['full_name'],
                'phone' => $validated['phone'],
                'email' => $validated['email'] ?? null,
                'customer_type' => $validated['customer_type'],
                'company_name' => $validated['company_name'] ?? null,
                'gst_number' => $validated['gst_number'] ?? null,
                'status' => $validated['status'],
                'customer_label' => $validated['customer_label'] ?? null,
                'custom_label_id' => $validated['custom_label_id'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            CustomerAddress::create([
                'customer_id' => $customer->id,
                'address_type' => 'BOTH',
                'address_line1' => $validated['address_line1'],
                'address_line2' => $validated['address_line2'] ?? null,
                'landmark' => $validated['landmark'] ?? null,
                'city' => $validated['city'],
                'state' => $validated['state'],
                'postal_code' => $validated['postal_code'],
                'is_default' => true,
            ]);

            AuditLog::log('CREATE', 'CUSTOMER', $customer->id, null, ['name' => $customer->full_name]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Customer {$customer->full_name} created successfully.",
                    'redirect' => route('customers.show', $customer->id),
                ]);
            }

            return redirect()->route('customers.show', $customer->id)
                ->with('success', "Customer {$customer->full_name} created successfully.");
        });
    }

    /**
     * Update customer profile
     */
    public function update(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'customer_type' => 'required|in:INDIVIDUAL,COMMERCIAL',
            'company_name' => 'nullable|string|max:255',
            'gst_number' => 'nullable|string|max:50',
            'status' => 'required|in:ACTIVE,INACTIVE',
            'customer_label' => 'nullable|in:GOOD,BAD',
            'notes' => 'nullable|string',
        ]);

        $customer->update($validated);
        AuditLog::log('UPDATE', 'CUSTOMER', $customer->id, null, $validated);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Customer profile updated successfully.',
                'reload' => true,
            ]);
        }

        return back()->with('success', 'Customer profile updated successfully.');
    }

    /**
     * Soft-archive / delete customer
     */
    public function archive($id)
    {
        $customer = Customer::findOrFail($id);
        $customer->update(['archived_at' => now(), 'status' => 'INACTIVE']);
        AuditLog::log('ARCHIVE', 'CUSTOMER', $customer->id);

        return redirect()->route('customers.index')->with('success', "Customer {$customer->full_name} archived.");
    }

    /**
     * Add address to customer
     */
    public function storeAddress(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);

        $validated = $request->validate([
            'address_type' => 'required|in:BILLING,SERVICE,BOTH',
            'address_line1' => 'required|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'landmark' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'is_default' => 'nullable|boolean',
        ]);

        if (!empty($validated['is_default'])) {
            CustomerAddress::where('customer_id', $customer->id)->update(['is_default' => false]);
        }

        $address = CustomerAddress::create([
            'customer_id' => $customer->id,
            'address_type' => $validated['address_type'],
            'address_line1' => $validated['address_line1'],
            'address_line2' => $validated['address_line2'] ?? null,
            'landmark' => $validated['landmark'] ?? null,
            'city' => $validated['city'],
            'state' => $validated['state'],
            'postal_code' => $validated['postal_code'],
            'is_default' => $request->boolean('is_default'),
        ]);

        return back()->with('success', 'New address added successfully.');
    }

    /**
     * Export customer records as CSV
     */
    private function exportCsv($customers)
    {
        $filename = 'customers-export-' . date('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($customers) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Customer Number', 'Full Name', 'Phone', 'Email', 'Type', 'Company', 'GSTIN', 'City', 'State', 'Status', 'Label', 'Created At']);

            foreach ($customers as $c) {
                $addr = $c->primaryAddress;
                fputcsv($handle, [
                    $c->customer_number,
                    $c->full_name,
                    $c->phone,
                    $c->email ?? '',
                    $c->customer_type,
                    $c->company_name ?? '',
                    $c->gst_number ?? '',
                    $addr ? $addr->city : '',
                    $addr ? $addr->state : '',
                    $c->status,
                    $c->customer_label ?? '',
                    $c->created_at->format('Y-m-d'),
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Download sample import template (CSV with name, number, address)
     */
    public function downloadSampleImport()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="customers_import_sample.csv"',
        ];

        $callback = function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['name', 'number', 'address']);
            fputcsv($handle, ['Rohan Deshmukh', '9822011223', 'Flat 102, Green Acres, Baner, Pune']);
            fputcsv($handle, ['Sunita Joshi', '', 'Plot 14, MIDC Bhosari, Pune']);
            fputcsv($handle, ['Vikas Shinde', '9822998877', '']);
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Step 1: Analyze uploaded Excel or CSV file and return pre-import report
     */
    public function analyzeImport(Request $request)
    {
        $rawRows = [];
        $filename = 'imported_data';

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filename = $file->getClientOriginalName();
            $ext = strtolower($file->getClientOriginalExtension());
            $realPath = $file->getRealPath();

            if (in_array($ext, ['csv', 'txt'])) {
                $rawRows = $this->parseCsvFile($realPath);
            } elseif (in_array($ext, ['xlsx', 'xls'])) {
                $rawRows = $this->parseExcelFile($realPath);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Unsupported file format. Please upload an Excel (.xlsx, .xls) or CSV (.csv) file.',
                ], 422);
            }
        } elseif ($request->filled('rows')) {
            $rawRows = is_array($request->input('rows')) ? $request->input('rows') : json_decode($request->input('rows'), true);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'No file or spreadsheet data received for import analysis.',
            ], 422);
        }

        if (empty($rawRows) || !is_array($rawRows)) {
            return response()->json([
                'success' => false,
                'message' => 'The uploaded file is empty or could not be read.',
            ], 422);
        }

        // Detect column indices based on header names (name, number, address)
        $headerRow = $rawRows[0] ?? [];
        $nameCol = null;
        $numberCol = null;
        $addressCol = null;

        foreach ($headerRow as $idx => $val) {
            $norm = strtolower(trim((string)$val));
            $norm = preg_replace('/[^a-z0-9]/', '', $norm);

            if ($nameCol === null && in_array($norm, ['name', 'fullname', 'customername', 'customer', 'client', 'contactname', 'person'])) {
                $nameCol = $idx;
            } elseif ($numberCol === null && in_array($norm, ['number', 'phone', 'phonenumber', 'mobile', 'mobilenumber', 'contact', 'contactnumber', 'tel', 'cell'])) {
                $numberCol = $idx;
            } elseif ($addressCol === null && in_array($norm, ['address', 'location', 'addr', 'fulladdress', 'addressline', 'addressline1', 'city', 'residence'])) {
                $addressCol = $idx;
            }
        }

        $hasHeader = ($nameCol !== null || $numberCol !== null || $addressCol !== null);
        $startRow = $hasHeader ? 1 : 0;

        // Default missing columns to sequential indices if not matched in header
        if ($nameCol === null) $nameCol = 0;
        if ($numberCol === null) $numberCol = ($nameCol === 1 ? 0 : 1);
        if ($addressCol === null) {
            $used = [$nameCol, $numberCol];
            for ($i = 0; $i < 10; $i++) {
                if (!in_array($i, $used)) {
                    $addressCol = $i;
                    break;
                }
            }
        }

        $analyzedRows = [];
        $completeCount = 0;
        $partialCount = 0;
        $blankCount = 0;

        for ($i = $startRow; $i < count($rawRows); $i++) {
            $row = $rawRows[$i];
            if (!is_array($row)) {
                continue;
            }

            $name = isset($row[$nameCol]) ? trim((string)$row[$nameCol]) : '';
            $number = isset($row[$numberCol]) ? trim((string)$row[$numberCol]) : '';
            $address = isset($row[$addressCol]) ? trim((string)$row[$addressCol]) : '';

            // Clean numerical phone if exported as float like 9822011223.0
            if (preg_match('/^\d+\.0$/', $number)) {
                $number = substr($number, 0, -2);
            }

            // If entire row is empty, count as blank and skip
            if ($name === '' && $number === '' && $address === '') {
                $blankCount++;
                continue;
            }

            $missing = [];
            if ($name === '') $missing[] = 'Name';
            if ($number === '') $missing[] = 'Number';
            if ($address === '') $missing[] = 'Address';

            $status = empty($missing) ? 'COMPLETE' : 'PARTIAL';
            if ($status === 'COMPLETE') {
                $completeCount++;
            } else {
                $partialCount++;
            }

            $analyzedRows[] = [
                'row_num' => $i + 1,
                'name' => $name,
                'number' => $number,
                'address' => $address,
                'status' => $status,
                'missing' => $missing,
            ];
        }

        if (empty($analyzedRows)) {
            return response()->json([
                'success' => false,
                'message' => 'No importable rows found in the uploaded file.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'report' => [
                'filename' => $filename,
                'total_rows' => count($analyzedRows),
                'complete_count' => $completeCount,
                'partial_count' => $partialCount,
                'blank_count' => $blankCount,
                'columns' => [
                    'name_header' => $hasHeader && isset($headerRow[$nameCol]) ? $headerRow[$nameCol] : 'Col ' . ($nameCol + 1),
                    'number_header' => $hasHeader && isset($headerRow[$numberCol]) ? $headerRow[$numberCol] : 'Col ' . ($numberCol + 1),
                    'address_header' => $hasHeader && isset($headerRow[$addressCol]) ? $headerRow[$addressCol] : 'Col ' . ($addressCol + 1),
                ],
                'preview' => array_slice($analyzedRows, 0, 50),
                'rows' => $analyzedRows,
            ]
        ]);
    }

    /**
     * Step 2: Final commit of analyzed customer rows into the CRM
     */
    public function commitImport(Request $request)
    {
        $validated = $request->validate([
            'rows' => 'required|array|min:1',
            'rows.*.name' => 'nullable|string',
            'rows.*.number' => 'nullable|string',
            'rows.*.address' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated) {
            $importedCount = 0;

            foreach ($validated['rows'] as $item) {
                $name = trim((string)($item['name'] ?? ''));
                $number = trim((string)($item['number'] ?? ''));
                $address = trim((string)($item['address'] ?? ''));

                if ($name === '' && $number === '' && $address === '') {
                    continue;
                }

                $customerNumber = BusinessSequence::nextNumber('CUSTOMER');

                $customer = Customer::create([
                    'customer_number' => $customerNumber,
                    'full_name' => $name,
                    'phone' => $number,
                    'customer_type' => 'INDIVIDUAL',
                    'status' => 'ACTIVE',
                    'created_by' => auth()->id(),
                ]);

                CustomerAddress::create([
                    'customer_id' => $customer->id,
                    'address_type' => 'BOTH',
                    'address_line1' => $address,
                    'city' => '',
                    'state' => '',
                    'postal_code' => '',
                    'is_default' => true,
                ]);

                AuditLog::log('IMPORT', 'CUSTOMER', $customer->id, null, [
                    'name' => $name,
                    'phone' => $number,
                    'address' => $address,
                ]);

                $importedCount++;
            }

            return response()->json([
                'success' => true,
                'message' => "Successfully imported {$importedCount} customer" . ($importedCount === 1 ? '' : 's') . ".",
                'imported_count' => $importedCount,
                'redirect' => route('customers.index'),
            ]);
        });
    }

    /**
     * Parse CSV file into rows
     */
    private function parseCsvFile(string $filePath): array
    {
        $rows = [];
        $handle = fopen($filePath, 'r');
        if (!$handle) return [];

        // Auto-detect delimiter from first line
        $firstLine = fgets($handle);
        $delimiter = ',';
        if (substr_count($firstLine, ';') > substr_count($firstLine, ',')) {
            $delimiter = ';';
        } elseif (substr_count($firstLine, "\t") > substr_count($firstLine, ',')) {
            $delimiter = "\t";
        }
        rewind($handle);

        $isFirst = true;
        while (($data = fgetcsv($handle, 10000, $delimiter)) !== false) {
            if ($isFirst && !empty($data)) {
                // Strip UTF-8 BOM if present
                $bom = pack('H*','EFBBBF');
                $data[0] = preg_replace("/^$bom/", '', $data[0]);
                $isFirst = false;
            }
            $rows[] = $data;
        }
        fclose($handle);
        return $rows;
    }

    /**
     * Parse XLSX file using python helper
     */
    private function parseExcelFile(string $filePath): array
    {
        $scriptPath = base_path('scripts/parse_excel.py');
        if (!file_exists($scriptPath)) {
            return [];
        }

        $cmd = 'python3 ' . escapeshellarg($scriptPath) . ' ' . escapeshellarg($filePath);
        $output = shell_exec($cmd);
        if (!$output) {
            return [];
        }

        $decoded = json_decode($output, true);
        return is_array($decoded) ? $decoded : [];
    }
}
