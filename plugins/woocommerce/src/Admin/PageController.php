<?php
/**
 * PageController
 */

namespace Automattic\WooCommerce\Admin;

use Automattic\WooCommerce\Internal\Admin\Loader;

use WC_Gateway_BACS;
use WC_Gateway_Cheque;
use WC_Gateway_COD;
use WC_Gateway_Paypal;

defined( 'ABSPATH' ) || exit;

/**
 * PageController
 */
class PageController {
	/**
	 * App entry point.
	 */
	const APP_ENTRY_POINT = 'wc-admin';

	// JS-powered page root.
	const PAGE_ROOT = 'wc-admin';

	/**
	 * Regex fragment matching a route parameter name (the part after `:` in `:paramName`).
	 *
	 * Single source of truth for the accepted parameter charset: the matcher's segment
	 * rewrite, the specificity scorer, and the route pattern detector must all agree on it.
	 */
	private const ROUTE_PARAM_NAME_PATTERN = '[A-Za-z0-9_]+';

	/**
	 * Singleton instance of self.
	 *
	 * @var PageController
	 */
	private static $instance = false;

	/**
	 * Current page data (or false if not registered with this controller).
	 *
	 * @var array|false|null
	 */
	private $current_page = null;

	/**
	 * Whether the current page was resolved by matching a route pattern.
	 *
	 * Only true when the fallback matcher selected a page whose registered path actually
	 * carries a `:param` or terminal `/*` segment. The fallback also matches pattern-free
	 * paths (it tolerates trailing slashes and casing), and those resolve to a concrete,
	 * linkable path just like an exact match does.
	 *
	 * @var bool
	 */
	private $current_page_is_route_pattern_match = false;

	/**
	 * Registered pages
	 * Contains information (breadcrumbs, menu info) about JS powered pages and classic WooCommerce pages.
	 *
	 * @var array
	 */
	private $pages = array();

	/**
	 * We want a single instance of this class so we can accurately track registered menus and pages.
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 * Hooks added here should be removed in `wc_admin_initialize` via the feature plugin.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_page_handler' ) );
		add_action( 'admin_menu', array( $this, 'register_store_details_page' ) );

		// priority is 20 to run after https://github.com/woocommerce/woocommerce/blob/a55ae325306fc2179149ba9b97e66f32f84fdd9c/includes/admin/class-wc-admin-menus.php#L165.
		add_action( 'admin_head', array( $this, 'remove_app_entry_page_menu_item' ), 20 );
		// Using low priority to run before other hooks.
		add_action( 'admin_init', array( $this, 'maybe_redirect_payment_tasks_to_settings' ), 1 );
	}

	/**
	 * Connect an existing page to wc-admin.
	 *
	 * @param array $options {
	 *   Array describing the page.
	 *
	 *   @type string       id           Id to reference the page.
	 *   @type string|array title        Page title. Used in menus and breadcrumbs.
	 *   @type string|null  parent       Parent ID. Null for new top level page.
	 *   @type string       path         Path for this page. E.g. admin.php?page=wc-settings&tab=checkout
	 *   @type string       capability   Capability needed to access the page.
	 *   @type string       icon         Icon. Dashicons helper class, base64-encoded SVG, or 'none'.
	 *   @type int          position     Menu item position.
	 *   @type boolean      js_page      If this is a JS-powered page.
	 * }
	 */
	public function connect_page( $options ) {
		if ( ! is_array( $options['title'] ) ) {
			$options['title'] = array( $options['title'] );
		}

		/**
		 * Filter the options when connecting or registering a page.
		 *
		 * Use the `js_page` option to determine if registering.
		 *
		 * @param array $options {
		 *   Array describing the page.
		 *
		 *   @type string       id           Id to reference the page.
		 *   @type string|array title        Page title. Used in menus and breadcrumbs.
		 *   @type string|null  parent       Parent ID. Null for new top level page.
		 *   @type string       screen_id    The screen ID that represents the connected page. (Not required for registering).
		 *   @type string       path         Path for this page. E.g. admin.php?page=wc-settings&tab=checkout
		 *   @type string       capability   Capability needed to access the page.
		 *   @type string       icon         Icon. Dashicons helper class, base64-encoded SVG, or 'none'.
		 *   @type int          position     Menu item position.
		 *   @type boolean      js_page      If this is a JS-powered page.
		 * }
		 */
		$options = apply_filters( 'woocommerce_navigation_connect_page_options', $options );

		// In the future, we should consider check for collision, but keep in mind that the current behavior is: the later call silently overwrites the earlier one.
		$id = $options['id'] ?? null;

		if ( is_string( $id ) && '' !== $id ) {
			$this->pages[ $id ] = $options;
		}
	}

