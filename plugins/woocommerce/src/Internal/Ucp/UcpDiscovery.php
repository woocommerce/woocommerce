<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Ucp;

use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\StoreApi\Utilities\Ucp\Utils;

/**
 * Serves the UCP discovery profile at `/.well-known/ucp`.
 *
 * @internal Just for internal use.
 */
final class UcpDiscovery implements RegisterHooksInterface {

	/**
	 * Query var set by the `/.well-known/ucp` rewrite rule.
	 */
	public const QUERY_VAR = 'wc_ucp_discovery';

	/**
	 * Rewrite rule regex for `/.well-known/ucp`.
	 */
	public const RULE = '^\.well-known/ucp/?$';

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_action( FeaturesController::FEATURE_ENABLED_CHANGED_ACTION, array( $this, 'maybe_flush_rewrite_rules' ), 10, 2 );
		add_action( 'init', array( $this, 'register_feature_hooks' ), 0, 0 );
	}

	/**
	 * Register the renderer, plus the rewrite rule when pretty permalinks are on, when UCP is enabled.
	 */
	public function register_feature_hooks(): void {
		global $wp_rewrite;

		$enabled = Utils::is_enabled();
		$active  = $enabled && $wp_rewrite->using_permalinks();

		if ( $enabled ) {
			add_action( 'parse_request', array( $this, 'maybe_render' ), 0 );
			add_action( 'admin_init', array( $this, 'maybe_sync_file' ) );
			add_action( 'admin_notices', array( $this, 'maybe_render_notice' ) );
		}

		if ( $active ) {
			add_rewrite_rule( self::RULE, 'index.php?' . self::QUERY_VAR . '=1', 'top' );
			add_filter( 'query_vars', array( $this, 'add_query_var' ), 0 );
		}
	}

	/**
	 * Register the discovery query var.
	 *
	 * @param array $vars Public query vars.
	 * @return array
	 */
	public function add_query_var( $vars ): array {
		$vars[] = self::QUERY_VAR;
		return $vars;
	}

	/**
	 * Queue a rewrite rules flush when the UCP feature is toggled, and remove the static file when it is turned off.
	 *
	 * @param string $feature_id Feature being toggled.
	 * @param bool   $is_enabled Whether the feature is now enabled.
	 */
	public function maybe_flush_rewrite_rules( string $feature_id, bool $is_enabled = true ): void {
		if ( Utils::FEATURE_ID !== $feature_id ) {
			return;
		}

		update_option( 'woocommerce_queue_flush_rewrite_rules', 'yes' );

		$path = $this->get_file_path();
		if ( ! $is_enabled && $this->is_deletable( $path ) ) {
			wp_delete_file( $path );
		}
	}

	/**
	 * Keep the static discovery file in sync on admin screens: write it under plain permalinks, delete it under pretty ones.
	 */
	public function maybe_sync_file(): void {
		global $wp_rewrite;

		// admin_init also fires for anonymous admin-ajax.php/admin-post.php requests and on every heartbeat.
		if ( wp_doing_ajax() || ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$path = $this->get_file_path();
		// phpcs:disable WordPress.WP.AlternativeFunctions
		if ( $wp_rewrite->using_permalinks() ) {
			if ( $this->is_deletable( $path ) ) {
				wp_delete_file( $path );
			}
			return;
		}

		$content  = (string) wp_json_encode( $this->get_profile(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		$dir      = dirname( $path );
		$writable = is_file( $path ) ? is_writable( $path ) : wp_mkdir_p( $dir ) && is_writable( $dir );
		if ( $writable && ! ( is_file( $path ) && file_get_contents( $path ) === $content ) ) {
			file_put_contents( $path, $content );
		}
		// phpcs:enable WordPress.WP.AlternativeFunctions
	}

	/**
	 * Warn store managers when the discovery endpoint is unreachable or shadowed by a stale static file.
	 */
	public function maybe_render_notice(): void {
		global $wp_rewrite;

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$path = $this->get_file_path();
		if ( $wp_rewrite->using_permalinks() ) {
			if ( ! is_file( $path ) || $this->is_deletable( $path ) ) {
				return;
			}
			/* translators: %1$s: discovery file path. */
			$message = __( 'A static %1$s file is overriding the UCP discovery endpoint. Delete it or make its directory writable by the web server.', 'woocommerce' );
		} else {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable
			if ( is_writable( $path ) ) {
				return;
			}
			/* translators: %1$s: discovery file path, %2$s: opening link tag, %3$s: closing link tag. */
			$message = __( 'The UCP discovery endpoint /.well-known/ucp may not be reachable. %2$sEnable pretty permalinks%3$s, or make %1$s writable by the web server.', 'woocommerce' );
		}

		echo wp_kses_post(
			'<div class="notice notice-warning"><p>'
			. sprintf( $message, '<code>' . esc_html( $path ) . '</code>', '<a href="' . esc_url( admin_url( 'options-permalink.php' ) ) . '">', '</a>' )
			. '</p></div>'
		);
	}

	/**
	 * Whether the static file exists and the web server can remove it: unlink needs write access to the directory, not the file.
	 *
	 * @param string $path Absolute file path.
	 * @return bool
	 */
	private function is_deletable( string $path ): bool {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable
		return is_file( $path ) && is_writable( dirname( $path ) );
	}

	/**
	 * Path of the static discovery file served under plain permalinks.
	 *
	 * @return string
	 */
	private function get_file_path(): string {
		/**
		 * Filters the path of the static UCP discovery file written when plain permalinks are active.
		 *
		 * @since 11.4.0
		 *
		 * @param string $path Absolute file path.
		 */
		return (string) apply_filters( 'woocommerce_ucp_discovery_file_path', ABSPATH . '.well-known/ucp' );
	}

	/**
	 * Send the discovery profile for `/.well-known/ucp` requests.
	 *
	 * @param \WP $wp Current WordPress environment instance.
	 */
	public function maybe_render( \WP $wp ): void {
		// Check the matched rule, not only the query var: WP also fills public query vars from $_GET/$_POST.
		$rule_matched = self::RULE === $wp->matched_rule && '1' === ( $wp->query_vars[ self::QUERY_VAR ] ?? '' );
		// Servers that route every path to index.php (nginx try_files) reach WP without a matching rule under plain permalinks.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$path = untrailingslashit( (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH ) );
		if ( ! $rule_matched && wp_parse_url( home_url( '/.well-known/ucp' ), PHP_URL_PATH ) !== $path ) {
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( ! in_array( $_SERVER['REQUEST_METHOD'] ?? '', array( 'GET', 'HEAD' ), true ) ) {
			// RFC 9110 requires a 405 to name the allowed methods.
			if ( ! headers_sent() ) {
				header( 'Allow: GET, HEAD' );
			}
			status_header( 405 );
			exit;
		}

		// UCP requires a publicly cacheable profile with a max-age of at least 60 seconds.
		if ( ! headers_sent() ) {
			header( 'Cache-Control: public, max-age=300' );
		}

		wp_send_json( $this->get_profile() );
	}

	/**
	 * Build the discovery profile.
	 *
	 * @return array
	 */
	public function get_profile(): array {
		return array(
			'ucp'   => array(
				'version'      => Utils::VERSION,
				'services'     => array(
					'dev.ucp.shopping' => array(
						array(
							'version'   => Utils::VERSION,
							'transport' => 'rest',
							'endpoint'  => rest_url( 'wc/ucp/v1' ),
							'spec'      => 'https://ucp.dev/' . Utils::VERSION . '/specification/overview',
							'schema'    => 'https://ucp.dev/' . Utils::VERSION . '/services/shopping/rest.openapi.json',
						),
					),
				),
				'capabilities' => array_map(
					static function ( array $declaration ): array {
						return array( $declaration );
					},
					Utils::capability_declarations()
				),
			),
			'links' => array(
				array(
					'rel'  => 'self',
					'href' => home_url( '/.well-known/ucp' ),
				),
			),
		);
	}
}
