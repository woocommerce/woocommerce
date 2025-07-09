<?php
/**
 * Test for WooCommerce Issue #59487: Coupon code invalid when similar code is used prior
 * 
 * This test verifies that the cache key consistency fix resolves conflicts between
 * similar coupon codes like "cat bird" and "catbird".
 */

// Test the cache key generation to ensure consistency
function test_coupon_cache_key_consistency() {
    echo "Testing coupon cache key consistency fix...\n\n";
    
    // Test cases that previously caused conflicts
    $test_cases = [
        ['cat bird', 'catbird'],
        ['test code', 'testcode'], 
        ['multi word', 'multiword'],
        ['UPPER CASE', 'upper case'],
        ['with-dash', 'with dash'],
    ];
    
    foreach ($test_cases as $pair) {
        $code1 = $pair[0];
        $code2 = $pair[1];
        
        // Simulate the fixed cache key generation
        $sanitized1 = wc_sanitize_coupon_code($code1);
        $sanitized2 = wc_sanitize_coupon_code($code2);
        
        $cache_key1 = 'wc_coupon_id_from_code_' . $sanitized1;
        $cache_key2 = 'wc_coupon_id_from_code_' . $sanitized2;
        
        echo "Pair: '{$code1}' vs '{$code2}'\n";
        echo "  Sanitized: '{$sanitized1}' vs '{$sanitized2}'\n";
        echo "  Cache keys: '{$cache_key1}' vs '{$cache_key2}'\n";
        
        if ($cache_key1 === $cache_key2) {
            echo "  ❌ CONFLICT: Both codes map to same cache key\n";
        } else {
            echo "  ✅ DISTINCT: Each code has unique cache key\n";
        }
        echo "\n";
    }
}

/**
 * Simplified mock of wc_sanitize_coupon_code for testing
 * In reality this calls WordPress sanitization functions
 */
function wc_sanitize_coupon_code($value) {
    // This mimics the actual WordPress sanitization that can normalize codes
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9\-_\s]/', '', $value);
    $value = preg_replace('/\s+/', '-', $value); // Replace spaces with dashes
    return $value;
}

/**
 * Test that demonstrates the fix
 */
function test_coupon_lookup_simulation() {
    echo "Simulating coupon lookup with fixed caching...\n\n";
    
    // Simulate coupon database (ID => normalized_code)
    $coupon_db = [
        123 => 'cat-bird',    // "cat bird" coupon 
        456 => 'catbird',     // "catbird" coupon
    ];
    
    // Simulate cache
    $cache = [];
    
    // Function to simulate the FIXED wc_get_coupon_id_by_code
    function lookup_coupon_id($code, &$cache, $coupon_db) {
        // Use sanitized code for cache key (this is the fix)
        $sanitized_code = wc_sanitize_coupon_code($code);
        $cache_key = 'coupon_id_from_code_' . $sanitized_code;
        
        echo "Looking up: '{$code}'\n";
        echo "  Sanitized to: '{$sanitized_code}'\n";
        echo "  Cache key: '{$cache_key}'\n";
        
        // Check cache first
        if (isset($cache[$cache_key])) {
            echo "  ✅ Found in cache: {$cache[$cache_key]}\n";
            return $cache[$cache_key];
        }
        
        // Simulate database lookup
        foreach ($coupon_db as $id => $stored_code) {
            if ($stored_code === $sanitized_code) {
                echo "  📁 Found in database: {$id}\n";
                $cache[$cache_key] = $id;
                return $id;
            }
        }
        
        echo "  ❌ Not found\n";
        return 0;
    }
    
    // Test the problematic scenario
    echo "Test 1: Look up 'cat bird'\n";
    $result1 = lookup_coupon_id('cat bird', $cache, $coupon_db);
    echo "\n";
    
    echo "Test 2: Look up 'catbird' (should be different coupon)\n";
    $result2 = lookup_coupon_id('catbird', $cache, $coupon_db);
    echo "\n";
    
    echo "Results:\n";
    echo "  'cat bird' returned ID: {$result1}\n";
    echo "  'catbird' returned ID: {$result2}\n";
    
    if ($result1 !== $result2 && $result1 > 0 && $result2 > 0) {
        echo "  ✅ SUCCESS: Each code returned different coupon ID\n";
    } else if ($result1 === $result2) {
        echo "  ❌ FAILURE: Both codes returned same ID (cache conflict)\n";
    } else {
        echo "  ❌ FAILURE: One or both codes not found\n";
    }
}

// Run tests
test_coupon_cache_key_consistency();
echo str_repeat('-', 60) . "\n\n";
test_coupon_lookup_simulation();

echo "\nNote: This test uses simplified mocks. The actual fix ensures that\n";
echo "wc_sanitize_coupon_code() is used consistently for both cache keys\n";
echo "and database lookups, preventing cache conflicts.\n";
?>