<?php
/**
 * Products settings adapter for settings UI.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings\SettingsUIPages;

use Automattic\WooCommerce\Admin\Settings\LegacySettingsPageAdapter;

defined( 'ABSPATH' ) || exit;

/**
 * Adapts the WooCommerce Products settings page for the settings UI renderer.
 *
 * @since 10.9.0
 */
final class ProductsSettingsPageAdapter extends LegacySettingsPageAdapter {

	/**
	 * Build the canonical settings schema for a section.
	 *
	 * @param string $section Section id. Empty string means the default section.
	 * @return array
	 */
	public function get_schema( string $section ): array {
		$schema = parent::get_schema( $section );

		$schema['shell']['title'] = __( 'Product settings', 'woocommerce' );

		if ( '' === $section ) {
			$schema = $this->with_field_options(
				$schema,
				'woocommerce_shop_page_id',
				$this->get_page_options()
			);
		}

		return $schema;
	}

	/**
	 * Add options to a field in a schema.
	 *
	 * @param array  $schema Schema.
	 * @param string $field_id Field id.
	 * @param array  $options Field options.
	 * @return array
	 */
	private function with_field_options( array $schema, string $field_id, array $options ): array {
		if ( empty( $options ) || ! isset( $schema['groups'] ) || ! is_array( $schema['groups'] ) ) {
			return $schema;
		}

		foreach ( $schema['groups'] as $group_id => $group ) {
			if ( ! isset( $group['fields'] ) || ! is_array( $group['fields'] ) ) {
				continue;
			}

			foreach ( $group['fields'] as $field_index => $field ) {
				if ( ! is_array( $field ) || ( $field['id'] ?? null ) !== $field_id ) {
					continue;
				}

				$schema['groups'][ $group_id ]['fields'][ $field_index ]['options'] = $options;
				return $schema;
			}
		}

		return $schema;
	}

	/**
	 * Build the page options for the shop page selector.
	 *
	 * @return array<int, array{label: string, value: string}>
	 */
	private function get_page_options(): array {
		$pages = get_pages(
			array(
				'sort_column' => 'menu_order',
				'sort_order'  => 'ASC',
				'post_status' => array( 'publish', 'private', 'draft' ),
			)
		);

		$options = array(
			array(
				'label' => __( 'Select a page...', 'woocommerce' ),
				'value' => '',
			),
		);

		if ( ! is_array( $pages ) ) {
			return $options;
		}

		foreach ( $pages as $page ) {
			$options[] = array(
				'label' => wp_strip_all_tags( $page->post_title ),
				'value' => (string) $page->ID,
			);
		}

		return $options;
	}
}
