<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class SyncFirestoreBlueprint extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firestore:sync-blueprint';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Firestore collections to match the Master Blueprint exactly (adds missing, deletes extra, enforces types).';

    private $projectId = env('FIREBASE_PROJECT_ID', 'doremi-admin');

    // The Master Blueprint
    private $blueprint = [
        'users' => [
            'full_name' => 'str', 'email' => 'str', 'phone_number' => 'str', 'department' => 'str', 
            'job_title' => 'str', 'role' => 'str', 'status' => 'str', 'al_balance' => 'int', 
            'el_balance' => 'int', 'mc_balance' => 'int', 'ot_balance' => 'double', 
            'join_date' => 'str', 'profile_image' => 'str', 
            'Address' => 'str', 'updated_at' => 'timestamp'
        ],
        'overtime' => [
            'uid' => 'str', 'staffName' => 'str', 'date' => 'str', 'startTime' => 'str', 
            'endTime' => 'str', 'totalHours' => 'double', 'notes' => 'str', 'status' => 'str', 
            'proof_file' => 'str', 'submitted_at' => 'timestamp'
        ],
        'flexi' => [
            'uid' => 'str', 'staffName' => 'str', 'date' => 'str', 'startTime' => 'str', 
            'endTime' => 'str', 'totalHours' => 'double', 'flexi_type' => 'str', 'status' => 'str', 
            'submitted_at' => 'timestamp'
        ],
        'leave_requests' => [ // Maps to 'leaves' blueprint
            'uid' => 'str', 'staffName' => 'str', 'leave_type' => 'str', 'start_date' => 'str', 
            'end_date' => 'str', 'total_days' => 'int', 'reason' => 'str', 'status' => 'str', 
            'proof_file' => 'str', 'submitted_at' => 'timestamp'
        ],
        'attendances' => [
            'uid' => 'str', 'staffName' => 'str', 'date' => 'str', 'clock_in_time' => 'str', 
            'clock_out_time' => 'str', 'status' => 'str', 'work_location' => 'str'
        ]
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Starting Firestore Blueprint Sync...");
        $this->warn("CRITICAL: Excluded collection 'notifications'.");
        
        foreach ($this->blueprint as $collection => $fieldsBlueprint) {
            $this->info("\nProcessing collection: {$collection}");
            $this->syncCollection($collection, $fieldsBlueprint);
        }

        $this->info("\nSync completed successfully.");
    }

    private function syncCollection($collection, $blueprint)
    {
        $url = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/{$collection}";
        
        // Fetch all documents. (Assuming no pagination for this script or data fits in one page).
        // Added page size to handle more documents if needed.
        $response = Http::withoutVerifying()
            ->withOptions(['connect_timeout' => 30])
            ->timeout(30)
            ->get($url . "?pageSize=1000");
        $data = $response->json();

        if (!isset($data['documents'])) {
            $this->line("  -> No documents found.");
            return;
        }

        $count = 0;
        foreach ($data['documents'] as $doc) {
            $docName = $doc['name']; // full path like projects/.../documents/users/ID
            $existingFields = $doc['fields'] ?? [];
            
            $newFields = [];

            // Build new fields based strictly on blueprint
            foreach ($blueprint as $fieldName => $type) {
                // Determine existing value or fallback to default
                $value = $this->extractExistingValue($existingFields, $fieldName, $type);
                $newFields[$fieldName] = $this->formatFieldValue($type, $value);
            }

            // Patch document (Overwrites completely, removing undocumented fields)
            $patchUrl = "https://firestore.googleapis.com/v1/{$docName}";
            $patchRes = Http::withoutVerifying()
                ->retry(3, 1000)
                ->withOptions(['connect_timeout' => 30])
                ->timeout(30)
                ->patch($patchUrl, [
                    'fields' => $newFields
                ]);

            if ($patchRes->successful()) {
                $count++;
            } else {
                $this->error("  -> Failed to sync document: {$docName}");
                $this->error($patchRes->body());
            }
            
            sleep(1); // prevent rate limit / connection drops
        }
        $this->info("  -> Successfully synced {$count} documents.");
    }

    private function extractExistingValue($fields, $fieldName, $expectedType)
    {
        if (!array_key_exists($fieldName, $fields)) {
            // Return default based on type
            return $this->getDefaultValue($expectedType);
        }

        $fieldData = $fields[$fieldName];
        
        // Extract whatever value exists, prioritizing the expected type key
        if (isset($fieldData['stringValue'])) return $fieldData['stringValue'];
        if (isset($fieldData['integerValue'])) return $fieldData['integerValue'];
        if (isset($fieldData['doubleValue'])) return $fieldData['doubleValue'];
        if (isset($fieldData['timestampValue'])) return $fieldData['timestampValue'];
        if (isset($fieldData['booleanValue'])) return $fieldData['booleanValue'];
        if (isset($fieldData['nullValue'])) return null;

        return $this->getDefaultValue($expectedType);
    }

    private function getDefaultValue($type)
    {
        switch ($type) {
            case 'str': return '';
            case 'int': return 0;
            case 'double': return 0.0;
            case 'timestamp': return Carbon::now()->toIso8601String();
            default: return '';
        }
    }

    private function formatFieldValue($type, $value)
    {
        switch ($type) {
            case 'str':
                return ['stringValue' => (string) $value];
            case 'int':
                return ['integerValue' => (int) $value];
            case 'double':
                // Firestore expects a number, casting to float
                return ['doubleValue' => (float) $value];
            case 'timestamp':
                // Check if it's already a valid ISO string, if not format it
                try {
                    $ts = Carbon::parse($value)->toIso8601ZuluString();
                } catch (\Exception $e) {
                    $ts = Carbon::now()->toIso8601ZuluString();
                }
                // Ensure the 'Z' format for Firestore timestamp
                return ['timestampValue' => str_replace('+00:00', 'Z', $ts)];
            default:
                return ['stringValue' => (string) $value];
        }
    }
}
