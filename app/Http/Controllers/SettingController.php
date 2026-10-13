<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use App\Models\BusinessSequence;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'business'); // business, numbering, tax, audit, data

        if ($tab === 'data' && (!$request->user() || !$request->user()->isSuperAdmin())) {
            $tab = 'business';
        }

        $settings = SystemSetting::all()->pluck('value', 'key')->toArray();

        // Harmonize legacy aliases for GSTIN, phone, email
        if (!isset($settings['company_gstin']) && isset($settings['gst_number'])) {
            $settings['company_gstin'] = $settings['gst_number'];
        }
        if (!isset($settings['company_phone']) && isset($settings['support_phone'])) {
            $settings['company_phone'] = $settings['support_phone'];
        }
        if (!isset($settings['company_email']) && isset($settings['support_email'])) {
            $settings['company_email'] = $settings['support_email'];
        }

        $sequences = BusinessSequence::orderBy('name')->get();
        $auditLogs = AuditLog::with('user')->latest('created_at')->paginate(20, ['*'], 'audit_page');

        return view('settings.index', compact('settings', 'sequences', 'auditLogs', 'tab'));
    }

    public function update(Request $request)
    {
        $inputs = $request->except(['_token', 'tab']);

        foreach ($inputs as $key => $val) {
            SystemSetting::updateOrCreate(
                ['key' => $key],
                ['value' => is_array($val) ? json_encode($val) : (string) $val]
            );
        }

        // Keep synced aliases
        if (isset($inputs['company_gstin'])) {
            SystemSetting::updateOrCreate(['key' => 'gst_number'], ['value' => (string) $inputs['company_gstin']]);
        }
        if (isset($inputs['company_phone'])) {
            SystemSetting::updateOrCreate(['key' => 'support_phone'], ['value' => (string) $inputs['company_phone']]);
        }
        if (isset($inputs['company_email'])) {
            SystemSetting::updateOrCreate(['key' => 'support_email'], ['value' => (string) $inputs['company_email']]);
        }

        AuditLog::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'entity_type' => 'SYSTEM_SETTINGS',
            'entity_id' => $request->get('tab', 'global'),
            'action' => 'UPDATE',
            'user_id' => auth()->id(),
            'new_values' => $inputs,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'System settings saved successfully.',
            ]);
        }

        return redirect()->route('settings.index', ['tab' => $request->get('tab', 'business')])->with('success', 'Settings updated.');
    }

    public function updateSequences(Request $request)
    {
        $sequencesData = $request->input('sequences', []);

        $changes = [];
        foreach ($sequencesData as $name => $data) {
            $seq = BusinessSequence::find($name);
            if ($seq) {
                $oldPrefix = $seq->prefix;
                $oldPadding = $seq->padding;

                if (!empty($data['prefix'])) {
                    $seq->prefix = strtoupper(trim($data['prefix']));
                }
                if (isset($data['padding'])) {
                    $seq->padding = max(2, min(10, (int) $data['padding']));
                }
                $seq->save();

                $changes[$name] = [
                    'prefix' => ['old' => $oldPrefix, 'new' => $seq->prefix],
                    'padding' => ['old' => $oldPadding, 'new' => $seq->padding],
                ];
            }
        }

        AuditLog::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'entity_type' => 'BUSINESS_SEQUENCES',
            'entity_id' => 'sequences',
            'action' => 'UPDATE',
            'user_id' => auth()->id(),
            'new_values' => $changes,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Document numbering rules saved successfully.',
            ]);
        }

        return redirect()->route('settings.index', ['tab' => 'numbering'])->with('success', 'Document numbering rules updated.');
    }

    /**
     * Generate real-time server calculated impact preview of CRM business data cleanup.
     * Restricted to authenticated Super Administrators.
     */
    public function cleanDataPreview(Request $request, \App\Services\DataCleanupService $cleanupService)
    {
        if (!$request->user() || !$request->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized. Only Super Administrators can preview CRM data cleanup.');
        }

        $preview = $cleanupService->getImpactPreview();

        return response()->json([
            'success' => true,
            'preview' => $preview,
        ]);
    }

    /**
     * Execute destructive CRM business data cleanup.
     * Enforces: Super Admin authorization, confirmation phrase, risk acknowledgment, password re-authentication, and concurrency locking.
     */
    public function executeCleanData(\App\Http\Requests\CleanDataRequest $request, \App\Services\DataCleanupService $cleanupService)
    {
        $user = $request->user();

        // 1. Authorization Gate
        if (!$user || !$user->isSuperAdmin()) {
            abort(403, 'Unauthorized. Only Super Administrators can execute CRM data cleanup.');
        }

        // 2. Sensitive Action Password Re-Authentication
        if (!\Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Super Administrator password. Operation rejected.',
                'errors' => [
                    'password' => ['The provided password does not match your Super Administrator account credentials.'],
                ],
            ], 422);
        }

        // 3. Concurrency & Replay Guard (Atomic Lock)
        $lock = \Illuminate\Support\Facades\Cache::lock('crm_data_cleanup_mutex', 60);
        if (!$lock->get()) {
            return response()->json([
                'success' => false,
                'message' => 'Another CRM data cleanup operation is currently executing. Please wait for it to complete.',
            ], 429);
        }

        try {
            $result = $cleanupService->executeCleanup($user);

            return response()->json($result);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('CRM Data Cleanup failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while cleaning CRM business records. Database changes were safely rolled back.',
            ], 500);
        } finally {
            $lock->release();
        }
    }
}

