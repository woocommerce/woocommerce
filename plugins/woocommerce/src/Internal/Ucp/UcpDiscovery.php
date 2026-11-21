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
	 * Rel of the profile link identifying WooCommerce; also marks the static file as one WooCommerce wrote.
	 */
	public const LINK_REL = 'woocommerce';

	/**
	 * Href of the WooCommerce profile link.
	 */
	private const LINK_HREF = 'https://developer.woocommerce.com/docs/';

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_action( FeaturesController::FEATURE_ENABLED_CHANGED_ACTION, array( $this, 'maybe_flush_rewrite_rules' ), 10, 2 );
		add_action( 'init', array( $this, 'register_feature_hooks' ), 0, 0 );
	}

	/**
	 * Why `/.well-known/ucp` does or doesn't serve WooCommerce's current profile, from the
	 * permalink setting and the static file, read on every call so nothing is stored.
	 *
	 * Plain permalinks, and structures that start with index.php, don't route that address.
	 * Before UCP is on, a folder WooCommerce can write still counts as ok: turning UCP on writes the file.
	 *
	 * @return string One of `ok`, `no_file`, `file_in_the_way`, `not_readable`, `file_unusable`, `file_outdated`.
	 */
	public static function serve_status(): string {
		$path = self::get_file_path();
		if ( ! is_file( $path ) ) {
			if ( self::rewrite_serves_discovery() ) {
				return 'ok';
			}
			// UCP off and a writable folder: enabling UCP writes the file, so permalinks don't have to change.
			if ( ! Utils::is_enabled() && self::can_create_file( $path ) ) {
				return 'ok';
			}
			return 'no_file';
		}

		if ( ! is_readable( $path ) ) {
			return 'not_readable';
		}

		// The profile is a few KB; cap the read so a large file someone else put there isn't read whole.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$profile = json_decode( (string) file_get_contents( $path, false, null, 0, 65536 ), true );
		if ( ! is_array( $profile ) ) {
			return 'file_unusable';
		}

		$links = $profile['links'] ?? null;
		if ( ! is_array( $links ) || ! in_array( self::LINK_REL, array_column( $links, 'rel' ), true ) ) {
			return 'file_in_the_way';
		}

		// Compare the decoded `ucp` part, not the raw text, so formatting and the ownership link don't matter.
		// The current profile goes through the same JSON round trip, so objects and arrays compare alike.
		$current = json_decode( (string) wp_json_encode( ( new self() )->get_profile()['ucp'] ), true );
		return ( $profile['ucp'] ?? null ) === $current ? 'ok' : 'file_outdated';
	}

	/**
	 * Whether the site is hosted on WordPress.com, the same test as Jetpack's Host::is_atomic_platform().
	 */
	public static function is_wordpress_com(): bool {
		return defined( 'ATOMIC_SITE_ID' ) && ATOMIC_SITE_ID && defined( 'ATOMIC_CLIENT_ID' ) && ATOMIC_CLIENT_ID;
	}

	/**
	 * What a merchant should do for a failing serve_status() value. Includes the file path.
	 *
	 * On WordPress.com the fix names its support instead of the hosting provider.
	 *
	 * @param string    $status        A status from serve_status().
	 * @param bool|null $wordpress_com Whether the site is on WordPress.com, or null to detect it.
	 * @return string Empty when the address is already served.
	 */
	public static function discovery_fix( string $status, ?bool $wordpress_com = null ): string {
		$path = self::get_file_path();
		$dir  = dirname( $path );

		// On WordPress.com its support stands in for the hosting provider.
		$wordpress_com = $wordpress_com ?? self::is_wordpress_com();

		switch ( $status ) {
			case 'not_readable':
				return $wordpress_com
					/* translators: %s: discovery file path. */
					? sprintf( __( 'WooCommerce can’t read the file at %s. Delete it, or contact WordPress.com support to make it readable.', 'woocommerce' ), $path )
					/* translators: %s: discovery file path. */
					: sprintf( __( 'WooCommerce can’t read the file at %s. Delete it, or ask your hosting provider to make it readable.', 'woocommerce' ), $path );
			case 'file_unusable':
				return $wordpress_com
					/* translators: 1: discovery file path, 2: folder path. */
					? sprintf( __( 'The file at %1$s is incomplete or damaged. Delete it, and check that your server has free disk space. If you can’t delete it, contact WordPress.com support to make %2$s writable.', 'woocommerce' ), $path, $dir )
					/* translators: 1: discovery file path, 2: folder path. */
					: sprintf( __( 'The file at %1$s is incomplete or damaged. Delete it, and check that your server has free disk space. If you can’t delete it, ask your hosting provider to make %2$s writable.', 'woocommerce' ), $path, $dir );
			case 'file_in_the_way':
				return $wordpress_com
					/* translators: 1: discovery file path, 2: folder path. */
					? sprintf( __( 'A file at %1$s that WooCommerce didn’t create is loading instead. Remove it, or contact WordPress.com support to make %2$s writable.', 'woocommerce' ), $path, $dir )
					/* translators: 1: discovery file path, 2: folder path. */
					: sprintf( __( 'A file at %1$s that WooCommerce didn’t create is loading instead. Remove it, or ask your hosting provider to make %2$s writable.', 'woocommerce' ), $path, $dir );
			case 'file_outdated':
				return $wordpress_com
					/* translators: 1: discovery file path, 2: folder path. */
					? sprintf( __( 'The file at %1$s has old store details. Delete it, or contact WordPress.com support to make %2$s writable so WooCommerce can replace it.', 'woocommerce' ), $path, $dir )
					/* translators: 1: discovery file path, 2: folder path. */
					: sprintf( __( 'The file at %1$s has old store details. Delete it, or ask your hosting provider to make %2$s writable so WooCommerce can replace it.', 'woocommerce' ), $path, $dir );
			case 'no_file':
				if ( is_multisite() ) {
					return __( 'Choose any structure except Plain. Structures that start with index.php don’t work either.', 'woocommerce' );
				}
				return $wordpress_com
					/* translators: %s: folder path. */
					? sprintf( __( 'Choose any structure except Plain. Structures that start with index.php don’t work either. If you need to keep your current structure, contact WordPress.com support to make %s writable, so WooCommerce can add your connection file.', 'woocommerce' ), $dir )
					/* translators: %s: folder path. */
					: sprintf( __( 'Choose any structure except Plain. Structures that start with index.php don’t work either. If you need to keep your current structure, ask your hosting provider to make %s writable, so WooCommerce can add your connection file.', 'woocommerce' ), $dir );
			default:
				return '';
		}
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
			register_deactivation_hook( WC_PLUGIN_FILE, array( $this, 'delete_file' ) );
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
	public function maybe_flush_rewrite_rules( $feature_id, $is_enabled = true ): void {
		$feature_id = (string) $feature_id;
		$is_enabled = (bool) $is_enabled;

		if ( Utils::FEATURE_ID !== $feature_id ) {
			return;
		}

		update_option( 'woocommerce_queue_flush_rewrite_rules', 'yes' );

		if ( ! $is_enabled ) {
			$this->delete_file();
		}
	}

	/**
	 * Remove the static discovery file, which the web server would otherwise keep serving without WooCommerce.
	 * Files WooCommerce did not write are left alone.
	 */
	public function delete_file(): void {
		$path = $this->get_file_path();
		if ( $this->is_deletable( $path ) && $this->is_owned( $path ) ) {
			wp_delete_file( $path );
		}
	}

	/**
	 * Keep the static discovery file in sync: write it when permalinks don't route `/.well-known/ucp`
	 * (Plain, or a structure that starts with index.php), and delete it when they do.
	 * The file only refreshes on admin page loads, and when UCP is turned on.
	 *
	 * Failures are silent here: the Agentic Readiness checks report an unreachable or invalid discovery endpoint.
	 */
	public function maybe_sync_file(): void {
		// admin_init also fires for anonymous admin-ajax.php/admin-post.php requests and on every heartbeat.
		if ( wp_doing_ajax() || ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		if ( self::rewrite_serves_discovery() ) {
			// A file that cannot be deleted keeps overriding the profile WooCommerce serves, as the web server answers first.
			$this->delete_file();
			return;
		}

		// One file under ABSPATH cannot hold a profile per site, so multisite requires pretty permalinks.
		if ( is_multisite() ) {
			return;
		}

		// A file WooCommerce did not write is left in place, so it is served instead of the WooCommerce profile.
		$path = $this->get_file_path();
		if ( is_file( $path ) && ! $this->is_owned( $path ) ) {
			return;
		}

		// phpcs:disable WordPress.WP.AlternativeFunctions
		$content = (string) wp_json_encode( $this->get_profile(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		$dir     = dirname( $path );

		// Without write access no file is written, and /.well-known/ucp is unreachable unless the server routes it to index.php.
		$writable = is_file( $path ) ? is_writable( $path ) : wp_mkdir_p( $dir ) && is_writable( $dir );
		if ( $writable && ! ( is_file( $path ) && file_get_contents( $path ) === $content ) ) {
			// A full disk can leave a truncated file behind, which is then served as an invalid profile.
			// It also loses the ownership marker, so it is no longer rewritten or removed.
			file_put_contents( $path, $content );
		}
		// phpcs:enable WordPress.WP.AlternativeFunctions
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
	 * Whether the static file was written by WooCommerce. Unreadable or non-JSON files count as not owned.
	 *
	 * @param string $path Absolute file path.
	 * @return bool
	 */
	private function is_owned( string $path ): bool {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$profile = is_file( $path ) ? json_decode( (string) file_get_contents( $path ), true ) : null;
		$links   = $profile['links'] ?? null;
		return is_array( $links ) && in_array( self::LINK_REL, array_column( $links, 'rel' ), true );
	}

	/**
	 * Whether the permalink structure routes `/.well-known/ucp` to WordPress.
	 * Structures that start with index.php (PATHINFO) do not: the web server only does
	 * when it sends every path to WordPress, and the static file covers that case.
	 *
	 * @return bool
	 */
	private static function rewrite_serves_discovery(): bool {
		global $wp_rewrite;

		return $wp_rewrite->using_mod_rewrite_permalinks();
	}

	/**
	 * Whether WooCommerce can create the static file, including the `.well-known` folder.
	 * One file under ABSPATH can't hold a profile per site, so multisite never can.
	 *
	 * @param string $path Absolute file path.
	 * @return bool
	 */
	private static function can_create_file( string $path ): bool {
		if ( is_multisite() ) {
			return false;
		}

		$dir = dirname( $path );
		while ( ! is_dir( $dir ) ) {
			$parent = dirname( $dir );
			if ( $parent === $dir ) {
				return false;
			}
			$dir = $parent;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable
		return is_writable( $dir );
	}

	/**
	 * Path of the static discovery file served under plain permalinks.
	 *
	 * @return string
	 */
	private static function get_file_path(): string {
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
				array(
					'rel'  => self::LINK_REL,
					'href' => self::LINK_HREF,
				),
			),
		);
	}
}
