<?php
/**
 * Example usage of the modified get_woocommerce_log_sources function
 */

// Include the function file
require_once 'get_woocommerce_log_sources.php';

// Example 1: Basic usage
echo "=== Basic Usage ===\n";
$sources = get_woocommerce_log_sources();

if ( $sources === null ) {
    echo "Error: Unknown logging handler or system error\n";
} elseif ( empty( $sources ) ) {
    echo "No log sources found or logging is disabled\n";
} else {
    echo "Found " . count( $sources ) . " log sources:\n";
    foreach ( $sources as $source ) {
        echo "- " . $source . "\n";
    }
}

echo "\n";

// Example 2: Comprehensive logging info
echo "=== Comprehensive Logging Info ===\n";
$info = get_woocommerce_logging_info();

echo "Logging Enabled: " . ( $info['logging_enabled'] ? 'Yes' : 'No' ) . "\n";
echo "Handler Class: " . $info['handler'] . "\n";
echo "Handler Type: " . $info['handler_type'] . "\n";

if ( $info['error'] ) {
    echo "Error: " . $info['error'] . "\n";
} else {
    echo "Sources Count: " . count( $info['sources'] ) . "\n";
    if ( ! empty( $info['sources'] ) ) {
        echo "Sources: " . implode( ', ', $info['sources'] ) . "\n";
    }
}

echo "\n";

// Example 3: Check specific source
echo "=== Check Specific Source ===\n";
$test_source = 'plugin-woocommerce';
if ( woocommerce_log_source_exists( $test_source ) ) {
    echo "Source '$test_source' exists\n";
} else {
    echo "Source '$test_source' does not exist\n";
}

echo "\n";

// Example 4: Get source count
echo "=== Source Count ===\n";
$count = get_woocommerce_log_sources_count();
if ( $count === null ) {
    echo "Error getting source count\n";
} else {
    echo "Total sources: " . $count . "\n";
}

echo "\n";

// Example 5: Simple wrapper (backward compatibility)
echo "=== Simple Wrapper (Backward Compatibility) ===\n";
$simple_sources = get_woocommerce_log_sources_simple();
echo "Sources (simple): " . implode( ', ', $simple_sources ) . "\n";

echo "\n";

// Example 6: Handle different return types
echo "=== Handling Different Return Types ===\n";
$sources = get_woocommerce_log_sources();

switch ( true ) {
    case $sources === null:
        echo "Status: Error - Unknown handler or system error\n";
        break;
        
    case empty( $sources ):
        echo "Status: No sources found or logging disabled\n";
        break;
        
    default:
        echo "Status: Success - Found " . count( $sources ) . " sources\n";
        break;
}