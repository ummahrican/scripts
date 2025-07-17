<?php
// api/sheet-data.php - Google Sheets API with Service Account Authentication

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://mp.melc.berkeley.edu');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Configuration
$SHEET_ID = '<GOOGLE_SHEET_ID>';
$SHEET_NAME = 'MP Docs';
$SERVICE_ACCOUNT_FILE = '/var/www/html/api/service-account.json'; // Path to your service account JSON file

// Cache settings
$cache_file = '/tmp/melc_sheet_cache.json';
$cache_duration = 300; // 5 minutes in seconds

function generateJWT($service_account_info) {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'RS256']);
    $now = time();
    $payload = json_encode([
        'iss' => $service_account_info['client_email'],
        'scope' => 'https://www.googleapis.com/auth/spreadsheets.readonly',
        'aud' => 'https://oauth2.googleapis.com/token',
        'exp' => $now + 3600,
        'iat' => $now
    ]);
    
    $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
    
    $signature = '';
    $private_key = $service_account_info['private_key'];
    openssl_sign($base64Header . "." . $base64Payload, $signature, $private_key, OPENSSL_ALGO_SHA256);
    $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    return $base64Header . "." . $base64Payload . "." . $base64Signature;
}

function getAccessToken($service_account_info) {
    $jwt = generateJWT($service_account_info);
    
    $postData = http_build_query([
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $jwt
    ]);
    
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/x-www-form-urlencoded',
            'content' => $postData,
            'timeout' => 10
        ]
    ]);
    
    $response = file_get_contents('https://oauth2.googleapis.com/token', false, $context);
    if ($response === false) {
        throw new Exception('Failed to get access token from Google OAuth2');
    }
    
    $data = json_decode($response, true);
    if (!isset($data['access_token'])) {
        throw new Exception('Invalid response from Google OAuth2: ' . $response);
    }
    
    return $data['access_token'];
}

function fetchSheetData($sheet_id, $sheet_name, $service_account_file) {
    // Load service account credentials
    if (!file_exists($service_account_file)) {
        throw new Exception('Service account file not found. Please upload the JSON file to: ' . $service_account_file);
    }
    
    $service_account_content = file_get_contents($service_account_file);
    if ($service_account_content === false) {
        throw new Exception('Failed to read service account file');
    }
    
    $service_account_info = json_decode($service_account_content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON in service account file');
    }
    
    // Get access token
    $access_token = getAccessToken($service_account_info);
    
    // Make API request
    $url = "https://sheets.googleapis.com/v4/spreadsheets/{$sheet_id}/values/" . urlencode($sheet_name);
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 15,
            'user_agent' => 'MELC-Sheets-API/1.0',
            'method' => 'GET',
            'header' => "Authorization: Bearer {$access_token}\r\n"
        ]
    ]);
    
    $response = file_get_contents($url, false, $context);
    
    if ($response === false) {
        $error = error_get_last();
        throw new Exception('Failed to fetch data from Google Sheets API: ' . ($error['message'] ?? 'Unknown error'));
    }
    
    $data = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON response from Google Sheets API');
    }
    
    if (isset($data['error'])) {
        throw new Exception('Google Sheets API Error: ' . $data['error']['message']);
    }
    
    if (!isset($data['values']) || empty($data['values'])) {
        throw new Exception('No data found in the sheet. Check sheet name and permissions.');
    }
    
    // Convert to array of objects - ONLY SPECIFIC COLUMNS
    $values = $data['values'];
    $headers = array_shift($values);
    $result = [];
    
    // Define which columns to include (0-indexed: A=0, B=1, C=2, D=3, E=4, F=5, G=6, H=7, K=10, M=12, R=17, S=18, Y=24)
    $allowed_columns = [0, 1, 2, 3, 4, 5, 6, 7, 8, 10, 14, 15, 16]; // A, B, C, D, E, F, G, H, I, O, P, Q
    
    foreach ($values as $row) {
        $rowData = [];
        
        // Only process allowed columns
        foreach ($allowed_columns as $col_index) {
            if (isset($headers[$col_index])) {
                $header = trim($headers[$col_index]);
                $value = isset($row[$col_index]) ? trim($row[$col_index]) : '';
                $rowData[$header] = $value;
            }
        }
        
        // Skip completely empty rows
        if (array_filter($rowData, function($value) { return !empty($value); })) {
            $result[] = $rowData;
        }
    }
    
    return $result;
}

function getCachedData($cache_file, $cache_duration) {
    if (!file_exists($cache_file)) {
        return null;
    }
    
    $cache_age = time() - filemtime($cache_file);
    if ($cache_age >= $cache_duration) {
        return null;
    }
    
    $cached_content = file_get_contents($cache_file);
    if ($cached_content === false) {
        return null;
    }
    
    $cached_data = json_decode($cached_content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return null;
    }
    
    return $cached_data;
}

function setCachedData($cache_file, $data) {
    $cache_data = [
        'data' => $data,
        'timestamp' => time()
    ];
    
    file_put_contents($cache_file, json_encode($cache_data));
}

// Main execution
try {
    // Check cache first
    $cached = getCachedData($cache_file, $cache_duration);
    
    if ($cached !== null) {
        // Return cached data
        echo json_encode([
            'success' => true,
            'data' => $cached['data'],
            'lastUpdated' => $cached['timestamp'] * 1000, // JavaScript timestamp
            'recordCount' => count($cached['data']),
            'cached' => true
        ]);
    } else {
        // Fetch fresh data
        $data = fetchSheetData($SHEET_ID, $SHEET_NAME, $SERVICE_ACCOUNT_FILE);
        
        // Cache the result
        setCachedData($cache_file, $data);
        
        // Return fresh data
        echo json_encode([
            'success' => true,
            'data' => $data,
            'lastUpdated' => time() * 1000, // JavaScript timestamp
            'recordCount' => count($data),
            'cached' => false
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch sheet data',
        'message' => $e->getMessage()
    ]);
}
?>