	/**
	 * Determine the current page ID, if it was registered with this controller.
	 */
	public function determine_current_page() {
		$current_url       = '';
		$current_screen_id = $this->get_current_screen_id();

		$this->current_page_is_route_pattern_match = false;

		if ( isset( $_SERVER['REQUEST_URI'] ) ) {
			$current_url = esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) );
		}

		$current_query = wp_parse_url( $current_url, PHP_URL_QUERY );
		parse_str( (string) $current_query, $current_pieces );
		$current_path  = empty( $current_pieces['page'] ) ? '' : $current_pieces['page'];
		$current_path .= empty( $current_pieces['path'] ) ? '' : '&path=' . $current_pieces['path'];

		foreach ( $this->pages as $page ) {
			if ( isset( $page['js_page'] ) && $page['js_page'] ) {
				// Check registered admin pages.
				if (
					$page['path'] === $current_path
				) {
					$this->current_page = $page;
					return;
				}
			} else {
				// Check connected admin pages.
				if (
					isset( $page['screen_id'] ) &&
					$page['screen_id'] === $current_screen_id
				) {
					$this->current_page = $page;
					return;
				}
			}
		}

		// Route templates only apply to requests with an app path. The request path is split but never
		// normalized, unlike the registered paths below: see split_normalized_registered_page_path().
		$current_path_parts = $this->split_registered_page_path( $current_path );
		if ( '' === $current_path_parts['path'] ) {
			$this->current_page = false;
			return;
		}

		$matching_page  = false;
		$matching_score = null;

		foreach ( $this->pages as $page ) {
			if ( empty( $page['js_page'] ) || ! isset( $page['path'] ) || ! is_string( $page['path'] ) ) {
				continue;
			}

			$registered_parts = $this->split_normalized_registered_page_path( $page['path'] );

			if ( ! $this->registered_path_matches_current_path( $registered_parts, $current_path_parts ) ) {
				continue;
			}

			$score = $this->get_registered_path_score( $registered_parts['path'] );
			if ( null === $matching_score || $score > $matching_score ) {
				$matching_page  = $page;
				$matching_score = $score;
			}
		}

		$this->current_page                        = $matching_page;
		$this->current_page_is_route_pattern_match = false !== $matching_page && $this->registered_path_has_route_pattern( $matching_page['path'] );
	}

	/**
	 * Split a registered page path into root and app path pieces, normalized for route matching.
	 *
	 * React Router resolves a registered route that omits the leading slash against the app root, so
	 * `route/:itemId` renders at `/route/123`. Normalizing the registered side describes the path the
	 * client actually renders. Current request paths are deliberately not normalized this way: the
	 * admin history assigns `pathname` from the `path` query argument verbatim, so a request without a
	 * leading slash matches no React route and must not be recognized here either.
	 *
	 * @param string $registered_path Registered page path.
	 * @return array
	 */
	private function split_normalized_registered_page_path( $registered_path ) {
		$path_parts = $this->split_registered_page_path( $registered_path );

		if ( '' !== $path_parts['path'] ) {
			$path_parts['path'] = '/' . ltrim( $path_parts['path'], '/' );
		}

		return $path_parts;
	}

	/**
	 * Whether a registered path matches the current request path.
	 *
	 * Both paths arrive pre-split so the current request path is only parsed once per request,
	 * rather than once per registered page. The caller guarantees a non-empty current app path.
	 *
	 * @param array $registered_parts Registered page path, as returned by split_registered_page_path().
	 * @param array $current_parts Current request path, as returned by split_registered_page_path().
	 * @return bool
	 */
	private function registered_path_matches_current_path( $registered_parts, $current_parts ) {
		if ( $registered_parts['root'] !== $current_parts['root'] || '' === $registered_parts['path'] ) {
			return false;
		}

		$route_pattern = $registered_parts['path'];
		$current_path  = $current_parts['path'];

		// React Router 6.3 compiles route regexes with JavaScript's plain `i` flag (no `u`), whose
		// case folding is narrower than PCRE's Unicode folding: `É` reaches `é`, but `ſ` does not
		// reach `s`, nor the Kelvin sign `k`. Canonicalizing both sides the way JavaScript does and
		// matching case-sensitively reproduces those semantics exactly. Without mbstring, PCRE's
		// `iu` is the closest approximation (it over-matches only such exotic case pairs); malformed
		// UTF-8 would make `preg_match()` fail outright, which would silently read as an unrecognized
		// page, so it falls back to the byte-wise `i` comparison used before instead.
		$is_utf8   = 1 === preg_match( '//u', $route_pattern ) && 1 === preg_match( '//u', $current_path );
		$modifiers = $is_utf8 ? 'iu' : 'i';

		if ( $is_utf8 && function_exists( 'mb_strtoupper' ) && function_exists( 'mb_ord' ) && function_exists( 'mb_strlen' ) ) {
			$route_pattern = $this->canonicalize_path_for_route_match( $route_pattern );
			$current_path  = $this->canonicalize_path_for_route_match( $current_path );
			$modifiers     = '';
		}

		$has_terminal_splat = 1 === preg_match( '#/\*$#', $route_pattern );

		if ( $has_terminal_splat ) {
			$route_pattern = substr( $route_pattern, 0, -2 );
		} else {
			$route_pattern = rtrim( $route_pattern, '/' );
		}

		$route_regex = preg_quote( $route_pattern, '#' );

		// preg_quote() escapes parameter colons (for example, `:itemId` becomes `\:itemId`).
		// Match only complete parameter segments: `(^|/)` preserves the start-or-slash prefix,
		// `\\:` targets the escaped colon, and `(?=/|$)` requires the parameter name to end the segment.
		// Falling back to the quoted pattern on a PCRE failure keeps `:itemId` literal, which matches
		// nothing it should not, rather than leaving an empty pattern that matches everything.
		$route_regex = preg_replace( '#(^|/)\\\\:' . self::ROUTE_PARAM_NAME_PATTERN . '(?=/|$)#', '$1[^/]+', $route_regex ) ?? $route_regex;

		if ( $has_terminal_splat ) {
			// A supported terminal `/*` matches both the base route and any descendants.
			$route_regex .= '(?:/.*)?';
		}

		// `D` keeps the `$` anchor strict (no trailing-newline allowance) without relying on the
		// upstream esc_url_raw() sanitization to have stripped newlines from the request path.
		return 1 === preg_match( '#^' . $route_regex . '/*$#D' . $modifiers, $current_path );
	}

	/**
	 * Canonicalize a path the way JavaScript canonicalizes case-insensitive regex input.
	 *
	 * Emulates ECMA-262 Canonicalize for a RegExp with the `i` flag and without the `u` flag —
	 * what React Router compiles routes with. Each character maps to its uppercase form, except:
	 *
	 * - A multi-character uppercase mapping never folds (`ß` stays `ß` rather than becoming `SS`).
	 * - A non-ASCII character whose uppercase form is ASCII never folds (`ſ` stays `ſ`, not `S`).
	 * - Supplementary-plane characters never fold: JavaScript canonicalizes UTF-16 code units, and
	 *   a surrogate half has no case mapping.
	 *
	 * Matching two canonicalized paths case-sensitively therefore folds case exactly where
	 * JavaScript's `i` flag does, and nowhere else.
	 *
	 * @param string $path Valid UTF-8 app path.
	 * @return string
	 */
	private function canonicalize_path_for_route_match( $path ) {
		// ASCII-only paths (the common case) canonicalize to plain uppercase.
		if ( 1 !== preg_match( '/[\x80-\xFF]/', $path ) ) {
			return strtoupper( $path );
		}

		$chars = preg_split( '//u', $path, -1, PREG_SPLIT_NO_EMPTY );

		// Unreachable for the valid UTF-8 the caller guarantees; an unchanged path merely
		// falls back to case-sensitive matching rather than corrupting the comparison.
		if ( false === $chars ) {
			return $path;
		}

		$canonical = '';

		foreach ( $chars as $char ) {
			$code_point = mb_ord( $char, 'UTF-8' );

			if ( $code_point >= 0x10000 ) {
				$canonical .= $char;
				continue;
			}

			$upper = mb_strtoupper( $char, 'UTF-8' );

			if ( 1 !== mb_strlen( $upper, 'UTF-8' ) ) {
				$canonical .= $char;
				continue;
			}

			$canonical .= ( $code_point >= 0x80 && mb_ord( $upper, 'UTF-8' ) < 0x80 ) ? $char : $upper;
		}

		return $canonical;
	}

	/**
	 * Split a registered PageController path into root and app path pieces.
	 *
	 * @param string $path Registered or current page path.
	 * @return array
	 */
	private function split_registered_page_path( $path ) {
		$path_parts = explode( '&path=', $path, 2 );

		return array(
			'root' => $path_parts[0],
			'path' => $path_parts[1] ?? '',
		);
	}

	/**
	 * Whether a registered path contains a supported route pattern.
	 *
	 * Callers are responsible for rejecting non-string paths, which a page options filter can
	 * produce; both call sites already do so before reaching here.
	 *
	 * @param string $registered_path Registered page path.
	 * @return bool
	 */
	private function registered_path_has_route_pattern( $registered_path ) {
		// Normalizing keeps this consistent with the matcher: a slash-less registered path (for
		// example a bare `*`) resolves against the app root there, so it must read as patterned here.
		$path_parts = $this->split_normalized_registered_page_path( $registered_path );

		// The first alternative recognizes a complete `:param` segment (normalization guarantees the
		// leading slash). The second recognizes only a splat that occupies the terminal segment.
		return 1 === preg_match( '#(?:/:' . self::ROUTE_PARAM_NAME_PATTERN . '(?=/|$)|/\*$)#', $path_parts['path'] );
	}

	/**
	 * Get a specificity score for a registered page path.
	 *
	 * The shape and constants mirror React Router's computeScore route ranking: the segment count
	 * seeds the score, any splat costs a penalty of 2, and each non-splat segment then adds 10
	 * (static), 3 (parameter), or 1 (empty).
	 *
	 * Only meaningful for app paths that already matched the current request, which is why a `*`
	 * segment can be scored as a wildcard here: the matcher only treats a terminal `/*` as a splat.
	 *
	 * @param string $app_path Registered page app path (the part after `&path=`).
	 * @return int
	 */
	private function get_registered_path_score( $app_path ) {
		$segments = explode( '/', $app_path );
		$score    = count( $segments );

		if ( in_array( '*', $segments, true ) ) {
			$score -= 2;
		}

		foreach ( $segments as $segment ) {
			if ( '*' === $segment ) {
				continue;
			}

			if ( '' === $segment ) {
				++$score;
			} elseif ( 1 === preg_match( '#^:' . self::ROUTE_PARAM_NAME_PATTERN . '$#', $segment ) ) {
				$score += 3;
			} else {
				$score += 10;
			}
		}

		return $score;
	}


	/**
	 * Get breadcrumbs for WooCommerce Admin Page navigation.
	 *
	 * @return array Navigation pieces (breadcrumbs).
	 */
	public function get_breadcrumbs() {
		$current_page = $this->get_current_page();

		// Bail if this isn't a page registered with this controller.
		if ( false === $current_page ) {
			// Filter documentation below.
			return apply_filters( 'woocommerce_navigation_get_breadcrumbs', array( '' ), $current_page );
		}

		$page_title = ! empty( $current_page['page_title'] ) ? $current_page['page_title'] : $current_page['title'];
		$page_title = (array) $page_title;
		// A patterned path is not linkable: it would point at the literal `:param`/`*` template.
		if ( 1 === count( $page_title ) || $this->current_page_is_route_pattern_match ) {
			$breadcrumbs = $page_title;
		} else {
			// If this page has multiple title pieces, only link the first one.
			$breadcrumbs = array_merge(
				array(
					array( $current_page['path'], reset( $page_title ) ),
				),
				array_slice( $page_title, 1 )
			);
		}

		if ( isset( $current_page['parent'] ) ) {
			$parent_id = $current_page['parent'];

			while ( $parent_id ) {
				if ( isset( $this->pages[ $parent_id ] ) ) {
					$parent      = $this->pages[ $parent_id ];
					$parent_path = $parent['path'] ?? null;

					// A non-string filtered path is never linkable. Once the current page resolved through
					// route-pattern matching, a patterned ancestor is not linkable either: the link would
					// point at the literal `:param`/`*` template.
					$parent_is_linkable = is_string( $parent_path ) &&
						( ! $this->current_page_is_route_pattern_match || ! $this->registered_path_has_route_pattern( $parent_path ) );

					if ( $parent_is_linkable ) {
						if ( 0 === strpos( $parent_path, self::PAGE_ROOT ) ) {
							$parent_path = 'admin.php?page=' . $parent_path;
						}

						array_unshift( $breadcrumbs, array( $parent_path, reset( $parent['title'] ) ) );
					} else {
						array_unshift( $breadcrumbs, reset( $parent['title'] ) );
					}

					$parent_id = isset( $parent['parent'] ) ? $parent['parent'] : false;
				} else {
					$parent_id = false;
				}
			}
		}

		$woocommerce_breadcrumb = array( 'admin.php?page=' . self::PAGE_ROOT, __( 'WooCommerce', 'woocommerce' ) );

		array_unshift( $breadcrumbs, $woocommerce_breadcrumb );

		/**
		 * The navigation breadcrumbs for the current page.
		 *
		 * @since 6.5.0
		 *
		 * @param array         $breadcrumbs Navigation pieces. Each piece is either a URL and label array or an unlinked label string.
		 * @param array|boolean $current_page The connected page data or false if not identified.
		 */
		return apply_filters( 'woocommerce_navigation_get_breadcrumbs', $breadcrumbs, $current_page );
	}

	/**
	 * Get the current page.
	 *
	 * @return array|boolean Current page or false if not registered with this controller.
	 */
	public function get_current_page() {
		// If 'current_screen' hasn't fired yet, the current page calculation
		// will fail which causes `false` to be returned for all subsequent calls.
		if ( ! did_action( 'current_screen' ) ) {
			_doing_it_wrong( __FUNCTION__, esc_html__( 'Current page retrieval should be called on or after the `current_screen` hook.', 'woocommerce' ), '0.16.0' );
		}

		if ( is_null( $this->current_page ) ) {
			$this->determine_current_page();
		}

		// determine_current_page() always assigns, so null cannot escape; the coalesce states that
		// invariant where static analysis can see it.
		return $this->current_page ?? false;
	}


	/**
	 * Returns the current screen ID.
	 *
	 * This is slightly different from WP's get_current_screen, in that it attaches an action,
	 * so certain pages like 'add new' pages can have different breadcrumbs or handling.
	 * It also catches some more unique dynamic pages like taxonomy/attribute management.
	 *
	 * Format:
	 * - {$current_screen->action}-{$current_screen->action}-tab-section
	 * - {$current_screen->action}-{$current_screen->action}-tab
	 * - {$current_screen->action}-{$current_screen->action} if no tab is present
	 * - {$current_screen->action} if no action or tab is present
	 *
	 * @return string Current screen ID.
	 */
	public function get_current_screen_id() {
		// The screen cannot be determined during REST API requests or before the WordPress screen API loads.
		$current_screen = ( wp_is_serving_rest_request() || ! function_exists( 'get_current_screen' ) ) ? null : get_current_screen();

		if ( ! $current_screen ) {
			// Filter documentation below.
			return apply_filters( 'woocommerce_navigation_current_screen_id', false, $current_screen );
		}

		$screen_pieces = array( $current_screen->id );

		if ( $current_screen->action ) {
			$screen_pieces[] = $current_screen->action;
		}

		if (
			! empty( $current_screen->taxonomy ) &&
			isset( $current_screen->post_type ) &&
			'product' === $current_screen->post_type
		) {
			// Editing a product attribute.
			if ( 0 === strpos( $current_screen->taxonomy, 'pa_' ) ) {
				$screen_pieces = array( 'product_page_product_attribute-edit' );
			}

			// Editing a product taxonomy term.
			if ( ! empty( $_GET['tag_ID'] ) ) {
				$screen_pieces = array( $current_screen->taxonomy );
			}
		}

		// Pages with default tab values.
		$pages_with_tabs = apply_filters(
			'woocommerce_navigation_pages_with_tabs',
			array(
				'wc-reports'  => 'orders',
				'wc-settings' => 'general',
				'wc-status'   => 'status',
				'wc-addons'   => 'browse-extensions',
			)
		);

		// Tabs that have sections as well.
		$wc_emails    = \WC_Emails::instance();
		$wc_email_ids = array_map( 'sanitize_title', array_keys( $wc_emails->get_emails() ) );

		$tabs_with_sections = apply_filters(
			'woocommerce_navigation_page_tab_sections',
			array(
				'products'          => array( '', 'inventory', 'downloadable', 'download_urls', 'advanced' ),
				'shipping'          => array( '', 'options', 'classes', 'pickup_location' ),
				'checkout'          => array( WC_Gateway_BACS::ID, WC_Gateway_Cheque::ID, WC_Gateway_COD::ID, WC_Gateway_Paypal::ID ),
				'email'             => $wc_email_ids,
				'advanced'          => array(
					'',
					'keys',
					'webhooks',
					'woocommerce_com',
					'features',
					'blueprint',
				),
				'browse-extensions' => array( 'helper' ),
			)
		);

		if ( ! empty( $_GET['page'] ) ) {
			$page = wc_clean( wp_unslash( $_GET['page'] ) );
			if ( in_array( $page, array_keys( $pages_with_tabs ) ) ) {
				if ( ! empty( $_GET['tab'] ) ) {
					$tab = wc_clean( wp_unslash( $_GET['tab'] ) );
				} else {
					$tab = $pages_with_tabs[ $page ];
				}

				$screen_pieces[] = $tab;

				if ( ! empty( $_GET['section'] ) ) {
					$section = wc_clean( wp_unslash( $_GET['section'] ) );
					if (
						isset( $tabs_with_sections[ $tab ] ) &&
						in_array( $section, array_values( $tabs_with_sections[ $tab ] ), true )
					) {
						$screen_pieces[] = $section;
					}
				}

				// Editing a shipping zone.
				if ( ( 'shipping' === $tab ) && isset( $_GET['zone_id'] ) ) {
					$screen_pieces[] = 'edit_zone';
				}
			}
		}

		/**
		 * The current screen id.
		 *
		 * Used for identifying pages to render the WooCommerce Admin header.
		 *
		 * @since 3.9.0
		 *
		 * @param string|boolean  $screen_id The screen id or false if not identified.
		 * @param \WP_Screen|null $current_screen The current WP_Screen or null if it could not be determined.
		 */
		return apply_filters( 'woocommerce_navigation_current_screen_id', implode( '-', $screen_pieces ), $current_screen );
	}

	/**
	 * Returns the path from an ID.
	 *
	 * @param  string $id  ID to get path for.
	 * @return string Path for the given ID, or the ID on lookup miss.
	 */
	public function get_path_from_id( $id ) {
		if ( isset( $this->pages[ $id ] ) && isset( $this->pages[ $id ]['path'] ) ) {
			return $this->pages[ $id ]['path'];
		}
		return $id;
	}

	/**
	 * Returns true if we are on a page connected to this controller.
	 *
	 * @return boolean
	 */
	public function is_connected_page() {
		$current_page = $this->get_current_page();

		if ( false === $current_page ) {
			$is_connected_page = false;
		} else {
			$is_connected_page = isset( $current_page['js_page'] ) ? ! $current_page['js_page'] : true;
		}

		// Disable embed on the block editor.
		$current_screen = did_action( 'current_screen' ) ? get_current_screen() : false;
		if ( ! empty( $current_screen ) && method_exists( $current_screen, 'is_block_editor' ) && $current_screen->is_block_editor() ) {
			$is_connected_page = false;
		}

		/**
		 * Whether or not the current page is an existing page connected to this controller.
		 *
		 * Used to determine if the WooCommerce Admin header should be rendered.
		 *
		 * @param boolean       $is_connected_page True if the current page is connected.
		 * @param array|boolean $current_page The connected page data or false if not identified.
		 */
		return apply_filters( 'woocommerce_navigation_is_connected_page', $is_connected_page, $current_page );
	}

	/**
	 * Returns true if we are on a page registered with this controller.
	 *
	 * @return boolean
	 */
	public function is_registered_page() {
		$current_page = $this->get_current_page();

		if ( false === $current_page ) {
			$is_registered_page = false;
		} else {
			$is_registered_page = isset( $current_page['js_page'] ) && $current_page['js_page'];
		}

		/**
		 * Whether or not the current page was registered with this controller.
		 *
		 * Used to determine if this is a JS-powered WooCommerce Admin page.
		 *
		 * @param boolean       $is_registered_page True if the current page was registered with this controller.
		 * @param array|boolean $current_page The registered page data or false if not identified.
		 */
		return apply_filters( 'woocommerce_navigation_is_registered_page', $is_registered_page, $current_page );
	}

	/**
	 * Adds a JS powered page to wc-admin.
	 *
	 * @param array $options {
	 *   Array describing the page.
	 *
	 *   @type string      id           Id to reference the page.
	 *   @type string      title        Page title. Used in menus and breadcrumbs.
	 *   @type string|null parent       Parent ID. Null for new top level page.
	 *   @type string      path         Path for this page, full path in app context; ex /analytics/report
	 *   @type string      capability   Capability needed to access the page.
	 *   @type string      icon         Icon. Dashicons helper class, base64-encoded SVG, or 'none'.
	 *   @type int         position     Menu item position.
	 *   @type int         order        Navigation item order.
	 * }
	 */
	public function register_page( $options ) {
		$defaults = array(
			'id'         => null,
			'parent'     => null,
			'title'      => '',
			'page_title' => '',
			'capability' => 'view_woocommerce_reports',
			'path'       => '',
			'icon'       => '',
			'position'   => null,
			'js_page'    => true,
		);

		$options = wp_parse_args( $options, $defaults );

		if ( 0 !== strpos( $options['path'], self::PAGE_ROOT ) ) {
			$options['path'] = self::PAGE_ROOT . '&path=' . $options['path'];
		}

		if ( null !== $options['position'] ) {
			$options['position'] = intval( round( $options['position'] ) );
		}

		if ( empty( $options['page_title'] ) ) {
			$options['page_title'] = $options['title'];
		}

		if ( is_null( $options['parent'] ) ) {
			add_menu_page(
				$options['page_title'],
				$options['title'],
				$options['capability'],
				$options['path'],
				array( __CLASS__, 'page_wrapper' ),
				$options['icon'],
				$options['position']
			);
		} else {
			$parent_path = $this->get_path_from_id( $options['parent'] );
			// @todo check for null path.
			add_submenu_page(
				$parent_path,
				$options['page_title'],
				$options['title'],
				$options['capability'],
				$options['path'],
				array( __CLASS__, 'page_wrapper' )
			);
		}

		$this->connect_page( $options );
	}

	/**
	 * Get registered pages.
	 *
	 * @return array
	 */
	public function get_pages() {
		return $this->pages;
	}

	/**
	 * Set up a div for the app to render into.
	 */
	public static function page_wrapper() {
		Loader::page_wrapper();
	}

	/**
	 * Connects existing WooCommerce pages.
	 *
	 * @todo The entry point for the embed needs moved to this class as well.
	 */
	public function register_page_handler() {
		require_once WC_ADMIN_ABSPATH . 'includes/react-admin/connect-existing-pages.php';
	}

	/**
	 * Registers the store details (profiler) page.
	 */
	public function register_store_details_page() {
		wc_admin_register_page(
			array(
				'id'     => 'setup-wizard',
				'title'  => __( 'Setup Wizard', 'woocommerce' ),
				'parent' => '',
				'path'   => '/setup-wizard',
			)
		);
	}

	/**
	 * Remove the menu item for the app entry point page.
	 */
	public function remove_app_entry_page_menu_item() {
		global $submenu;
		// User does not have capabilities to see the submenu.
		if ( ! current_user_can( 'manage_woocommerce' ) || empty( $submenu['woocommerce'] ) ) {
			return;
		}

		$wc_admin_key = null;
		foreach ( $submenu['woocommerce'] as $submenu_key => $submenu_item ) {
			// Our app entry page menu item has no title.
			if ( is_null( $submenu_item[0] ) && self::APP_ENTRY_POINT === $submenu_item[2] ) {
				$wc_admin_key = $submenu_key;
				break;
			}
		}

		if ( ! $wc_admin_key ) {
			return;
		}

		unset( $submenu['woocommerce'][ $wc_admin_key ] );
	}

	/**
	 * Returns true if we are on a JS powered admin page or
	 * a "classic" (non JS app) powered admin page (an embedded page).
	 */
	public static function is_admin_or_embed_page() {
		return self::is_admin_page() || self::is_embed_page();
	}

	/**
	 * Returns true if we are on a JS powered admin page.
	 */
	public static function is_admin_page() {
		// phpcs:disable WordPress.Security.NonceVerification
		return isset( $_GET['page'] ) && 'wc-admin' === $_GET['page'];
		// phpcs:enable WordPress.Security.NonceVerification
	}

	/**
	 * Returns true if we are on a settings page.
	 */
	public static function is_settings_page() {
		// phpcs:disable WordPress.Security.NonceVerification
		return isset( $_GET['page'] ) && 'wc-settings' === $_GET['page'];
		// phpcs:enable WordPress.Security.NonceVerification
	}

	/**
	 *  Returns true if we are on a "classic" (non JS app) powered admin page.
	 *
	 * TODO: See usage in `admin.php`. This needs refactored and implemented properly in core.
	 */
	public static function is_embed_page() {
		return wc_admin_is_connected_page();
	}

	/**
	 * Redirect payment tasks to the settings page.
	 *
	 * Redirects both 'payments' and 'woocommerce-payments' tasks to the Payments settings page,
	 * when it is safe to do so in terms of backwards compatibility.
	 */
	public function maybe_redirect_payment_tasks_to_settings() {
		// Bail if we are not in the WP admin or not on a WC admin page.
		if ( ! is_admin() || ! self::is_admin_page() ) {
			return;
		}

		// Bail if we are not requesting a page for a WooCommerce task.
		// phpcs:ignore WordPress.Security.NonceVerification
		if ( empty( $_GET['task'] ) ) {
			return;
		}

		// Only sufficiently capable users should be redirected.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		// Get the current task ID.
		// phpcs:ignore WordPress.Security.NonceVerification
		$task_id = wc_clean( wp_unslash( $_GET['task'] ) );

		// Bail if the task is not a payments task.
		if ( ! in_array( $task_id, array( 'payments', 'woocommerce-payments' ), true ) ) {
			return;
		}

		$redirect_url = admin_url( 'admin.php?page=wc-settings&tab=checkout&from=WCADMIN_PAYMENT_TASK' );

		// The WooPayments task is always redirected to the settings page.
		if ( 'woocommerce-payments' === $task_id ) {
			wp_safe_redirect( $redirect_url );
			exit;
		}

		// The generic payments task is only redirected if the request is a regular user request,
		// not part of an onboarding flow or other special case.
		$special_request_params = array(
			// This is used by the legacy, Payments task-based suggestions onboarding flow.
			// Nobody should be using this anymore, but just in case.
			'connection-return',
			// This is used by the legacy, Payments task-based suggestions onboarding flow.
			// Nobody should be using this anymore, but just in case.
			'id',
			// Some params for gateway IDs, just in case.
			'gateway_id',
			'gateway-id',
			// Sometimes the gateway is referred to as 'method'. Stay clear of it.
			'method',
			// If there is a success or error param, better not redirect.
			'success',
			'error',
			// If the URL is nonced, better not redirect.
			'_wpnonce',
		);
		foreach ( $special_request_params as $param ) {
			// phpcs:ignore WordPress.Security.NonceVerification
			if ( isset( $_GET[ $param ] ) ) {
				return;
			}
		}

		// If we reach this point, we can safely redirect to the settings page.
		wp_safe_redirect( $redirect_url );
		exit;
	}
}
