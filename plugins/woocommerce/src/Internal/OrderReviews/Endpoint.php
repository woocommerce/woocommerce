<?php
/**
 * Endpoint class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\OrderReviews;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Enums\OrderStatus;
use WC_Order;
use WP_Post;

/**
 * Routes `/review-order/{id}/?key={order_key}` to the WooCommerce-managed
 * Review Order page and renders the read-only landing page through the
 * `[woocommerce_review_order]` shortcode.
 *
 * The page is intentionally hosted outside the checkout/my-account family:
 *
 * - It is not a checkout sub-mode like order-pay or order-received; the
 *   customer is reviewing past purchases, not transacting.
 * - It is not a my-account endpoint because the order key is the auth, so
 *   guest customers must be able to reach it without logging in.
 *
 * The route uses the same wp_posts-backed page pattern as the checkout
 * page so the active theme owns the page chrome (header, footer, sidebar)
 * on both classic and block themes; the shortcode only renders the form
 * body inside `the_content`. Any failed gating check renders the theme's
 * 404 template so a leaked or stale link cannot disclose order existence.
 *
 * The container auto-calls `init()` after instantiation, which is where
 * the WordPress hooks are registered. Resolution is driven by the
 * `OrderReviews` wrapper that lists this class as an `init()` argument.
 *
 * @internal Just for internal use.
 *
 * @since 10.8.0
 */
class Endpoint {

	/**
	 * Query var that the rewrite rule sets to the order id.
	 */
	public const QUERY_VAR = 'review-order';

	/**
	 * `wc_get_page_id()` key for the WC-managed Review Order page.
	 */
	public const PAGE_KEY = 'review_order';

	/**
	 * Shortcode tag that renders the page body inside the WC page content.
	 */
	public const SHORTCODE = 'woocommerce_review_order';

	/**
	 * Wire the endpoint into WordPress.
	 *
	 * Auto-called by the WC dependency container after instantiation. The
	 * title-suppression filters are deliberately NOT registered here; they
	 * land inside `gate_request()` once the request is confirmed to be an
	 * authorised review-order render, so they never run on unrelated pages.
	 *
	 * @internal
	 */
	final public function init(): void {
		// Seed the host page before `add_rewrite_rule` runs on init:10.
		add_action( 'init', array( $this, 'maybe_create_host_page' ), 4 );
		add_action( 'init', array( $this, 'add_rewrite_rule' ) );
		add_filter( 'query_vars', array( $this, 'add_query_var' ), 0 );
		add_action( 'template_redirect', array( $this, 'gate_request' ) );
		add_action( 'wp_loaded', array( $this, 'maybe_flush_pending_rewrite' ) );
		add_action( 'transition_post_status', array( $this, 'skip_auto_menu_for_self' ), 9, 3 );
		add_filter( 'get_pages', array( $this, 'exclude_self_from_page_list' ) );
		add_filter( 'display_post_states', array( $this, 'add_post_state_label' ), 10, 2 );
		// Inject our entry into every `WC_Install::create_pages()` invocation so
		// Status → Tools "Create default pages" and any other repair caller see it too.
		add_filter( 'woocommerce_create_pages', array( $this, 'inject_review_order_page' ) );
		add_shortcode( self::SHORTCODE, array( $this, 'render_shortcode' ) );
	}

