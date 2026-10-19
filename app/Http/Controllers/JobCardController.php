<?php

namespace App\Http\Controllers;

use App\Models\JobCard;
use App\Models\Technician;
use Illuminate\Http\Request;

class JobCardController extends Controller
{
    /* 1. View / List Details */
    public function index(Request $request)
    {
        $search = $request->filled('search') ? trim((string) $request->input('search')) : null;
        $status = $request->filled('status') ? trim((string) $request->input('status')) : null;

        $query = JobCard::with(['customer', 'asset.product', 'technician', 'service']);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('job_card_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('full_name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        if (!empty($status) && $status !== 'ALL') {
            $query->where('status', $status);
        }

        $jobCards = $query->latest()->paginate(15)->withQueryString();

        return view('job_cards.index', compact('jobCards'));
    }

    /* 2. View Single / Show Details */
    public function show($id)
    {
        $jobCard = JobCard::with(['customer.addresses', 'asset.product', 'technician', 'service.warranty'])
            ->where('id', $id)
            ->orWhere('job_card_number', $id)
            ->firstOrFail();

        return view('job_cards.show', compact('jobCard'));
    }
}
