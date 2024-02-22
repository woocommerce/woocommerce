<?php

use WP_REST_Request;

if ( ! class_exists( 'QM_Result_Summary' ) ) {
    class QM_Result_Summary {
        protected string $endpoint                    = 'result-summary';
        protected static ?QM_Result_Summary $instance = null;

        public static function instance() {

            if ( is_null( self::$instance ) ) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        public function init() {
            add_action( 'rest_api_init', [ $this, 'register_route' ] );
        }

        public function register_route() {
            register_rest_route( 
                'qm/v1', 
                $this->endpoint, 
                [
                    'methods'             => 'GET',
                    'callback'            => [ $this, 'get_summary' ],
                    'permission_callback' => '__return_true'
                ] 
            );
        }

        public function base64url_decode( string $url ): string {
            return base64_decode( str_pad( strtr( $url, '-_', '+/' ), strlen( $url ) % 4, '=', STR_PAD_RIGHT ) );
        }
        
        
        public function aggregate_data( string $type ): array {
            $root_dir    = __DIR__ . '/../results/' .  $type; // Change this to your directory path
            $directories = new DirectoryIterator( $root_dir );
            $all_data    = [];
            
            foreach ( $directories as $dir ) {
                if ( $dir->isDot() || !$dir->isDir() ) {
                    continue; // Skip non-directories and dot references
                }
            
                $dir_name         = $dir->getFilename();
                $key              = $this->base64url_decode( $dir_name );
                $all_data[ $key ] = []; // Initialize an array for this directory
            
                // Iterate over the JSON files in the current directory
                $json_files = new FilesystemIterator( $dir->getPathname(), FilesystemIterator::SKIP_DOTS );

                foreach ( $json_files as $file ) {
                    if ( $file->isFile() && $file->getExtension() === 'json' ) {
                        $json_content = file_get_contents( $file->getPathname() );
                        $json_data    = json_decode( $json_content, true ); // Decode the JSON content as an associative array
            
                        if ( $json_data ) { // If decoding was successful, add it to the array
                            $all_data[ $key ][] = $json_data;
                        }
                    }
                }
            }

            return $all_data;
        }

        public function summarize( array $data ): array {
            $summarized_data = [];
            foreach ( $data as $route_group => $entries ) {
                $metrics = [
                    'request_overview'   => [ 'time' => [], 'peak_memory' => [], 'memory_percent' => [] ],
                    'db'                 => [ 'total_time' => [], 'total' => [] ],
                    'http'               => [ 'total' => [], 'time' => [] ],
                    'autoloaded_options' => [ 'total_size' => [] ],
                    'resources_summary'  => [
                        'scripts' => [ 'total_size' => [] ],
                        'styles'  => [ 'total_size' => [] ]
                    ],
                ];
            
                $expensive_queries    = [];
                $expensive_calls      = [];
                $large_options        = [];
                $scripts_large_assets = [];
                $styles_large_assets  = [];
            
                // Collect values for each metric and aggregate arrays
                foreach ( $entries as $entry ) {
                    foreach ( $metrics as $category => &$metric_group ) {

                        if ( isset( $metric_group['total_size'] ) && $category == 'resource_summary' ) { // For resources_summary
                            foreach ( $entry['resources_summary'] as $resource_type => $resource_data ) {
                                
                                $metric_group[ $resource_type ]['total_size'][] = $resource_data['total_size'];

                                if ($resource_type === 'scripts') {
                                    $scripts_large_assets = array_merge( $scripts_large_assets, $resource_data['large_assets'] );
                                } elseif ($resource_type === 'styles') {
                                    $styles_large_assets = array_merge( $styles_large_assets, $resource_data['large_assets'] );
                                }
                            }
                        } else { // For other metrics
                            foreach ( $metric_group as $metric => &$values ) {
                                if ( isset( $entry[ $category ][ $metric ] ) ) {
                                    $values[] = $entry[$category][$metric];
                                }
                            }
                        }
                    }
            
                    $expensive_queries = array_merge( $expensive_queries, $entry['db']['expensive_queries'] ?? [] );
                    $expensive_calls   = array_merge( $expensive_calls, $entry['http']['expensive_calls'] ?? [] );
                    $large_options     = array_merge( $large_options, $entry['autoloaded_options']['large_options'] ?? [] );
                }
        
                // Calculate p95 for each metric
                foreach ( $metrics as $category => &$metric_group ) {
                    foreach ( $metric_group as $metric => &$values ) {
                        if ( is_array( $values ) ) {
                            sort( $values );
                            $index  = ceil( 0.95 * count( $values ) ) - 1;
                            $values = $values[ $index ] ?? null;
                        }
                    }
                }
            
                // Construct the summary object
                $summarized_data[ $route_group ] = [
                    'p95' => $metrics,
                    'expensive_queries' => $expensive_queries,
                    'expensive_calls'   => $expensive_calls,
                    'large_options'     => $large_options,
                    'resource_summary'  => [
                        'scripts' => [ 'large_assets' => $this->unique_array( $scripts_large_assets ) ],
                        'styles'  => [ 'large_assets' => $this->unique_array( $styles_large_assets ) ]
                    ],
                    'num_requests_summarized' => count( $entries )
                ];
            }

            return $summarized_data;
        }

        public function unique_array( array $original_array ): array {
            $temp_array   = []; // Initialize temp_array
            $unique_array = [];

            foreach ( $original_array as $element ) {
                $serialized_element                = serialize( $element );
                $temp_array[ $serialized_element ] = true; // Use serialized string as key
            }
        
            foreach ( array_keys( $temp_array ) as $serialized_element ) {
                $unique_array[] = unserialize( $serialized_element );
            }
        
            return $unique_array;
        }
        

        public function get_summary() {
            $type = 'ui';

            if ( 
                isset( $_GET['type'] ) &&
                in_array( $_GET['type'], [ 'ui', 'api' ] )
            ) {
                $type = $_GET['type'];
            }

            $data    = $this->aggregate_data( $type );
            $summary = $this->summarize( $data );
            return $summary;
        }
    }
}

QM_Result_Summary::instance()->init();