	/**
	 * Create or adopt the Review Order host page on every feature-on init.
	 *
	 * Idempotent and self-healing: re-aligns the stored option with whichever
	 * row WP's permalink routing would resolve `/review-order/` to, so the
	 * page id `gate_request()` checks always matches the page that
	 * `add_rewrite_rule()` points at. Leftover duplicates from prior
	 * activation/disable cycles no longer cause asset enqueueing to silently
	 * skip.
	 *
	 * @since 10.8.0
	 *
	 * @internal
	 */
	public function maybe_create_host_page(): void {
		// Fast path: the stored option already points at a published page
		// that still embeds our shortcode. `get_post()` is served from the
		// posts cache so this short-circuit costs ~nothing per request and
		// avoids the slug `wp_posts` lookup the reconciliation path runs.
		$option_id   = (int) wc_get_page_id( self::PAGE_KEY );
		$option_page = $option_id > 0 ? get_post( $option_id ) : null;
		if ( $option_page instanceof WP_Post
			&& 'page' === $option_page->post_type
			&& 'publish' === $option_page->post_status
			&& false !== strpos( (string) $option_page->post_content, '[' . self::SHORTCODE . ']' ) ) {
			return;
		}

		// Reconcile: adopt the slug-routed page when it also embeds our
		// shortcode. The combined signal avoids hijacking a merchant page
		// that happens to share either the slug or the shortcode alone.
		$canonical = $this->find_canonical_host_page();
		if ( $canonical instanceof WP_Post ) {
			$needs_save = false;

			if ( $option_id !== (int) $canonical->ID ) {
				update_option( 'woocommerce_review_order_page_id', (int) $canonical->ID );
				$needs_save = true;
			}
			if ( 'publish' !== $canonical->post_status ) {
				wp_update_post(
					array(
						'ID'          => (int) $canonical->ID,
						'post_status' => 'publish',
					)
				);
				$needs_save = true;
			}
			if ( $needs_save ) {
				update_option( 'woocommerce_review_order_flush_rewrite_pending', 'yes' );
			}
			return;
		}

		// No slug-canonical page. If the merchant renamed the host page away
		// from our default slug but the stored option still resolves to a
		// non-trashed page, respect it and only republish a draft we own.
		if ( $option_page instanceof WP_Post && 'page' === $option_page->post_type && 'trash' !== $option_page->post_status ) {
			if ( 'publish' !== $option_page->post_status ) {
				wp_update_post(
					array(
						'ID'          => (int) $option_page->ID,
						'post_status' => 'publish',
					)
				);
				update_option( 'woocommerce_review_order_flush_rewrite_pending', 'yes' );
			}
			return;
		}

		// No managed page anywhere. The permanent `woocommerce_create_pages`
		// filter (registered in `init()`) makes the call inject our entry.
		\WC_Install::create_pages();

		// Defer the rewrite flush to wp_loaded; rewrite_rule fires later on init.
		update_option( 'woocommerce_review_order_flush_rewrite_pending', 'yes' );
	}

	/**
	 * Append the Review Order page to any caller of
	 * `WC_Install::create_pages()` — keeps Status → Tools' "Create default
	 * pages" repair path and any third-party callers seeded with our page
	 * whenever the feature is on, without having to call create_pages()
	 * with a one-off filter in `maybe_create_host_page()`.
	 *
	 * @since 10.8.0
	 *
	 * @internal Public only because WP filter callbacks need to be callable from outside.
	 *
	 * @param array<string,array<string,string>>|mixed $pages Existing page definitions.
	 * @return array<string,array<string,string>>|mixed
	 */
	public function inject_review_order_page( $pages ) {
		if ( ! is_array( $pages ) ) {
			return $pages;
		}
		$pages[ self::PAGE_KEY ] = array(
			'name'    => _x( 'review-order', 'Page slug', 'woocommerce' ),
			'title'   => _x( 'Review your order', 'Page title', 'woocommerce' ),
			'content' => '<!-- wp:shortcode -->[' . self::SHORTCODE . ']<!-- /wp:shortcode -->',
		);
		return $pages;
	}

	/**
	 * Return the slug-routed page if it also embeds our shortcode, so we only
	 * adopt rows that are unambiguously WC-owned (matching slug alone or the
	 * shortcode alone would hijack merchant-authored pages).
	 *
	 * @since 10.8.0
	 *
	 * @return WP_Post|null
	 */
	private function find_canonical_host_page(): ?WP_Post {
		$page = get_page_by_path( _x( 'review-order', 'Page slug', 'woocommerce' ), OBJECT, 'page' );
		if ( ! $page instanceof WP_Post || 'trash' === $page->post_status ) {
			return null;
		}
		if ( false === strpos( (string) $page->post_content, '[' . self::SHORTCODE . ']' ) ) {
			return null;
		}
		return $page;
	}

