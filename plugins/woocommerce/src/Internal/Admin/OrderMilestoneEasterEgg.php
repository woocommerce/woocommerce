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
		 * @since 10.9.0
		 * @param bool $enabled Whether the feature is enabled. Default true.
		 */
		if ( ! apply_filters( 'wc_order_milestone_egg_enabled', true ) ) {
			return;
		}

		if ( ! function_exists( 'wc_get_orders' ) ) {
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$woo_egg_key  = isset( $_GET['woo_egg'] ) ? sanitize_text_field( wp_unslash( $_GET['woo_egg'] ) ) : '';
		$page_param   = isset( $_GET['page'] )     ? sanitize_text_field( wp_unslash( $_GET['page'] ) )    : '';
		$action_param = isset( $_GET['action'] )   ? sanitize_text_field( wp_unslash( $_GET['action'] ) )  : '';
		$id_param     = isset( $_GET['id'] )       ? absint( wp_unslash( $_GET['id'] ) )                   : 0;
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

		$asset_file = WC_ABSPATH . 'assets/client/admin/wp-admin-scripts/order-milestone-easter-egg.asset.php';
		$asset      = file_exists( $asset_file )
			? require $asset_file
			: array( 'dependencies' => array(), 'version' => WC_VERSION );

		wp_register_script(
			'wc-order-milestone-easter-egg',
			plugins_url( 'assets/client/admin/wp-admin-scripts/order-milestone-easter-egg.js', WC_PLUGIN_FILE ),
			$asset['dependencies'],
			$asset['version'],
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);

		wp_enqueue_script( 'wc-order-milestone-easter-egg' );

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

		wp_localize_script( 'wc-order-milestone-easter-egg', 'wcOrderMilestoneEgg', $localize_data );
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
	 * Paginates through processing/completed orders in chronological order until
	 * 1000 orders with a transaction ID have been collected, ensuring milestone
	 * positions are counted against qualifying orders only.
	 *
	 * @return array<int, array<string, string>>
	 */
	private function get_milestone_map(): array {
		$qualifying_order_ids = array();
		$page                 = 1;
		$batch_size           = 100;

		while ( count( $qualifying_order_ids ) < 1000 ) {
			$batch = (array) wc_get_orders(
				array(
					'limit'   => $batch_size,
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
					if ( count( $qualifying_order_ids ) >= 1000 ) {
						break 2;
					}
				}
			}

			if ( count( $batch ) < $batch_size ) {
				break;
			}

			++$page;
		}

		$positions     = array(
			0   => 'first',
			99  => 'hundred',
			999 => 'thousand',
		);
		$messages      = $this->get_milestone_messages();
		$milestone_map = array();

		foreach ( $positions as $pos => $key ) {
			if ( isset( $qualifying_order_ids[ $pos ] ) ) {
				$milestone_map[ (int) $qualifying_order_ids[ $pos ] ] = $messages[ $key ];
			}
		}

		/**
		 * Filters the map of milestone order IDs to their milestone data.
		 *
		 * @since 10.9.0
		 * @param array<int, array<string, string>> $milestone_map Map of order ID to milestone data.
		 */
		return apply_filters( 'wc_order_milestone_egg_map', $milestone_map );
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
				'variant'  => 'lama',
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
			'lama'  => 'woo-pinata-lama2.svg',
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
	 * @param string[] $variants Variant keys to load (e.g. ['lama', 'octo']).
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
