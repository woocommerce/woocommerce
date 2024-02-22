<?php

namespace QIT;

use QM_Collectors;

class QM_Summary {

	private static $instance            = null;
	protected string $plugins_dir       = 'wp-content/plugins';
	protected $js_asset_size_threshold  = 1 * KB_IN_BYTES;
	protected $css_asset_size_threshold = 1 * KB_IN_BYTES;

	public function __construct() {
		$request_uri    = $_SERVER['REQUEST_URI'];
		$is_api_request = strpos( $request_uri, 'wp-json' ) !== false;

		// Skip collection on routes registered by the QIT QM plugin
		if ( strpos( $request_uri, 'qm/v1' ) !== false ) {
			return;
		}

		if ( $is_api_request ) {
			add_action( 'shutdown', array( $this, 'generate_api_summary' ) );
		} else {
			add_filter( 'qm/outputter/html', [ $this, 'generate_browser_summary' ], 999, 2 );
			add_action( 'shutdown', [ $this, 'capture_resources' ] );
		}
	}

	public static function  init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
	}

	public function fetch_db_queries(): array {
		$summary = [
			'total_time'        => 0,
			'total'             => 0,
			'types'             => [],
			'query_threshold'   => 0.01,
			'expensive_queries' => [],
		];

		$collector   = QM_Collectors::get( 'db_queries' );
		$data        = $collector->get_data();
		$query_types = [];

		if ( empty( $data->rows ) ) {
			return $summary;
		}

		if ( ! empty( $data->types ) ) {
			$query_types = $data->types;
		}

		$summary['total']      = $data->total_qs;
		$summary['total_time'] = $data->total_time;

		if ( count( $query_types ) > 0 ) {
			foreach (  $query_types as $type => $count ) {

				$summary['types'][] = [
					'type'  => $type,
					'count' => $count
				];
			}
		}

		$expensive_queries = $data->expensive;

		// Uncomment to see the structure of the $expensive_queries array
		// $expensive_queries = $data->rows;

		foreach ( $expensive_queries as $expensive ) {
			$summary['expensive_queries'][] = [
				'sql'       => $expensive['sql'],
				'time'      => $expensive['ltime'],
				'caller'    => $expensive['caller']
			];
		}

		return $summary;
	}

	public function fetch_request_summary(): array {
		$summary = [
			'total_time'     => 0,
			'peak_memory'    => 0,
			'memory_percent' => 0,
		];

		$collector = QM_Collectors::get( 'overview' );
		$data      = $collector->get_data();

		$summary['time']           = $data->time_taken;
		$summary['peak_memory']    = $data->memory;
		$summary['memory_percent'] = $data->memory_usage;

		return $summary;
	}

	public function fetch_http_calls(): array {
		$summary = [
			'total'                    => 0,
			'time'                     => 0,
			'expensive_call_threshold' => 0.1,
			'expensive_calls'          => []
		];

		$collector = QM_Collectors::get( 'http' );
		$data      = $collector->get_data();

		if ( empty( $data->http ) ) {
			return $summary;
		}

		$summary['total'] = count( $data->http );
		$summary['time']  = $data->ltime;

		foreach ( $data->http as $http ) {
			if ( $http['ltime'] > $summary['expensive_call_threshold'] ) {
				$summary['expensive_calls'][] = [
					'method'  => $http['args']['method'],
					'url'     => ! empty( $http['redirected_tp' ] ) ? $http['redirected_tp' ] : $http['url'],
					'time'    => $http['ltime'],
					'type'    => $http['type'],
					'size'    => $http['info']['size_download'] ?? 0,
					'timeout' => $http['args']['timeout']
				];
			}
		}

		return $summary;
	}

	public function fetch_options_summary(): array {
		$summary = [
			'total_size'      => 0,
			'total_size_comp' => 0,
			'threshold'       => MB_IN_BYTES,
			'large_options'   => []
		];

		$collector = QM_Collectors::get( 'alloptions' );
		$data      = $collector->get_data();

		if ( empty( $data->options ) ) {
			return $summary;
		}

		$summary['total_size']      = $data->total_size;
		$summary['total_size_comp'] = $data->total_size_comp;

		foreach ( $data->options as $option ) {
			if ( $option->size > $summary['threshold'] ) {
				$summary['large_options'][] = [
					'name' => $option->name,
					'size' => $option->size
				];
			}
		}

		return $summary;
	}

	public function fetch_hooks_summary(): array {
		$summary = [
			'total' => 0,
			'hooks' => []
		];

		$collector   = QM_Collectors::get( 'hooks' );
		$data        = $collector->get_data();

		if ( empty( $data->hooks ) ) {
			return $summary;
		}

		$summary['total'] = count( $data->hooks );

		foreach ( $data->hooks as $hook ) {
			$h = [
				'name'      => $hook['name'],
				'callbacks' => [],
			];

			if ( count( $hook['actions'] ) > 0 ) {
				foreach ( $hook['actions'] as $action ) {
					$callback = $action['callback'];
					// Filter only hooks registered in the plugins directory.
					if ( strpos( $callback['file'], $this->plugins_dir ) !== false ) {
						$h['callbacks'][] = [
							'name'	    => $callback['name'],
							'file'       => $callback['file'],
							'component' => $callback['component'],
							'priority'  => $action['priority']
						];
					}
				}

				$summary['hooks'][] = $h;
			}
		}

		return $summary;
	}

	public function fetch_resources_summary(): array {
		$summary   = [
			'scripts'    => [],
			'styles'     => [],
			'total'      => 0,
			'total_size' => 0
		];

		$scripts_collector = QM_Collectors::get( 'assets_scripts' );
		$styles_collector  = QM_Collectors::get( 'assets_styles' );

		$scripts_data = $scripts_collector->get_data();
		$styles_data  = $styles_collector->get_data();

		if ( ! empty( $scripts_data->assets ) ) {
			$summary['scripts']    = $this->asset_summary( $scripts_data, 'scripts' );
			$summary['total']      += $summary['scripts']['total'];
			$summary['total_size'] += $summary['scripts']['total_size'];
		}

		if ( ! empty( $styles_data->assets ) ) {
			$summary['styles']     = $this->asset_summary( $styles_data, 'styles' );
			$summary['total']      += $summary['styles']['total'];
			$summary['total_size'] += $summary['styles']['total_size'];
		}


		return $summary;
	}

	public function asset_summary( $asset_data, $type ): array {
		$summary  = [
			'total'        => 0,
			'total_size'   => 0,
			'threshold'    => $type === 'scripts' ? $this->js_asset_size_threshold : $this->css_asset_size_threshold,
			'large_assets' => [],
		];
		$positions = [
			'header',
			'footer',
		];

		foreach ( $positions as $position ) {

			$assets = $asset_data->assets[ $position ];

			foreach ( $assets as $handle => $data ) {

				if ( strpos( $data['source'], $this->plugins_dir ) !== false ) {

					$path = ABSPATH . '/' .  $data['display'];
					$size = file_exists( $path ) ? filesize( $path ) : 'File not found';

					if ( $size > $summary['threshold'] ) {
						$summary['large_assets'][] = [
							'name'     => $handle,
							'source'   => $data['display'],
							'size'     => $size,
							'position' => $position
						];
					}

					$summary['total']++;
					$summary['total_size'] += $size;
				}
			}
		}

		return $summary;
	}

	public function generate_summary( string $path, bool $is_api = false ) {
		$request_summary   = $this->fetch_request_summary();
		$db_summary        = $this->fetch_db_queries();
		$http_summary      = $this->fetch_http_calls();
		$options_summary   = $this->fetch_options_summary();
		$hooks_summary     = $this->fetch_hooks_summary();
		$resources_summary = $is_api ? [] : $this->fetch_resources_summary();
		$route			   = $_SERVER['REQUEST_URI'];
 
		$key         = time();
		$route_group = $this->get_route_group( $route );
		$sub_dir     = $this->base64url_encode( $route_group );
		$file_path   = sprintf( "%s/%s/%s.json", $path, $sub_dir, $key );

		$summary = [
			'route_group'        => $route_group,
			'route'              => $route,
			'request_overview'   => $request_summary,
			'db'                 => $db_summary,
			'http'               => $http_summary,
			'autoloaded_options' => $options_summary,
			// 'hooks'              => $hooks_summary,
			'resources_summary'  => $resources_summary
		];

		$this->write_file( $file_path, json_encode( $summary, JSON_PRETTY_PRINT ) );
	}

	public function generate_browser_summary( array $output, QM_Collectors $collectors ): array {
		$dir_path = __DIR__ . '/results/ui';
		$this->generate_summary( $dir_path );
		return $output;
	}

	public function generate_api_summary() {
		$dir_path = __DIR__ . '/results/api';
		$this->generate_summary( $dir_path, true );
	}

	public function write_file( $path, $contents ) {
		$dir_path = dirname( $path );

		if ( ! file_exists( $dir_path ) ) {
			mkdir( $dir_path, 0777, true );
		}

		file_put_contents( $path, $contents );
	}

	public function get_route_group( string $route ): string {
		return preg_replace('/\d+/', ':id', $route);
	}

	function base64url_encode( string $url ): string {
		return rtrim( strtr( base64_encode( $url ), '+/', '-_' ), '=' );
	}
	  
	public function base64url_decode( string $url ): string {
		return base64_decode( str_pad( strtr( $url, '-_', '+/' ), strlen( $url ) % 4, '=', STR_PAD_RIGHT ) );
	}
}