	/**
	 * Label the Review Order page in the admin Pages list ("— Review Order
	 * Page"), mirroring how `WC_Admin_Post_Types` labels Shop / Cart /
	 * Checkout / My account so editors can spot it at a glance.
	 *
	 * @since 10.8.0
	 *
	 * @internal Public only because WP filter callbacks need to be callable from outside.
	 *
	 * @param array<string,string>|mixed $post_states Existing post-state labels keyed by id.
	 * @param \WP_Post|mixed             $post        Current post being listed.
	 * @return array<string,string>|mixed
	 */
	public function add_post_state_label( $post_states, $post ) {
		if ( ! is_array( $post_states ) || ! $post instanceof \WP_Post ) {
			return $post_states;
		}
		$page_id = (int) wc_get_page_id( self::PAGE_KEY );
		if ( $page_id > 0 && $page_id === (int) $post->ID ) {
			$post_states['wc_page_for_review_order'] = __( 'Review Order Page', 'woocommerce' );
		}
		return $post_states;
	}

	/**
	 * Hide the Review Order page from `get_pages()` results.
	 *
	 * Block themes' `core/page-list` block (and any classic theme using
	 * `wp_list_pages()`) calls `get_pages()` to populate its list. Without
	 * this filter the tokenised landing page would appear in the site
	 * navigation alongside Cart / Checkout / My account, which is wrong:
	 * the page is reachable only through the per-order email link.
	 *
	 * @param \WP_Post[]|mixed $pages Page objects returned by get_pages().
	 * @return \WP_Post[]|mixed
	 */
	public function exclude_self_from_page_list( $pages ) {
		if ( ! is_array( $pages ) || empty( $pages ) ) {
			return $pages;
		}
		$page_id = (int) wc_get_page_id( self::PAGE_KEY );
		if ( $page_id <= 0 ) {
			return $pages;
		}
		return array_values(
			array_filter(
				$pages,
				static function ( $page ) use ( $page_id ) {
					return ! ( $page instanceof \WP_Post ) || (int) $page->ID !== $page_id;
				}
			)
		);
	}

	/**
	 * Suppress the theme-rendered page title for classic themes on the
	 * Review Order page.
	 *
	 * The page body (`templates/order/customer-review-order.php` and the
	 * empty-state template) already prints its own `<h1>`, so the chrome
	 * heading would duplicate the text both visually and for screen readers.
	 *
	 * `gate_request()` registers this filter only after the request passes
	 * the auth check, so on any unrelated render it isn't even on the hook.
	 * Two in-method guards narrow the scope to the page title slot of the
	 * Review Order render itself:
	 *
	 * - The post id must match the Review Order page id, so within the same
	 *   render a nav menu item or "recent posts" widget pointing at another
	 *   post stays intact.
	 * - `in_the_loop() && is_main_query()` keeps the filter scoped to the
	 *   actual page title slot. WP's `wp_get_document_title()` reads the
	 *   post title outside the loop, so the `<title>` tag stays meaningful.
	 *
	 * @since 10.8.0
	 *
	 * @param string|mixed $title   Title being rendered.
	 * @param int|mixed    $post_id Post id the title belongs to.
	 * @return string|mixed
	 */
	public function maybe_hide_page_title( $title, $post_id = 0 ) {
		$page_id = (int) wc_get_page_id( self::PAGE_KEY );
		if ( (int) $post_id !== $page_id ) {
			return $title;
		}
		if ( ! in_the_loop() || ! is_main_query() ) {
			return $title;
		}
		return '';
	}

	/**
	 * Suppress the `core/post-title` block on block themes when it is bound
	 * to the Review Order page itself.
	 *
	 * Block themes render the page title through `core/post-title` rather
	 * than `the_title`, so the classic-theme filter above doesn't catch it.
	 * Two guards keep the suppression narrow (registration is gated by
	 * `gate_request()` so the filter isn't even on the hook for unrelated
	 * renders):
	 *
	 * - The hook is `render_block_core/post-title` so unrelated block types
	 *   (headings, paragraphs, navigation, etc.) never reach this method.
	 * - The block's resolved `context['postId']` must match the Review Order
	 *   page id, so a `core/post-title` rendered inside a Query Loop, a
	 *   related-posts template part, or a footer "recent posts" panel for a
	 *   different post on the same render is untouched.
	 *
	 * @since 10.8.0
	 *
	 * @param string|mixed         $block_content Block markup.
	 * @param array<string,mixed>  $block         Parsed block (unused but kept for filter signature).
	 * @param \WP_Block|mixed|null $instance      Rendering instance carrying context.
	 * @return string|mixed
	 */
	public function maybe_hide_post_title_block( $block_content, $block, $instance = null ) {
		unset( $block );

		if ( ! $instance instanceof \WP_Block ) {
			return $block_content;
		}
		$page_id      = (int) wc_get_page_id( self::PAGE_KEY );
		$block_postid = isset( $instance->context['postId'] ) ? (int) $instance->context['postId'] : 0;
		if ( $block_postid !== $page_id ) {
			return $block_content;
		}
		return '';
	}

