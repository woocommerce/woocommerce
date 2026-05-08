<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\SiteHealth\Checks;

defined( 'ABSPATH' ) || exit;

/**
 * Scans the active theme's WooCommerce template overrides for outdated versions.
 *
 * @internal
 */
class TemplateOverrideScanner {

	public const ID = 'outdated_templates';

	public function get_id(): string {
		return 'woocommerce_' . self::ID;
	}

	public function get_label(): string {
		return __( 'Woo: WooCommerce template overrides', 'woocommerce' );
	}

	public function is_async(): bool {
		return true;
	}

	public function run(): array {
		try {
			$outdated = $this->find_outdated_overrides();
		} catch ( \Throwable $e ) {
			return $this->error_result( $e );
		}

		if ( ! empty( $outdated ) ) {
			$list = '';
			foreach ( array_slice( $outdated, 0, 5 ) as $entry ) {
				$list .= '<li>' . esc_html( $entry['relative'] ) . ' — ' .
					esc_html(
						sprintf(
							/* translators: 1: theme override version 2: core template version */
							__( 'theme: %1$s, core: %2$s', 'woocommerce' ),
							$entry['theme'],
							$entry['core']
						)
					) . '</li>';
			}
			return $this->finish( array(
				'label'       => __( 'Woo: WooCommerce has outdated template overrides', 'woocommerce' ),
				'status'      => 'recommended',
				'badge'       => array( 'label' => __( 'WooCommerce', 'woocommerce' ), 'color' => 'orange' ),
				'description' => '<p>' . esc_html__( 'These template files in your theme are at least two minor versions behind their core counterparts and may produce visual or functional issues:', 'woocommerce' ) . '</p><ul>' . $list . '</ul>',
				'actions'     => sprintf(
					'<p><a href="%s">%s</a></p>',
					esc_url( admin_url( 'admin.php?page=wc-status' ) ),
					esc_html__( 'View full template report', 'woocommerce' )
				),
			) );
		}

		return $this->finish( array(
			'label'       => __( 'Woo: WooCommerce template overrides are up to date', 'woocommerce' ),
			'status'      => 'good',
			'badge'       => array( 'label' => __( 'WooCommerce', 'woocommerce' ), 'color' => 'green' ),
			'description' => '<p>' . esc_html__( 'No outdated WooCommerce template overrides were found.', 'woocommerce' ) . '</p>',
			'actions'     => '',
		) );
	}

	/**
	 * @return array<int, array{relative:string, theme:string, core:string}>
	 */
	public function find_outdated_overrides(): array {
		$theme_dir = (string) apply_filters(
			'woocommerce_site_health_check_outdated_templates_scan_path',
			get_stylesheet_directory() . '/woocommerce/'
		);
		if ( ! is_dir( $theme_dir ) ) {
			return array();
		}
		$core_dir = WC()->plugin_path() . '/templates/';
		$iter     = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $theme_dir ) );
		$outdated = array();
		foreach ( $iter as $file ) {
			if ( ! $file->isFile() || 'php' !== strtolower( $file->getExtension() ) ) {
				continue;
			}
			$relative  = ltrim( str_replace( $theme_dir, '', $file->getPathname() ), '/' );
			$core_path = $core_dir . $relative;
			if ( ! file_exists( $core_path ) ) {
				continue;
			}
			$theme_v = $this->read_version( $file->getPathname() );
			$core_v  = $this->read_version( $core_path );
			if ( $this->is_two_minors_behind( $theme_v, $core_v ) ) {
				$outdated[] = array(
					'relative' => $relative,
					'theme'    => $theme_v,
					'core'     => $core_v,
				);
			}
		}
		return $outdated;
	}

	private function read_version( string $path ): string {
		$contents = file_get_contents( $path, false, null, 0, 4096 );
		if ( false === $contents ) {
			return '0';
		}
		if ( preg_match( '/@version\s+([0-9][0-9a-zA-Z.\-]*)/i', $contents, $matches ) ) {
			return $matches[1];
		}
		return '0';
	}

	private function is_two_minors_behind( string $theme_version, string $core_version ): bool {
		$theme = array_pad( array_map( 'intval', explode( '.', $theme_version ) ), 2, 0 );
		$core  = array_pad( array_map( 'intval', explode( '.', $core_version ) ),  2, 0 );
		if ( $theme[0] < $core[0] ) {
			return true;
		}
		if ( $theme[0] > $core[0] ) {
			return false;
		}
		return ( $core[1] - $theme[1] ) >= 2;
	}

	private function finish( array $base ): array {
		$base['test'] = $this->get_id();
		if ( ! apply_filters( 'woocommerce_site_health_check_' . self::ID . '_enabled', true ) ) {
			return array();
		}
		return (array) apply_filters( 'woocommerce_site_health_check_' . self::ID . '_result', $base );
	}

	private function error_result( \Throwable $e ): array {
		if ( function_exists( 'wc_get_logger' ) ) {
			wc_get_logger()->error( $e->getMessage(), array( 'source' => 'site-health' ) );
		}
		return $this->finish( array(
			'label'       => __( 'Woo: WooCommerce could not run the template overrides check', 'woocommerce' ),
			'status'      => 'recommended',
			'badge'       => array( 'label' => __( 'WooCommerce', 'woocommerce' ), 'color' => 'gray' ),
			'description' => '<p>' . esc_html__( 'WooCommerce was unable to scan template overrides. Check the site error logs.', 'woocommerce' ) . '</p>',
			'actions'     => '',
		) );
	}
}
