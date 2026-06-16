<?php
/**
 * LocationStockAdminController class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Inventory;

use Automattic\WooCommerce\Enums\ProductType;

defined( 'ABSPATH' ) || exit;

/**
 * Adds POS location stock fields to the classic product editor.
 *
 * @internal
 */
class LocationStockAdminController {

	/**
	 * Feature and configuration gate.
	 *
	 * @var LocationStockGate
	 */
	private LocationStockGate $gate;

	/**
	 * Location stock service.
	 *
	 * @var LocationStockService
	 */
	private LocationStockService $location_stock_service;

	/**
	 * Initialize dependencies.
	 *
	 * @param LocationStockGate    $gate Feature and configuration gate.
	 * @param LocationStockService $location_stock_service Location stock service.
	 *
	 * @internal
	 */
	final public function init( LocationStockGate $gate, LocationStockService $location_stock_service ): void {
		$this->gate                   = $gate;
		$this->location_stock_service = $location_stock_service;
	}

	/**
	 * Register admin product editor hooks.
	 */
	public function register(): void {
		add_action( 'woocommerce_product_options_stock_fields', array( $this, 'render_simple_product_location_fields' ) );
		add_action( 'woocommerce_admin_process_product_object', array( $this, 'save_simple_product_location_fields' ) );
		add_action( 'woocommerce_variation_options_inventory', array( $this, 'render_variation_location_fields' ), 10, 3 );
		add_action( 'woocommerce_admin_process_variation_object', array( $this, 'save_variation_location_fields' ), 10, 2 );
	}

	/**
	 * Render the POS location stock field in the classic product editor.
	 */
	public function render_simple_product_location_fields(): void {
		global $product_object;

		if ( ! $this->gate->can_manage() || ! $product_object instanceof \WC_Product ) {
			return;
		}

		$locations = $this->location_stock_service->get_locations();
		if ( empty( $locations ) ) {
			return;
		}

		echo '<div class="options_group show_if_simple show_if_variable">';
		echo '<p class="form-field"><strong>' . esc_html__( 'POS location stock', 'woocommerce' ) . '</strong></p>';
		foreach ( $locations as $location ) {
			woocommerce_wp_text_input(
				array(
					'id'                => '_inventory_stock_' . $location['slug'],
					'name'              => '_inventory_location_stock[' . $location['slug'] . ']',
					'label'             => sprintf(
						/* translators: %s: POS location name. */
						__( '%s stock', 'woocommerce' ),
						$location['name']
					),
					'value'             => wc_stock_amount( $this->location_stock_service->get_location_stock( $product_object, $location['slug'] ) ),
					'type'              => 'number',
					'custom_attributes' => array(
						'step' => 'any',
					),
				)
			);
		}
		echo '</div>';
	}

	/**
	 * Save the POS location stock field without changing legacy _stock.
	 *
	 * @param \WC_Product $product Product object.
	 */
	public function save_simple_product_location_fields( \WC_Product $product ): void {
		if ( ! $this->gate->can_manage() ) {
			return;
		}

		$location_stock_values = wc_clean( wp_unslash( $_POST['_inventory_location_stock'] ?? array() ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Product save nonce is verified before this hook fires.
		if ( ! is_array( $location_stock_values ) ) {
			return;
		}

		foreach ( $this->location_stock_service->get_locations() as $location ) {
			if ( ! array_key_exists( $location['slug'], $location_stock_values ) ) {
				continue;
			}

			$this->location_stock_service->set_location_stock(
				$product,
				$location['slug'],
				wc_stock_amount( $location_stock_values[ $location['slug'] ] )
			);
		}
	}

	/**
	 * Render the POS location stock field for variation-managed stock.
	 *
	 * @param int      $loop           Position in the loop.
	 * @param array    $variation_data Variation data.
	 * @param \WP_Post $variation      Variation post object.
	 */
	public function render_variation_location_fields( int $loop, array $variation_data, \WP_Post $variation ): void {
		if ( ! $this->gate->can_manage() ) {
			return;
		}

		$locations = $this->location_stock_service->get_locations();
		if ( empty( $locations ) ) {
			return;
		}

		$variation_product = wc_get_product( $variation->ID );
		if ( ! $variation_product instanceof \WC_Product || ! $variation_product->is_type( ProductType::VARIATION ) ) {
			return;
		}

		foreach ( $locations as $location ) {
			woocommerce_wp_text_input(
				array(
					'id'                => 'variable_inventory_stock_' . $location['slug'] . $loop,
					'name'              => 'variable_inventory_location_stock[' . $location['slug'] . "][{$loop}]",
					'label'             => sprintf(
						/* translators: %s: POS location name. */
						__( '%s stock', 'woocommerce' ),
						$location['name']
					),
					'value'             => $this->location_stock_service->get_location_stock_for_product_record( $variation_product, $location['slug'] ),
					'type'              => 'number',
					'custom_attributes' => array(
						'step' => 'any',
					),
					'data_type'         => 'stock',
					'desc_tip'          => true,
					'description'       => esc_html__( 'Set POS stock for this variation. This does not change web stock.', 'woocommerce' ),
					'wrapper_class'     => 'form-row form-row-full',
				)
			);
		}
	}

	/**
	 * Save the POS location stock field for variation-managed stock.
	 *
	 * @param \WC_Product $variation Variation product object.
	 * @param int         $loop      Position in the loop.
	 */
	public function save_variation_location_fields( \WC_Product $variation, int $loop ): void {
		if ( ! $this->gate->can_manage() || ! $variation->is_type( ProductType::VARIATION ) || true !== $variation->get_manage_stock( 'edit' ) ) {
			return;
		}

		$location_stock_values = wc_clean( wp_unslash( $_POST['variable_inventory_location_stock'] ?? array() ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Product save nonce is verified before this hook fires.
		if ( ! is_array( $location_stock_values ) ) {
			return;
		}

		foreach ( $this->location_stock_service->get_locations() as $location ) {
			if ( ! isset( $location_stock_values[ $location['slug'] ] ) || ! is_array( $location_stock_values[ $location['slug'] ] ) || ! array_key_exists( $loop, $location_stock_values[ $location['slug'] ] ) ) {
				continue;
			}

			$this->location_stock_service->set_location_stock(
				$variation,
				$location['slug'],
				wc_stock_amount( $location_stock_values[ $location['slug'] ][ $loop ] )
			);
		}
	}
}
