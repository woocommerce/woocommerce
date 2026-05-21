<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Admin;

/**
 * Displays a full-screen animated piñata overlay when a merchant opens a
 * milestone order (1st, 100th, or 1000th real order) in the admin.
 *
 * Fires for any real paid order: status is processing or completed and a
 * transaction ID is present.
 *
 * @since 10.9.0
 */
class OrderMilestoneEasterEgg {

	/**
	 * Option key used to cache computed milestone order IDs.
	 */
	private const MILESTONE_CACHE_OPTION = '_wc_order_milestone_egg_order_ids';

	/**
	 * Maximum number of qualifying orders needed to resolve all milestones.
	 */
	private const MAX_QUALIFYING_ORDERS = 1000;

	/**
	 * Number of orders to inspect per milestone scan batch.
	 */
	private const QUERY_BATCH_SIZE = 100;

	/**
	 * Milestone positions mapped to milestone message keys.
	 */
	private const MILESTONE_POSITIONS = array(
		0   => 'first',
		99  => 'hundred',
		999 => 'thousand',
	);

	/**
	 * Sets up the hooks.
	 *
	 * @internal
	 *
	 * @since 10.9.0
	 */
	final public function init(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'handle_admin_enqueue_scripts' ) );
		add_action( 'wp_ajax_wc_egg_dismiss', array( $this, 'handle_ajax_dismiss' ) );
		add_action( 'wp_ajax_wc_egg_opt_out', array( $this, 'handle_ajax_opt_out' ) );
		add_action( 'woocommerce_new_order', array( $this, 'clear_milestone_cache' ), 10, 0 );
		add_action( 'woocommerce_update_order', array( $this, 'clear_milestone_cache' ), 10, 0 );
		add_action( 'woocommerce_delete_order', array( $this, 'clear_milestone_cache' ), 10, 0 );
		add_action( 'woocommerce_trash_order', array( $this, 'clear_milestone_cache' ), 10, 0 );
	}

	/**
	 * Clears the cached milestone order IDs.
	 *
	 * @internal
	 */
	public function clear_milestone_cache(): void {
		delete_option( self::MILESTONE_CACHE_OPTION );
	}

	/**
	 * Opts the current user out of all future milestone overlays.
	 *
	 * @internal
	 */
	public function handle_ajax_opt_out(): void {
		check_ajax_referer( 'wc_egg_dismiss', 'nonce' );
		update_user_meta( get_current_user_id(), '_wc_egg_opted_out', '1' );
		wp_die();
	}

	/**
	 * Marks a milestone order as dismissed for the current user.
	 *
	 * @internal
	 */
	public function handle_ajax_dismiss(): void {
		check_ajax_referer( 'wc_egg_dismiss', 'nonce' );
		$order_id = isset( $_POST['order_id'] ) ? absint( wp_unslash( $_POST['order_id'] ) ) : 0;
		if ( $order_id > 0 ) {
			update_user_meta( get_current_user_id(), '_wc_egg_seen_' . $order_id, '1' );
		}
		wp_die();
	}

	/**
	 * Enqueues the milestone overlay script when the current order is a qualifying milestone.
	 *
	 * @internal
	 */
	public function handle_admin_enqueue_scripts(): void {
		/**
		 * Filters whether the order milestone easter egg feature is enabled.
		 *
		 * Return false to disable the feature entirely — no order queries or assets will be loaded.
		 *
		 * @param bool $enabled Whether the feature is enabled. Default true.
		 *
		 * @since 10.9.0
		 */
		if ( ! apply_filters( 'wc_order_milestone_egg_enabled', true ) ) {
			return;
		}

		if ( ! function_exists( 'wc_get_orders' ) ) {
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$woo_egg_key  = isset( $_GET['woo_egg'] ) ? sanitize_text_field( wp_unslash( $_GET['woo_egg'] ) ) : '';
		$page_param   = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		$action_param = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';
		$id_param     = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		// Preview: ?woo_egg=first|hundred|thousand lets admins preview any milestone without real orders.
		// Only available when WP_DEBUG is enabled to prevent accidental triggering in production.
		$is_debug_preview = ( defined( 'WP_DEBUG' ) && WP_DEBUG ) && current_user_can( 'manage_options' ) && '' !== $woo_egg_key;

		// Respect the user's opt-out preference (debug preview always shows).
		if ( ! $is_debug_preview && get_user_meta( get_current_user_id(), '_wc_egg_opted_out', true ) ) {
			return;
		}

		// Only run the order query on the HPOS order edit page to avoid overhead on every admin page.
		$is_order_edit_page = 'wc-orders' === $page_param && 'edit' === $action_param;

		if ( ! $is_debug_preview && ! $is_order_edit_page ) {
			return;
		}

		// For real order pages: check cheaply whether the current order qualifies
		// before running the more expensive milestone count query.
		if ( ! $is_debug_preview ) {
			if ( $id_param <= 0 || ! $this->is_qualifying_order( $id_param ) ) {
				return;
			}
		}

		$milestone_map = $is_debug_preview ? array() : $this->get_milestone_map();

		if ( ! $is_debug_preview && empty( $milestone_map ) ) {
			return;
		}

		// Remove milestones the current user has already seen.
		if ( ! $is_debug_preview ) {
			$user_id = get_current_user_id();
			foreach ( array_keys( $milestone_map ) as $order_id ) {
				if ( get_user_meta( $user_id, '_wc_egg_seen_' . $order_id, true ) ) {
					unset( $milestone_map[ $order_id ] );
				}
			}
			if ( empty( $milestone_map ) ) {
				return;
			}

			// Only show the overlay when the current order is itself the milestone.
			if ( ! isset( $milestone_map[ $id_param ] ) ) {
				return;
			}
			$milestone_map = array( $id_param => $milestone_map[ $id_param ] );
		}

		// Only load the SVG variants needed for the matched milestones.
		$all_msgs = array();
		if ( $is_debug_preview ) {
			$all_msgs        = $this->get_milestone_messages();
			$preview_variant = $all_msgs[ $woo_egg_key ]['variant'] ?? null;
			$needed_variants = $preview_variant ? array( $preview_variant ) : array_keys( $this->get_variant_map() );
		} else {
			$needed_variants = array_unique(
				array_filter( array_column( array_values( $milestone_map ), 'variant' ) )
			);
		}

		$svg_data = $this->get_svg_data( $needed_variants );
		$labels   = $this->get_ui_labels();

		WCAdminAssets::register_script( 'wp-admin-scripts', 'order-milestone-easter-egg', true );

		$localize_data = array(
			'milestones' => $milestone_map,
			'svgData'    => $svg_data,
			'labels'     => $labels,
			'dismiss'    => array(
				'url'   => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'wc_egg_dismiss' ),
			),
		);

		if ( $is_debug_preview ) {
			$localize_data['allMilestones'] = $all_msgs;
		}

		wp_localize_script( 'wc-admin-order-milestone-easter-egg', 'wcOrderMilestoneEgg', $localize_data );
	}

	/**
	 * Returns true if the given order qualifies for milestone consideration: status is
	 * processing or completed, and a transaction ID is present.
	 *
	 * Used as a cheap pre-filter before running the full milestone count query.
	 *
	 * @param int $order_id The order ID to check.
	 * @return bool
	 */
	public function is_qualifying_order( int $order_id ): bool {
		$order = wc_get_order( $order_id );
		if ( ! $order instanceof \WC_Order ) {
			return false;
		}
		return '' !== $order->get_transaction_id()
			&& in_array( $order->get_status(), array( 'processing', 'completed' ), true );
	}

	/**
	 * Returns a map of milestone order IDs to their milestone data.
	 *
	 * Uses cached milestone order IDs when available; otherwise computes and caches
	 * them by scanning qualifying orders in chronological order.
	 *
	 * @return array<int, array<string, string>>
	 */
	private function get_milestone_map(): array {
		$milestone_order_ids = $this->get_cached_milestone_order_ids();
		if ( null === $milestone_order_ids ) {
			$milestone_order_ids = $this->compute_milestone_order_ids();
			update_option( self::MILESTONE_CACHE_OPTION, $milestone_order_ids, false );
		}

		$messages      = $this->get_milestone_messages();
		$milestone_map = array();

		foreach ( $milestone_order_ids as $key => $order_id ) {
			if ( isset( $messages[ $key ] ) ) {
				$milestone_map[ $order_id ] = $messages[ $key ];
			}
		}

		/**
		 * Filters the map of milestone order IDs to their milestone data.
		 *
		 * @param array<int, array<string, string>> $milestone_map Map of order ID to milestone data.
		 *
		 * @since 10.9.0
		 */
		return apply_filters( 'wc_order_milestone_egg_map', $milestone_map );
	}

	/**
	 * Returns cached milestone order IDs, or null when the cache is missing.
	 *
	 * @return array<string, int>|null
	 */
	private function get_cached_milestone_order_ids(): ?array {
		$cached = get_option( self::MILESTONE_CACHE_OPTION, null );
		if ( ! is_array( $cached ) ) {
			return null;
		}

		$milestone_order_ids = array();
		foreach ( self::MILESTONE_POSITIONS as $key ) {
			if ( isset( $cached[ $key ] ) ) {
				$order_id = absint( $cached[ $key ] );
				if ( $order_id > 0 ) {
					$milestone_order_ids[ $key ] = $order_id;
				}
			}
		}

		return $milestone_order_ids;
	}

	/**
	 * Computes milestone order IDs by scanning qualifying orders in chronological order.
	 *
	 * @return array<string, int>
	 */
	private function compute_milestone_order_ids(): array {
		$qualifying_order_ids       = array();
		$qualifying_order_ids_count = 0;
		$page                       = 1;

		while ( $qualifying_order_ids_count < self::MAX_QUALIFYING_ORDERS ) {
			$batch = (array) wc_get_orders(
				array(
					'limit'   => self::QUERY_BATCH_SIZE,
					'paged'   => $page,
					'orderby' => 'date',
					'order'   => 'ASC',
					'status'  => array( 'processing', 'completed' ),
					'return'  => 'objects',
				)
			);

			if ( empty( $batch ) ) {
				break;
			}

			foreach ( $batch as $order ) {
				if ( $order instanceof \WC_Order && '' !== $order->get_transaction_id() ) {
					$qualifying_order_ids[] = $order->get_id();
					++$qualifying_order_ids_count;
					if ( $qualifying_order_ids_count >= self::MAX_QUALIFYING_ORDERS ) {
						break 2;
					}
				}
			}

			if ( count( $batch ) < self::QUERY_BATCH_SIZE ) {
				break;
			}

			++$page;
		}

		$milestone_order_ids = array();
		foreach ( self::MILESTONE_POSITIONS as $pos => $key ) {
			if ( isset( $qualifying_order_ids[ $pos ] ) ) {
				$milestone_order_ids[ $key ] = (int) $qualifying_order_ids[ $pos ];
			}
		}

		return $milestone_order_ids;
	}

	/**
	 * Returns milestone copy and variant configuration keyed by milestone name.
	 *
	 * @return array<string, array<string, string>>
	 */
	private function get_milestone_messages(): array {
		return array(
			'first'    => array(
				'title'    => __( 'Cha-ching! Order number one', 'woocommerce' ),
				'subtitle' => __( "That's a big deal. Smash the llama. You've earned it.", 'woocommerce' ),
				'variant'  => 'llama',
				'boomText' => __( 'One down', 'woocommerce' ),
			),
			'hundred'  => array(
				'title'    => __( 'Triple digits looks good on you', 'woocommerce' ),
				'subtitle' => __( "A hundred orders means you're juggling a lot. Take a moment to celebrate", 'woocommerce' ),
				'variant'  => 'octo',
				'boomText' => __( 'Hands full', 'woocommerce' ),
			),
			'thousand' => array(
				'title'    => __( 'ONE. THOUSAND. ORDERS', 'woocommerce' ),
				'subtitle' => __( 'Seriously. A thousand orders. This called for a bigger piñata', 'woocommerce' ),
				'variant'  => 'whale',
				'boomText' => __( 'Off the charts', 'woocommerce' ),
			),
		);
	}

	/**
	 * Returns translated UI labels for the overlay script.
	 *
	 * @return array<string, string>
	 */
	private function get_ui_labels(): array {
		return array(
			'cta'        => __( "Let's go!", 'woocommerce' ),
			'closeLabel' => __( 'Close', 'woocommerce' ),
			'closeTitle' => __( 'Close (Esc)', 'woocommerce' ),
			'optOut'     => __( "Don't show again", 'woocommerce' ),
		);
	}

	/**
	 * Returns the map of variant keys to their SVG filenames.
	 *
	 * @return array<string, string>
	 */
	private function get_variant_map(): array {
		return array(
			'llama' => 'woo-pinata-llama2.svg',
			'octo'  => 'woo-octo.svg',
			'whale' => 'woo-whale.svg',
		);
	}

	/**
	 * Loads and returns SVG assets as inline strings.
	 *
	 * Only the variant SVGs listed in $variants are loaded; shared assets
	 * (confetti, stick, sprinkle) are always included.
	 *
	 * @param string[] $variants Variant keys to load (e.g. ['llama', 'octo']).
	 * @return array<string, string>
	 */
	private function get_svg_data( array $variants = array() ): array {
		$svg_dir = WC_ABSPATH . 'assets/images/pinata/';

		if ( empty( $variants ) ) {
			$variants = array_keys( $this->get_variant_map() );
		}

		$svg_data    = array();
		$variant_map = $this->get_variant_map();

		// phpcs:disable WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		foreach ( $variants as $key ) {
			if ( isset( $variant_map[ $key ] ) ) {
				$svg_data[ $key ] = (string) file_get_contents( $svg_dir . $variant_map[ $key ] );
			}
		}

		$svg_data['confetti'] = (string) file_get_contents( $svg_dir . 'confetti.svg' );
		$svg_data['stick']    = (string) file_get_contents( $svg_dir . 'stick.svg' );
		$sprinkle_svg         = (string) file_get_contents( $svg_dir . 'sprinkle.svg' );
		// phpcs:enable WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		$sprinkle_svg = preg_replace( '/<defs>.*?<\/defs>/s', '', $sprinkle_svg ) ?? '';
		$sprinkle_svg = preg_replace( '/\s*clip-path="[^"]*"/', '', $sprinkle_svg ) ?? '';
		$sprinkle_svg = preg_replace( '/<rect[^>]+fill="white"[^>]*\/?>/', '', $sprinkle_svg ) ?? '';

		$svg_data['sprinkle'] = $sprinkle_svg;

		return $svg_data;
	}
}
