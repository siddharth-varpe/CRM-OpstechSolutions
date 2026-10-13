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
        $tab = $request->get('tab', 'business'); // business, numbering, tax, audit

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
}