	/**
	 * Keep the Review Order page out of nav menus that have "Auto add new
	 * top-level pages" enabled.
	 *
	 * The page is reachable only through the tokenised URL the email sends
	 * out; nobody navigates to it from a menu, so it should never appear
	 * there. WP's `_wp_auto_add_pages_to_menu()` runs on
	 * `transition_post_status` at priority 10. Detach it just before that
	 * for our specific page, then restore it on priority 11 so other
	 * transitions are unaffected.
	 *
	 * Compares by slug rather than by stored option id so it also fires on
	 * the very first install — before `woocommerce_review_order_page_id`
	 * is written.
	 *
	 * @param string   $new_status New post status.
	 * @param string   $old_status Old post status.
	 * @param \WP_Post $post       Post object.
	 */
	public function skip_auto_menu_for_self( $new_status, $old_status, $post ): void {
		unset( $new_status, $old_status );
		if ( ! $post instanceof \WP_Post || 'page' !== $post->post_type ) {
			return;
		}

		// Identify the page by stored option id (post-install) or by the
		// shortcode in its content (during install, before the option
		// exists). Don't compare $post->post_name to 'review-order' alone:
		// WP appends -2/-3/... if the slug already exists.
		$stored_id  = (int) get_option( 'woocommerce_review_order_page_id' );
		$is_by_id   = $stored_id > 0 && $stored_id === (int) $post->ID;
		$is_by_slug = '' === $post->post_name
			? false
			: ( 'review-order' === $post->post_name || 0 === strpos( $post->post_name, 'review-order-' ) );
		$is_by_body = false !== strpos( (string) $post->post_content, '[' . self::SHORTCODE . ']' );
		if ( ! $is_by_id && ! $is_by_slug && ! $is_by_body ) {
			return;
		}

		remove_action( 'transition_post_status', '_wp_auto_add_pages_to_menu', 10 );
		add_action(
			'transition_post_status',
			static function () {
				add_action( 'transition_post_status', '_wp_auto_add_pages_to_menu', 10, 3 );
			},
			11
		);
	}

	/**
	 * Flush rewrite rules once after the Review Order page is seeded or
	 * republished.
	 *
	 * `maybe_create_host_page()` runs on `init` priority 4 and queues the
	 * flush by setting `woocommerce_review_order_flush_rewrite_pending`;
	 * `add_rewrite_rule()` doesn't fire until `init` priority 10, so the
	 * flush has to happen later. `wp_loaded` runs after every `init`
	 * callback, which is the earliest safe moment.
	 */
	public function maybe_flush_pending_rewrite(): void {
		if ( 'yes' !== get_option( 'woocommerce_review_order_flush_rewrite_pending' ) ) {
			return;
		}
		flush_rewrite_rules( false );
		delete_option( 'woocommerce_review_order_flush_rewrite_pending' );
	}

	/**
	 * Register the rewrite rule for the review-order endpoint.
	 *
	 * Maps `/<page-slug>/{id}/` to the WC-managed Review Order page so the
	 * active theme renders its standard page chrome around the shortcode.
	 */
	public function add_rewrite_rule(): void {
		$page_id = (int) wc_get_page_id( self::PAGE_KEY );
		if ( $page_id <= 0 ) {
			return;
		}

		$page = get_post( $page_id );
		if ( ! $page instanceof WP_Post || 'publish' !== $page->post_status ) {
			return;
		}

		// Use the full page-permalink path so hierarchical pages
		// (Review Order page moved under a parent) keep working.
		$permalink = get_permalink( $page_id );
		if ( ! is_string( $permalink ) || '' === $permalink ) {
			return;
		}
		$path = trim( (string) wp_make_link_relative( $permalink ), '/' );
		if ( '' === $path ) {
			return;
		}

		add_rewrite_rule(
			'^' . preg_quote( $path, '/' ) . '/([0-9]+)/?$',
			'index.php?page_id=' . $page_id . '&' . self::QUERY_VAR . '=$matches[1]',
			'top'
		);
	}

