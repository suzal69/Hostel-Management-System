<?php
// eSewa Health Check - Determine best available configuration

/**
 * Check eSewa endpoint health
 * @param string $url Endpoint URL to check
 * @return array Health check result
 */
function checkEsewaEndpointHealth($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    // Add User-Agent to avoid being blocked
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    
    $start_time = microtime(true);
    $response = curl_exec($ch);
    $end_time = microtime(true);
    
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $total_time = $end_time - $start_time;
    
    curl_close($ch);
    
    // For POST-only endpoints, 405 (Method Not Allowed) on GET is actually OK - it means endpoint exists
    // 404 is worse - means endpoint doesn't exist at all
    // Accept 200, 302, 405, or 400 as signs server is reachable
    $is_available = ($http_code == 200 || $http_code == 302 || $http_code == 405 || $http_code == 400) && empty($error);
    
    // Fix health check to properly handle 405 responses for POST-only endpoints
    if ($http_code == 405) {
        $is_available = true;
    }
    
    return [
        'url' => $url,
        'http_code' => $http_code,
        'response_time' => $total_time,
        'error' => $error,
        'is_available' => $is_available,
        'response_length' => strlen($response)
    ];
}

/**
 * Get best available eSewa configuration
 * @return array Best configuration or false if none available
 */
function getBestEsewaConfiguration() {
    $urls = unserialize(ESEWA_PAYMENT_URLS);
    $credentials = unserialize(ESEWA_TEST_CREDENTIALS);
    
    $best_config = null;
    $best_score = -1;
    
    foreach ($urls as $url_type => $url) {
        $health = checkEsewaEndpointHealth($url);
        
        if ($health['is_available']) {
            // Score based on response time (lower is better)
            $score = 100 - ($health['response_time'] * 10);
            
            // Prefer primary endpoint
            if ($url_type === 'primary') {
                $score += 50;
            } elseif ($url_type === 'secondary') {
                $score += 25;
            }
            
            if ($score > $best_score) {
                $best_score = $score;
                $best_config = [
                    'url' => $url,
                    'url_type' => $url_type,
                    'health' => $health,
                    'credentials' => $credentials[0] // Use primary credentials for best endpoint
                ];
            }
        }
        
        // Health check completed
    }
    
    return $best_config;
}

/**
 * Update eSewa configuration to use best available endpoint
 * @return bool True if configuration updated successfully
 */
function updateEsewaToBestConfiguration() {
    $best_config = getBestEsewaConfiguration();
    
    if ($best_config) {
        // Update the global constant (this won't actually change the constant,
        // but we can use this in our payment logic)
        return $best_config;
    }
    
    return false;
}
?>
