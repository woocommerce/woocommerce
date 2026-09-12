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

	/**
	 * Return the prefixed test slug used by WordPress Site Health.
	 *
	 * @return string The Site Health test id.
	 */
	public function get_id(): string {
		return 'woocommerce_' . self::ID;
	}

	/**
	 * Return the human-readable label for this check.
	 *
	 * @return string The human-readable check title.
	 */
	public function get_label(): string {
		return __( 'WooCommerce template overrides', 'woocommerce' );
	}

	/**
	 * Whether this check runs asynchronously.
	 *
	 * @return bool Whether this check runs asynchronously.
	 */
	public function is_async(): bool {
		return true;
	}

	/**
	 * Run the check.
	 *
	 * @return array WP Site Health result array.
	 */
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
			return $this->finish(
				array(
					'label'       => __( 'WooCommerce has outdated template overrides', 'woocommerce' ),
					'status'      => 'recommended',
					'badge'       => array(
						'label' => __( 'WooCommerce', 'woocommerce' ),
						'color' => 'orange',
					),
					'description' => '<p>' . esc_html__( 'These template files in your theme are at least two minor versions behind their core counterparts and may produce visual or functional issues:', 'woocommerce' ) . '</p><ul>' . $list . '</ul>',
					'actions'     => sprintf(
						'<p><a href="%s">%s</a></p>',
						esc_url( admin_url( 'admin.php?page=wc-status' ) ),
						esc_html__( 'View full template report', 'woocommerce' )
					),
				)
			);
		}

		return $this->finish(
			array(
				'label'       => __( 'WooCommerce template overrides are up to date', 'woocommerce' ),
				'status'      => 'good',
				'badge'       => array(
					'label' => __( 'WooCommerce', 'woocommerce' ),
					'color' => 'green',
				),
				'description' => '<p>' . esc_html__( 'No outdated WooCommerce template overrides were found.', 'woocommerce' ) . '</p>',
				'actions'     => '',
			)
		);
	}

	/**
	 * Scan theme template overrides and return those that are outdated.
	 *
	 * @return array<int, array{relative:string, theme:string, core:string}>
	 */
	public function find_outdated_overrides(): array {
		/**
		 * Filters the directory scanned for WooCommerce template overrides.
		 *
		 * @since 11.0.0
		 *
		 * @param string $scan_path The absolute path to the theme's WooCommerce template directory.
		 */
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

	/**
	 * Read the @version header from a template file.
	 *
	 * @param string $path Absolute path to the template file.
	 * @return string The version string, or '0' when none is found.
	 */
	private function read_version( string $path ): string {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading a local template file header, not a remote URL; wp_remote_get() is not applicable.
		$contents = file_get_contents( $path, false, null, 0, 4096 );
		if ( false === $contents ) {
			return '0';
		}
		if ( preg_match( '/@version\s+([0-9][0-9a-zA-Z.\-]*)/i', $contents, $matches ) ) {
			return $matches[1];
		}
		return '0';
	}

	/**
	 * Determine whether the theme version is at least two minor versions behind core.
	 *
	 * @param string $theme_version The version found in the theme override.
	 * @param string $core_version  The version found in the core template.
	 * @return bool Whether the theme override is two or more minor versions behind.
	 */
	private function is_two_minors_behind( string $theme_version, string $core_version ): bool {
		$theme = array_pad( array_map( 'intval', explode( '.', $theme_version ) ), 2, 0 );
		$core  = array_pad( array_map( 'intval', explode( '.', $core_version ) ), 2, 0 );
		if ( $theme[0] < $core[0] ) {
			return true;
		}
		if ( $theme[0] > $core[0] ) {
			return false;
		}
		return ( $core[1] - $theme[1] ) >= 2;
	}

	/**
	 * Apply enable/result filters and finalize.
	 *
	 * @param array $base Base result array (without the 'test' key).
	 * @return array Filtered result, or empty array when the check is disabled.
	 */
	private function finish( array $base ): array {
		$base['test'] = $this->get_id();
		/**
		 * Filters whether this Site Health check is enabled.
		 *
		 * @since 11.0.0
		 *
		 * @param bool $enabled Whether the check is enabled.
		 */
		if ( ! apply_filters( 'woocommerce_site_health_check_' . self::ID . '_enabled', true ) ) {
			return array();
		}
		/**
		 * Filters the result array returned by this Site Health check.
		 *
		 * @since 11.0.0
		 *
		 * @param array $base The Site Health result array.
		 */
		return (array) apply_filters( 'woocommerce_site_health_check_' . self::ID . '_result', $base );
	}

	/**
	 * Build an error result when the check itself throws.
	 *
	 * @param \Throwable $e The caught exception.
	 * @return array WP Site Health result array.
	 */
	private function error_result( \Throwable $e ): array {
		if ( function_exists( 'wc_get_logger' ) ) {
			wc_get_logger()->error( $e->getMessage(), array( 'source' => 'site-health' ) );
		}
		return $this->finish(
			array(
				'label'       => __( 'WooCommerce could not run the template overrides check', 'woocommerce' ),
				'status'      => 'recommended',
				'badge'       => array(
					'label' => __( 'WooCommerce', 'woocommerce' ),
					'color' => 'gray',
				),
				'description' => '<p>' . esc_html__( 'WooCommerce was unable to scan template overrides. Check the site error logs.', 'woocommerce' ) . '</p>',
				'actions'     => '',
			)
		);
	}
}
