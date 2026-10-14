<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Console\Command;

class AuditCustomerCoordinatesCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'customers:audit-coordinates {--limit=20 : Maximum suspicious records to display in detail}';

    /**
     * The console command description.
     */
    protected $description = 'Safely audit existing customer location coordinates for missing, invalid, or placeholder data';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info("==========================================================");
        $this->info("   ENTERPRISES CRM — CUSTOMER COORDINATES AUDIT REPORT     ");
        $this->info("==========================================================");
        $this->newLine();

        $totalCustomers = Customer::whereNull('archived_at')->count();
        $totalAddresses = CustomerAddress::count();

        $missingCoords = 0;
        $placeholderZeros = 0;
        $outOfRange = 0;
        $suspectedReversed = 0;
        $validExactCoords = 0;
        $suspiciousRecords = [];

        CustomerAddress::with('customer')->chunk(500, function ($addresses) use (
            &$missingCoords,
            &$placeholderZeros,
            &$outOfRange,
            &$suspectedReversed,
            &$validExactCoords,
            &$suspiciousRecords
        ) {
            foreach ($addresses as $addr) {
                $lat = $addr->latitude;
                $lng = $addr->longitude;

                if ($lat === null || $lng === null) {
                    $missingCoords++;
                    if (count($suspiciousRecords) < 50) {
                        $suspiciousRecords[] = [
                            'customer_number' => $addr->customer ? $addr->customer->customer_number : 'N/A',
                            'address_id' => $addr->id,
                            'reason' => 'MISSING_COORDINATES',
                            'lat' => 'NULL',
                            'lng' => 'NULL',
                            'city' => $addr->city ?: 'Unknown',
                        ];
                    }
                    continue;
                }

                $latF = (float) $lat;
                $lngF = (float) $lng;

                // Check (0, 0) placeholder
                if (abs($latF) < 0.00001 && abs($lngF) < 0.00001) {
                    $placeholderZeros++;
                    $suspiciousRecords[] = [
                        'customer_number' => $addr->customer ? $addr->customer->customer_number : 'N/A',
                        'address_id' => $addr->id,
                        'reason' => 'PLACEHOLDER_ZERO (0,0)',
                        'lat' => $latF,
                        'lng' => $lngF,
                        'city' => $addr->city ?: 'Unknown',
                    ];
                    continue;
                }

                // Check geographic valid range: lat -90..90, lng -180..180
                if ($latF < -90 || $latF > 90 || $lngF < -180 || $lngF > 180) {
                    $outOfRange++;
                    $suspiciousRecords[] = [
                        'customer_number' => $addr->customer ? $addr->customer->customer_number : 'N/A',
                        'address_id' => $addr->id,
                        'reason' => 'OUT_OF_GEOGRAPHIC_RANGE',
                        'lat' => $latF,
                        'lng' => $lngF,
                        'city' => $addr->city ?: 'Unknown',
                    ];
                    continue;
                }

                // Check Indian subcontinent reversed coordinates (India latitude ~8..37 N, longitude ~68..97 E)
                // If lat is in [68..97] and lng is in [8..37], lat & lng were swapped!
                if ($latF >= 68 && $latF <= 97 && $lngF >= 8 && $lngF <= 37) {
                    $suspectedReversed++;
                    $suspiciousRecords[] = [
                        'customer_number' => $addr->customer ? $addr->customer->customer_number : 'N/A',
                        'address_id' => $addr->id,
                        'reason' => 'SUSPECTED_SWAPPED_LAT_LNG',
                        'lat' => $latF,
                        'lng' => $lngF,
                        'city' => $addr->city ?: 'Unknown',
                    ];
                    continue;
                }

                $validExactCoords++;
            }
        });

        // Summary Statistics Table
        $this->table(
            ['Metric', 'Count', 'Status'],
            [
                ['Total Active Customers', $totalCustomers, 'INFO'],
                ['Total Address Records', $totalAddresses, 'INFO'],
                ['Valid Non-Zero Coordinates', $validExactCoords, $validExactCoords > 0 ? '<info>OK</info>' : '<comment>NEEDS_ACTION</comment>'],
                ['Missing Coordinates (NULL)', $missingCoords, $missingCoords > 0 ? '<comment>REQUIRES_PIN</comment>' : '<info>NONE</info>'],
                ['Placeholder Zero Coordinates (0,0)', $placeholderZeros, $placeholderZeros > 0 ? '<error>INVALID</error>' : '<info>NONE</info>'],
                ['Geographically Out of Range', $outOfRange, $outOfRange > 0 ? '<error>INVALID</error>' : '<info>NONE</info>'],
                ['Suspected Reversed Lat/Lng', $suspectedReversed, $suspectedReversed > 0 ? '<error>REVERSED</error>' : '<info>NONE</info>'],
            ]
        );

        $this->newLine();
        $this->info("Safe Diagnostic Audit Complete (Zero data was modified).");
        $this->info("Admins can open any customer record in the CRM UI to pinpoint and save verified premise coordinates.");

        $limit = (int) $this->option('limit');
        if (!empty($suspiciousRecords) && $limit > 0) {
            $this->newLine();
            $this->warn("Sample of records requiring location attention (Displaying first {$limit}):");
            $this->table(
                ['Customer Number', 'Address ID', 'Diagnosis', 'Latitude', 'Longitude', 'City'],
                array_slice($suspiciousRecords, 0, $limit)
            );
        }

        return 0;
    }
}