	/**
	 * Allow the query var through `WP::parse_request()`.
	 *
	 * @param string[] $vars Query vars.
	 * @return string[]
	 */
	public function add_query_var( array $vars ): array {
		$vars[] = self::QUERY_VAR;
		return $vars;
	}

	/**
	 * Run the gating checks before the page template renders.
	 *
	 * Auth failures fall through to a 404 here rather than inside the
	 * shortcode so the response status is set before any output begins.
	 * On success the request continues into normal page rendering and the
	 * shortcode echoes the body inside `the_content`.
	 */
	public function gate_request(): void {
		global $wp;

		// Only act when the request resolves to the WC-managed Review Order
		// page. A leftover review-order query var on some other page (manual
		// URL tampering, third-party plugin) shouldn't trigger our auth
		// path or 404 an unrelated page.
		$page_id = (int) wc_get_page_id( self::PAGE_KEY );
		if ( $page_id <= 0 || ! is_page( $page_id ) ) {
			return;
		}

		// Use isset() rather than empty() so the literal "0" doesn't slip
		// through to normal WP routing; the auth check 404s on order_id 0.
		if ( ! isset( $wp->query_vars[ self::QUERY_VAR ] ) ) {
			// Visiting the host page directly (no order id in the URL) is a
			// dead end — the shortcode renders nothing and the customer
			// sees a chrome-only page. Send them to the home page instead.
			wp_safe_redirect( home_url( '/' ) );
			exit;
		}

		$order_id  = absint( $wp->query_vars[ self::QUERY_VAR ] );
		$order_key = $this->read_order_key();
		$order     = $order_id ? wc_get_order( $order_id ) : false;

		if ( ! $this->is_authorised( $order, $order_key ) ) {
			$this->render_404();
			exit;
		}

		// Register the page-title suppression filters now that the request
		// is fully authorised. Doing this here instead of `init()` keeps the
		// filters out of every unrelated page render and removes the need
		// for a per-instance "is this an authorised render" boolean.
		add_filter( 'the_title', array( $this, 'maybe_hide_page_title' ), 10, 2 );
		// Block-specific filter so only `core/post-title` is touched —
		// `render_block` would fire for every block on the page. The third
		// arg is the `WP_Block` instance carrying `context['postId']`, used
		// to scope to the host page.
		add_filter( 'render_block_core/post-title', array( $this, 'maybe_hide_post_title_block' ), 10, 3 );

		if ( $order instanceof WC_Order ) {
			$this->maybe_mark_no_actionable_rows( $order );
		}

		// template_redirect fires after wp_enqueue_scripts but before
		// wp_head, so styles registered here are still output in <head>.
		$this->enqueue_assets();
	}

	/**
	 * Render the Review Order page body for the WC-managed page.
	 *
	 * Called by `the_content` on the page that hosts `[woocommerce_review_order]`.
	 * Returns an empty string when the request did not arrive through the
	 * tokenised rewrite, so a logged-in admin previewing the page directly
	 * sees nothing rather than a partial form.
	 *
	 * @return string
	 */
	public function render_shortcode(): string {
		global $wp;

		if ( ! isset( $wp->query_vars[ self::QUERY_VAR ] ) ) {
			return '';
		}

		$order_id = absint( $wp->query_vars[ self::QUERY_VAR ] );
		$order    = $order_id ? wc_get_order( $order_id ) : false;
		if ( ! $order instanceof WC_Order ) {
			// gate_request() will already have 404'd; this is defensive.
			return '';
		}

		ob_start();
		wc_get_template( 'order/customer-review-order.php', array( 'order' => $order ) );
		return (string) ob_get_clean();
	}

