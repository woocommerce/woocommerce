<?php
/**
 * ProductsSettingsPage.
 *
 * @package WooCommerce\Admin
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings\ReactSettingsPages;

use Automattic\WooCommerce\Internal\Admin\Settings\ReactSettingsPageInterface;

defined( 'ABSPATH' ) || exit;

/**
 * React-settings contract for the Products tab.
 *
 * Ports weight-unit, dimension-unit, product-type, and shop-page option
 * synthesis from ReactSettingsSchema's private get_product_field_options()
 * plus its helpers get_unit_options() and get_page_options().
 *
 * @since 10.8.0
 *
 * @see \Automattic\WooCommerce\Internal\Admin\Settings\ReactSettingsSchema::get_product_field_options()
 */
final class ProductsSettingsPage implements ReactSettingsPageInterface {

	/**
	 * {@inheritDoc}
	 */
	public function get_extra_type_map( string $section ): array {
		return array();
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_extra_supported_types( string $section ): array {
		return array();
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_field_options( string $field_id, array $field, string $section ): ?array {
		switch ( $field_id ) {
			case 'woocommerce_weight_unit':
				return $this->reshape( $this->get_unit_options( 'weight', 'woocommerce_weight_units' ) );
			case 'woocommerce_dimension_unit':
				return $this->reshape( $this->get_unit_options( 'dimensions', 'woocommerce_dimension_units' ) );
			case 'woocommerce_product_type':
				return $this->reshape( $this->get_product_type_options() );
			case 'woocommerce_shop_page_id':
				return $this->reshape( $this->get_page_options() );
			default:
				return null;
		}
	}

	/**
	 * Load the unit list for a given bucket from WC's i18n/units.php and narrow it
	 * via the caller-supplied valid-keys filter.
	 *
	 * Ported from ReactSettingsSchema::get_unit_options().
	 *
	 * @param string $bucket Either 'weight' or 'dimensions'.
	 * @param string $filter Filter name that returns the array of valid unit keys.
	 * @return array<string, string>
	 */
	private function get_unit_options( string $bucket, string $filter ): array {
		if ( ! function_exists( 'WC' ) ) {
			return array();
		}

		$units = include WC()->plugin_path() . '/i18n/units.php';
		if ( ! is_array( $units ) || empty( $units[ $bucket ] ) ) {
			return array();
		}

		/** This filter is documented in plugins/woocommerce/src/Internal/RestApi/Routes/V4/Settings/Products/Controller.php */
		$valid_keys = apply_filters( $filter, array_keys( $units[ $bucket ] ) ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingSinceComment

		return is_array( $valid_keys )
			? array_intersect_key( $units[ $bucket ], array_flip( $valid_keys ) )
			: $units[ $bucket ];
	}

	/**
	 * Build the product-type list from wc_get_product_types().
	 *
	 * @return array<string, string>
	 */
	private function get_product_type_options(): array {
		if ( ! function_exists( 'wc_get_product_types' ) ) {
			return array();
		}

		$product_types = wc_get_product_types();
		return is_array( $product_types ) ? $product_types : array();
	}

	/**
	 * Build a page list for the shop-page select with a leading "Select a page…" placeholder.
	 *
	 * Ported from ReactSettingsSchema::get_page_options().
	 *
	 * Return type annotates `array<int|string, string>` because PHP silently coerces
	 * numeric string keys (page IDs) back to ints when assigned as array keys.
	 *
	 * @return array<int|string, string>
	 */
	private function get_page_options(): array {
		if ( ! function_exists( 'get_pages' ) ) {
			return array();
		}

		$pages   = get_pages(
			array(
				'sort_column' => 'menu_order',
				'sort_order'  => 'ASC',
				'post_status' => array( 'publish', 'private', 'draft' ),
			)
		);
		$options = array(
			'' => __( 'Select a page…', 'woocommerce' ),
		);

		if ( ! is_array( $pages ) ) {
			return $options;
		}

		foreach ( $pages as $page ) {
			$options[ (string) $page->ID ] = wp_strip_all_tags( $page->post_title );
		}

		return $options;
	}

	/**
	 * Reshape an associative `[value => label]` map into the interface-contract
	 * `[{label, value}]` list. Preserves iteration order.
	 *
	 * @param array<int|string, string> $map The associative map to reshape.
	 * @return array<int, array{label: string, value: string}>
	 */
	private function reshape( array $map ): array {
		$out = array();
		foreach ( $map as $value => $label ) {
			$out[] = array(
				'label' => (string) $label,
				'value' => (string) $value,
			);
		}
		return $out;
	}
}
