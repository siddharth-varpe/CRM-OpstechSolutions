<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Invoice;
use App\Models\Service;
use App\Models\CustomerAsset;
use App\Models\JobCard;

class SearchController extends Controller
{
    /**
     * Unified Global Command Search
     */
    public function globalSearch(Request $request)
    {
        return $this->search($request);
    }

    public function search(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        if (strlen($q) < 2) {
            return response()->json(['results' => []]);
        }

        $results = [];

        // 1. Customers
        $customers = Customer::where('full_name', 'like', "%{$q}%")
            ->orWhere('phone', 'like', "%{$q}%")
            ->orWhere('customer_number', 'like', "%{$q}%")
            ->orWhere('company_name', 'like', "%{$q}%")
            ->take(5)
            ->get();

        foreach ($customers as $c) {
            $results[] = [
                'type' => 'Customer',
                'title' => $c->full_name . ' (' . $c->customer_number . ')',
                'subtitle' => 'Phone: ' . $c->phone . ($c->company_name ? ' • ' . $c->company_name : ''),
                'url' => route('customers.show', $c->id),
            ];
        }

        // 2. Assets / Machines
        $assets = CustomerAsset::where('serial_number', 'like', "%{$q}%")
            ->orWhere('asset_number', 'like', "%{$q}%")
            ->orWhere('custom_name', 'like', "%{$q}%")
            ->with(['customer', 'product'])
            ->take(4)
            ->get();

        foreach ($assets as $a) {
            $results[] = [
                'type' => 'Asset',
                'title' => ($a->custom_name ?? ($a->product->name ?? 'Equipment / Asset')) . ' [' . ($a->serial_number ?? $a->asset_number) . ']',
                'subtitle' => 'Customer: ' . ($a->customer->full_name ?? 'Unknown') . ' • Status: ' . $a->status,
                'url' => route('customers.show', $a->customer_id) . '#assets',
            ];
        }

        // 3. Invoices
        $invoices = Invoice::where('invoice_number', 'like', "%{$q}%")
            ->with('customer')
            ->take(4)
            ->get();

        foreach ($invoices as $inv) {
            $results[] = [
                'type' => 'Invoice',
                'title' => $inv->invoice_number . ' — ₹' . number_format($inv->total_amount, 2),
                'subtitle' => 'Customer: ' . ($inv->customer->full_name ?? 'Unknown') . ' • Due: ' . $inv->due_date->format('d M, Y'),
                'url' => route('invoices.show', $inv->id),
            ];
        }

        // 4. Services
        $services = Service::where('service_number', 'like', "%{$q}%")
            ->with('customer')
            ->take(4)
            ->get();

        foreach ($services as $srv) {
            $results[] = [
                'type' => 'Service',
                'title' => $srv->service_number . ' (' . $srv->service_type . ')',
                'subtitle' => 'Customer: ' . ($srv->customer->full_name ?? 'Unknown') . ' • Status: ' . $srv->status,
                'url' => route('services.show', $srv->id),
            ];
        }

        // 5. Job Cards
        $jobCards = JobCard::where('job_card_number', 'like', "%{$q}%")
            ->with('customer')
            ->take(3)
            ->get();

        foreach ($jobCards as $jc) {
            $results[] = [
                'type' => 'Job Card',
                'title' => $jc->job_card_number . ' — ' . $jc->status,
                'subtitle' => 'Customer: ' . ($jc->customer->full_name ?? 'Unknown'),
                'url' => route('job_cards.show', $jc->id),
            ];
        }

        // 6. Products
        $products = Product::where('name', 'like', "%{$q}%")
            ->orWhere('sku', 'like', "%{$q}%")
            ->orWhere('brand', 'like', "%{$q}%")
            ->take(3)
            ->get();

        foreach ($products as $p) {
            $results[] = [
                'type' => 'Product',
                'title' => $p->name . ' (' . $p->sku . ')',
                'subtitle' => 'Brand: ' . $p->brand . ' • ₹' . number_format($p->unit_price, 2),
                'url' => route('inventory.index'),
            ];
        }

        return response()->json(['results' => $results]);
    }
}