	/**
	 * Render the Review Order body directly. Public so unit tests can drive
	 * the rendering path without staging a global request and the rewrite.
	 *
	 * @internal
	 *
	 * @param int $order_id Order id parsed from the URL.
	 */
	public function render( int $order_id ): void {
		$order_key = $this->read_order_key();
		$order     = $order_id ? wc_get_order( $order_id ) : false;

		if ( ! $this->is_authorised( $order, $order_key ) ) {
			$this->render_404();
			return;
		}

		if ( $order instanceof WC_Order ) {
			$this->maybe_mark_no_actionable_rows( $order );
		}

		wc_get_template( 'order/customer-review-order.php', array( 'order' => $order ) );
	}

	/**
	 * Stamp the completed-at meta when the Review Order page would render the
	 * empty-state, so back-button visits and direct revisits also record
	 * completion. The persistent write lives here, in the controller, so the
	 * page template stays read-only.
	 *
	 * Scope differs from `SubmissionHandler::maybe_mark_order_complete()`:
	 * that one counts the customer's reviews per product across all of their
	 * history, while this one walks the per-item decisions ItemEligibility
	 * produces (order-scoped, mirroring exactly what the page renders).
	 *
	 * @param WC_Order $order Order being reviewed.
	 */
	private function maybe_mark_no_actionable_rows( WC_Order $order ): void {
		$completed_meta_key = SubmissionHandler::COMPLETED_META_KEY;
		if ( $order->get_meta( $completed_meta_key ) ) {
			return;
		}

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- documented on customer-review-order.php template.
		$items = (array) apply_filters( 'woocommerce_review_order_eligible_items', $order->get_items(), $order );
		ItemEligibility::preload_for_items( $items, $order );

		foreach ( $items as $item ) {
			if ( ! $item instanceof \WC_Order_Item_Product ) {
				continue;
			}
			$decision = ItemEligibility::decide( $item, $order );
			// Skip rows are intentionally treated as "done": an order whose
			// items all have reviews disabled renders the empty-state, so we
			// stamp completion to match what the customer sees on the page.
			if ( ItemEligibility::STATUS_SKIP === $decision['status'] ) {
				continue;
			}
			// Any non-skip row without a review tied to this order means the
			// customer still has something to submit — order isn't complete.
			if ( ! ( $decision['comment'] instanceof \WP_Comment ) ) {
				return;
			}
		}

		$order->update_meta_data( $completed_meta_key, (string) time() );

		try {
			$order->save();
		} catch ( \Exception $e ) {
			wc_get_logger()->warning(
				sprintf(
					/* translators: 1: order ID, 2: error message */
					__( 'Could not stamp Review Order completion meta on order %1$d: %2$s.', 'woocommerce' ),
					$order->get_id(),
					$e->getMessage()
				),
				array( 'source' => 'order-reviews' )
			);
		}
	}

	/**
	 * Build the public, tokenised URL for an order's review-order page.
	 *
	 * @param WC_Order $order Order to build the URL for.
	 * @return string
	 */
	public static function get_url( WC_Order $order ): string {
		$page_id   = (int) wc_get_page_id( self::PAGE_KEY );
		$permalink = (string) ( $page_id > 0 ? get_permalink( $page_id ) : '' );

		if ( '' === $permalink ) {
			$url = '';
		} elseif ( false === strpos( $permalink, '?' ) ) {
			// Pretty permalinks: append the order id as a path segment.
			$url = trailingslashit( $permalink ) . (string) $order->get_id() . '/';
			$url = add_query_arg( 'key', $order->get_order_key(), $url );
		} else {
			// Plain permalinks: page permalink is /?page_id=NNN, so add the
			// order id as a query var rather than munging the path.
			$url = add_query_arg(
				array(
					self::QUERY_VAR => (string) $order->get_id(),
					'key'           => $order->get_order_key(),
				),
				$permalink
			);
		}

		/**
		 * Filter the Review Order URL that the review-request email links to.
		 *
		 * @since 10.8.0
		 *
		 * @param string   $url   The review-order URL.
		 * @param WC_Order $order The order object.
		 */
		return (string) apply_filters( 'woocommerce_review_order_url', $url, $order );
	}

