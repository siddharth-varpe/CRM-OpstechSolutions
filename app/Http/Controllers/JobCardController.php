<?php

namespace App\Http\Controllers;

use App\Models\JobCard;
use App\Models\Technician;
use Illuminate\Http\Request;

class JobCardController extends Controller
{
    public function index(Request $request)
    {
        $query = JobCard::with(['customer', 'asset.product', 'technician', 'service']);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('job_card_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('full_name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status') && $request->status !== 'ALL') {
            $query->where('status', $request->status);
        }

        $jobCards = $query->latest()->paginate(15)->withQueryString();

        return view('job_cards.index', compact('jobCards'));
    }

    public function show($id)
    {
        $jobCard = JobCard::with(['customer.addresses', 'asset.product', 'technician', 'service.warranty'])
            ->where('id', $id)
            ->orWhere('job_card_number', $id)
            ->firstOrFail();

        return view('job_cards.show', compact('jobCard'));
    }
}
