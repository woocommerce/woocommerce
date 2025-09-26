<?php
/**
 * Get all available log sources from WooCommerce logging systems
 * 
 * This function checks which logging system is enabled and retrieves
 * the appropriate list of sources. Returns null if an unknown handler is in use.
 *
 * @return array|null Array of unique log sources, or null if unknown handler
 */
function get_woocommerce_log_sources() {
    try {
        // Check if WooCommerce is available
        if ( ! function_exists( 'wc_get_container' ) ) {
            return null;
        }
        
        // Get the logging settings
        $settings = wc_get_container()->get( 
            \Automattic\WooCommerce\Internal\Admin\Logging\Settings::class 
        );
        
        // Check if logging is enabled
        if ( ! $settings->logging_is_enabled() ) {
            return array();
        }
        
        // Get the current handler
        $handler = $settings->get_default_handler();
        
        // Handle different logging systems
        switch ( $handler ) {
            case \Automattic\WooCommerce\Internal\Admin\Logging\LogHandlerFileV2::class:
                // New file-based logging system
                return get_file_log_sources_v2();
                
            case \WC_Log_Handler_File::class:
                // Legacy file-based logging system
                return get_file_log_sources_legacy();
                
            case \WC_Log_Handler_DB::class:
                // Database logging system
                return get_database_log_sources();
                
            case \WC_Log_Handler_Email::class:
                // Email logging - no persistent sources to retrieve
                return array();
                
            default:
                // Unknown handler
                return null;
        }
        
    } catch ( Exception $e ) {
        // Error occurred, return null to indicate failure
        return null;
    }
}

/**
 * Get log sources from the new file-based logging system (FileV2)
 *
 * @return array Array of unique log sources
 */
function get_file_log_sources_v2() {
    try {
        $file_controller = wc_get_container()->get( 
            \Automattic\WooCommerce\Internal\Admin\Logging\FileV2\FileController::class 
        );
        
        $sources = $file_controller->get_file_sources();
        
        if ( is_wp_error( $sources ) ) {
            return array();
        }
        
        return array_unique( $sources );
        
    } catch ( Exception $e ) {
        return array();
    }
}

/**
 * Get log sources from the legacy file-based logging system
 *
 * @return array Array of unique log sources
 */
function get_file_log_sources_legacy() {
    try {
        $log_directory = \Automattic\WooCommerce\Internal\Admin\Logging\Settings::get_log_directory();
        $log_files = glob( $log_directory . '*.log' );
        
        if ( false === $log_files ) {
            return array();
        }
        
        $sources = array();
        foreach ( $log_files as $file_path ) {
            $filename = basename( $file_path, '.log' );
            
            // Legacy file format: source-timestamp.log or source.log
            // Extract source from filename
            if ( preg_match( '/^([^-]+)/', $filename, $matches ) ) {
                $sources[] = $matches[1];
            }
        }
        
        return array_unique( $sources );
        
    } catch ( Exception $e ) {
        return array();
    }
}

/**
 * Get log sources from the database logging system
 *
 * @return array Array of unique log sources
 */
function get_database_log_sources() {
    global $wpdb;
    
    // First try to get cached sources
    $cached_sources = get_option( 'woocommerce_status_log_db_sources', null );
    if ( is_array( $cached_sources ) ) {
        return $cached_sources;
    }
    
    // If cache is empty or invalid, query the database
    $sql = "
        SELECT DISTINCT source
        FROM {$wpdb->prefix}woocommerce_log
        WHERE source != ''
        ORDER BY source ASC
    ";
    
    $sources = $wpdb->get_col( $sql );
    
    if ( is_array( $sources ) ) {
        // Cache the results for performance
        update_option( 'woocommerce_status_log_db_sources', $sources, true );
        return $sources;
    }
    
    return array();
}

/**
 * Get comprehensive logging status and sources
 *
 * @return array Array containing logging status and sources
 */
function get_woocommerce_logging_info() {
    $info = array(
        'logging_enabled' => false,
        'handler' => null,
        'handler_type' => null,
        'sources' => array(),
        'error' => null
    );
    
    try {
        $settings = wc_get_container()->get( 
            \Automattic\WooCommerce\Internal\Admin\Logging\Settings::class 
        );
        
        $info['logging_enabled'] = $settings->logging_is_enabled();
        $info['handler'] = $settings->get_default_handler();
        
        if ( $info['logging_enabled'] ) {
            // Determine handler type
            switch ( $info['handler'] ) {
                case \Automattic\WooCommerce\Internal\Admin\Logging\LogHandlerFileV2::class:
                    $info['handler_type'] = 'file_system_v2';
                    break;
                case \WC_Log_Handler_File::class:
                    $info['handler_type'] = 'file_system_legacy';
                    break;
                case \WC_Log_Handler_DB::class:
                    $info['handler_type'] = 'database';
                    break;
                case \WC_Log_Handler_Email::class:
                    $info['handler_type'] = 'email';
                    break;
                default:
                    $info['handler_type'] = 'unknown';
            }
            
            // Get sources based on handler type
            $sources = get_woocommerce_log_sources();
            if ( $sources !== null ) {
                $info['sources'] = $sources;
            } else {
                $info['error'] = 'Unknown logging handler: ' . $info['handler'];
            }
        }
        
    } catch ( Exception $e ) {
        $info['error'] = $e->getMessage();
    }
    
    return $info;
}

/**
 * Simple wrapper function for backward compatibility
 *
 * @return array Array of log sources (empty array if none found or disabled)
 */
function get_woocommerce_log_sources_simple() {
    $sources = get_woocommerce_log_sources();
    return $sources === null ? array() : $sources;
}

/**
 * Check if a specific log source exists
 *
 * @param string $source The source to check for
 * @return bool True if source exists, false otherwise
 */
function woocommerce_log_source_exists( $source ) {
    $sources = get_woocommerce_log_sources();
    return $sources !== null && in_array( $source, $sources, true );
}

/**
 * Get the count of available log sources
 *
 * @return int|null Number of sources, or null if error
 */
function get_woocommerce_log_sources_count() {
    $sources = get_woocommerce_log_sources();
    return $sources === null ? null : count( $sources );
}

// Example usage and testing
if ( defined( 'WP_CLI' ) && WP_CLI ) {
    // CLI usage example
    $sources = get_woocommerce_log_sources();
    
    if ( $sources === null ) {
        echo "Error: Unknown logging handler or logging system error\n";
    } elseif ( empty( $sources ) ) {
        echo "No log sources found or logging is disabled\n";
    } else {
        echo "Found " . count( $sources ) . " log sources:\n";
        foreach ( $sources as $source ) {
            echo "- " . $source . "\n";
        }
    }
    
    // Get comprehensive info
    $info = get_woocommerce_logging_info();
    echo "\nLogging Status:\n";
    echo "Enabled: " . ( $info['logging_enabled'] ? 'Yes' : 'No' ) . "\n";
    echo "Handler: " . $info['handler'] . "\n";
    echo "Type: " . $info['handler_type'] . "\n";
    if ( $info['error'] ) {
        echo "Error: " . $info['error'] . "\n";
    }
}