	/**
	 * Read the order key from the request, sanitised.
	 *
	 * @return string
	 */
	private function read_order_key(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only landing page; the order key is the auth.
		$raw = ( isset( $_GET['key'] ) && is_string( $_GET['key'] ) ) ? wc_clean( wp_unslash( $_GET['key'] ) ) : '';
		return is_string( $raw ) ? $raw : '';
	}

	/**
	 * Decide whether the request is allowed to render the page.
	 *
	 * @param mixed  $order     The candidate order. Anything other than a `WC_Order` fails.
	 * @param string $order_key The order key supplied via query arg.
	 * @return bool
	 */
	private function is_authorised( $order, string $order_key ): bool {
		if ( ! $order instanceof WC_Order ) {
			return false;
		}

		if ( '' === $order_key || ! hash_equals( $order->get_order_key(), $order_key ) ) {
			return false;
		}

		/**
		 * Filter the order statuses that are eligible to access the Review Order page.
		 *
		 * The scheduler unschedules pending sends on refund/cancel/trash/delete, but
		 * emails already in the customer's inbox can still be clicked. The route-level
		 * check blocks those late clicks for orders that have moved out of the
		 * eligible set.
		 *
		 * @since 10.8.0
		 *
		 * @param string[] $eligible_statuses Status slugs without the `wc-` prefix.
		 * @param WC_Order $order             The order being reviewed.
		 */
		$eligible_statuses = (array) apply_filters(
			'woocommerce_review_order_eligible_statuses',
			array( OrderStatus::COMPLETED ),
			$order
		);

		if ( ! in_array( $order->get_status(), $eligible_statuses, true ) ) {
			return false;
		}

		// Logged-in customer must own the order. Guests with the order key still pass.
		if ( $order->get_customer_id() && is_user_logged_in() && get_current_user_id() !== $order->get_customer_id() ) {
			return false;
		}

		return true;
	}

	/**
	 * Enqueue the JS and CSS that progressively enhance the page.
	 *
	 * Both files live under `client/legacy/` and are built into
	 * `assets/{js|css}/` by the classic-assets pipeline.
	 */
	private function enqueue_assets(): void {
		$plugin_url = untrailingslashit( plugins_url( '', WC_PLUGIN_FILE ) );
		$suffix     = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$version    = Constants::get_constant( 'WC_VERSION' );
		$asset_url  = static function ( string $path ) use ( $plugin_url ): string {
			// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- documented in includes/class-wc-frontend-scripts.php.
			return (string) apply_filters( 'woocommerce_get_asset_url', $plugin_url . $path, $path );
		};

		wp_enqueue_style( 'wc-order-review', $asset_url( '/assets/css/order-review.css' ), array(), $version );
		// Tell WP to swap to the *-rtl.css variant on RTL sites.
		wp_style_add_data( 'wc-order-review', 'rtl', 'replace' );

		wp_enqueue_script(
			'wc-order-review',
			$asset_url( '/assets/js/frontend/order-review' . $suffix . '.js' ),
			array(),
			$version,
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);

		wp_localize_script(
			'wc-order-review',
			'wcOrderReview',
			array(
				'i18n' => array(
					'ok'                 => __( 'Thanks, your review is live.', 'woocommerce' ),
					'pending_moderation' => __( 'Thanks, your review is pending approval.', 'woocommerce' ),
					'error'              => __( 'Something went wrong, please try again.', 'woocommerce' ),
					'rating_required'    => __( 'Please rate this product before submitting your review.', 'woocommerce' ),
				),
			)
		);
	}

	/**
	 * Mark the current request as a 404 and load the theme's 404 template.
	 *
	 * Fails closed on every gating check so a stale or tampered link cannot
	 * disclose order existence.
	 */
	private function render_404(): void {
		global $wp_query;

		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();

		$template = get_query_template( '404' );
		if ( ! empty( $template ) && file_exists( $template ) ) {
			include $template;
			return;
		}

		// Fallback when the active theme has no 404 template: emit a minimal
		// page so the response body isn't empty.
		printf(
			'<!doctype html><html><head><meta charset="utf-8"><title>%1$s</title></head><body><h1>%1$s</h1></body></html>',
			esc_html__( 'Page not found', 'woocommerce' )
		);
	}
}
