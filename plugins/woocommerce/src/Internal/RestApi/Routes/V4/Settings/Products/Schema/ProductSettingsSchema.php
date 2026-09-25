<?php
/**
 * REST API Product Settings Schema
 *
 * Handles schema definition for product settings.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4\Settings\Products\Schema;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\RestApi\Routes\V4\AbstractSchema;
use WP_REST_Request;

/**
 * Product Settings Schema Class.
 */
class ProductSettingsSchema extends AbstractSchema {
	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'product_settings';

	/**
	 * Describe product settings as a flat map of setting IDs to values.
	 *
	 * @since 11.2.0
	 * @return array The item schema.
	 */
	public function get_item_schema(): array {
		$schema                         = parent::get_item_schema();
		$schema['additionalProperties'] = array(
			'type'  => array( 'string', 'number', 'array', 'boolean' ),
			'items' => array( 'type' => 'string' ),
		);

		return $schema;
	}

	/**
	 * Return product setting values without field or layout metadata.
	 *
	 * @since 11.2.0
	 * @param mixed           $item           Raw setting definitions.
	 * @param WP_REST_Request $request        Request object.
	 * @param array           $include_fields Fields to include.
	 * @phpstan-param WP_REST_Request<array> $request
	 * @return array Setting values keyed by ID.
	 */
	public function get_item_response( $item, WP_REST_Request $request, array $include_fields = array() ): array {
		$settings = array_filter(
			$item,
			static function ( $setting ) {
				return ! empty( $setting['id'] ) && ! in_array( $setting['type'] ?? '', array( 'title', 'sectionend' ), true );
			}
		);

		// Prime caches to reduce future queries.
		wp_prime_option_caches( array_column( $settings, 'id' ) );

		$values = array();
		foreach ( $settings as $setting ) {
			$setting_id            = $setting['id'];
			$raw_value             = get_option( $setting_id, $setting['default'] ?? '' );
			$values[ $setting_id ] = $this->validate_field_value(
				$raw_value,
				$this->normalize_field_type( $setting['type'] ?? 'text' )
			);
		}

		return $values;
	}

	/**
	 * Normalize WooCommerce field types to REST API field types.
	 *
	 * @param string $wc_type WooCommerce field type.
	 * @return string Normalized field type.
	 */
	private function normalize_field_type( string $wc_type ): string {
		$type_map = array(
			'single_select_product' => 'select',
			'multi_select_product'  => 'multiselect',
		);

		return $type_map[ $wc_type ] ?? $wc_type;
	}

	/**
	 * Validate and sanitize field value based on its type.
	 *
	 * @param mixed  $value Field value.
	 * @param string $type  Field type.
	 * @return mixed Validated value.
	 */
	private function validate_field_value( $value, string $type ) {
		switch ( $type ) {
			case 'number':
				return is_numeric( $value ) ? (float) $value : 0;
			case 'checkbox':
				if ( function_exists( 'wc_string_to_bool' ) ) {
					return wc_string_to_bool( $value );
				}
				if ( is_bool( $value ) ) {
					return $value;
				}
				return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
			case 'multiselect':
				return is_array( $value ) ? $value : array();
			case 'text':
			case 'select':
			default:
				return is_string( $value ) ? $value : (string) $value;
		}
	}
}
