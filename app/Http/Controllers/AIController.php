<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AIController extends Controller
{
    public function analyzeStaff($id)
    {
        try {
            $apiKey = env('GEMINI_API_KEY');
            $projectId = env('FIRESTORE_PROJECT_ID', 'doremi-admin2'); 
            
            // 1. TARIK DATA DARI FIRESTORE
            $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/attendances";
            $response = Http::withoutVerifying()->get($url);
            $data = $response->json();

            $historyTeks = "";
            $foundRecords = 0;

            if (isset($data['documents'])) {
                // Kita terbalikkan array supaya dapat data terbaru dulu
                $reversedDocs = array_reverse($data['documents']);

                foreach ($reversedDocs as $doc) {
                    $f = $doc['fields'] ?? [];
                    $userIdInDb = $f['user_id']['stringValue'] ?? '';

                    if (trim($userIdInDb) === trim($id)) {
                        $foundRecords++;
                        $status = $f['status']['stringValue'] ?? 'Present';
                        $date   = $f['date']['stringValue'] ?? 'N/A';
                        
                        $historyTeks .= "($date: $status). ";

                        // ZASS: HADKAN DATA! 
                        // Cukup ambil 5-7 rekod terakhir supaya AI tak pening & jimat token.
                        if ($foundRecords >= 7) break; 
                    }
                }
            }

            if ($foundRecords === 0) {
                return response()->json(['insight' => "No attendance records found for this employee in Firestore."]);
            }

            // 2. GUNA MODEL LITE (Guna v1 supaya lebih stabil bila v1beta sibuk)
            // 2. GUNA MODEL YANG SAH (Gemini 2.0 Flash Lite)
// Kita guna v1beta sebab list bos tadi tunjuk model 2.0/2.5 aktif di sini
$modelName = "gemini-2.0-flash-lite"; 
$geminiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$modelName}:generateContent?key={$apiKey}";

$prompt = "Anda HR Manager DOREMi. Ulas disiplin staf ini (bawah 15 patah perkataan) dalam Bahasa Melayu: $historyTeks";

$geminiRes = Http::withoutVerifying()->post($geminiUrl, [
    "contents" => [["parts" => [["text" => $prompt]]]]
]);

$result = $geminiRes->json();

// Check ralat
if (isset($result['error'])) {
    return response()->json(['insight' => "Google Error: " . $result['error']['message']]);
}

$insight = $result['candidates'][0]['content']['parts'][0]['text'] ?? "AI service is currently unavailable.";
return response()->json(['insight' => trim($insight)]);

            $insight = $result['candidates'][0]['content']['parts'][0]['text'] ?? "AI service is currently unavailable.";
            return response()->json(['insight' => trim($insight)]);

        } catch (\Exception $e) {
            return response()->json(['insight' => "System Error: " . $e->getMessage()], 500);
        }
    }
}


