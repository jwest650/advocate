<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CookieConsentController extends Controller
{
    public function store(Request $request)
    {
        try {
            $data = $request->all();
            $csvFile = storage_path('app/private/cookie_consents.csv');
            
            // Ensure storage directory exists
            $storageDir = dirname($csvFile);
            if (!is_dir($storageDir)) {
                mkdir($storageDir, 0755, true);
            }
            
            // Create headers if file doesn't exist
            if (!file_exists($csvFile)) {
                $headers = array_keys($data);
                file_put_contents($csvFile, implode(',', $headers) . "\n");
            }
            
            // Append data
            $values = array_map(function($value) {
                return is_string($value) ? '"' . str_replace('"', '""', $value) . '"' : $value;
            }, array_values($data));
            
            $result = file_put_contents($csvFile, implode(',', $values) . "\n", FILE_APPEND);
            
            if ($result === false) {
                throw new \Exception('Failed to write to CSV file');
            }
            
            return response()->json(['success' => true, 'message' => 'Cookie consent saved successfully']);
        } catch (\Exception $e) {
            \Log::error('Cookie consent storage failed: ' . $e->getMessage());
            return response()->json([
                'success' => false, 
                'message' => 'Failed to save cookie consent: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function download()
    {
        $csvFile = storage_path('app/private/cookie_consents.csv');
        
        if (!file_exists($csvFile)) {
            abort(404, 'No cookie consent data found');
        }
        
        return response()->download($csvFile, 'cookie-consents-' . date('Y-m-d') . '.csv');
    }
}