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

use Automattic\WooCommerce\Internal\Admin\Settings\ReactSettingsSchema;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\AbstractSchema;
use WC_Settings_Products;
use WP_REST_Request;

/**
 * Product Settings Schema Class.
 *
 * The constructor performs hook registration for the
 * `woocommerce_react_settings_field_options` filter so that consuming code
 * gets the tab-specific option generation behaviour simply by instantiating
 * the schema. This is atypical for the `src/Internal/*` codebase — see the
 * follow-up note on the constructor docblock about moving registration into
 * the V4 Settings REST controller's init path.
 */
class ProductSettingsSchema extends AbstractSchema {
	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'product_settings';

	/**
	 * Constructor.
	 *
	 * Registers the tab-specific field options callback on the shared
	 * `woocommerce_react_settings_field_options` filter exposed by
	 * ReactSettingsSchema. The callback only injects options for field IDs
	 * owned by the products settings tab, so it is safe to register globally.
	 *
	 * `has_filter()` is used to avoid double-registration under DI container
	 * instantiation or test re-instantiation.
	 *
	 * @since 10.8.0
	 *
	 * @todo Move the filter registration into the V4 Settings REST
	 *       controller's init path so schema classes don't perform global
	 *       hook side effects at construction time.
	 */
	public function __construct() {
		if ( ! has_filter( 'woocommerce_react_settings_field_options', array( self::class, 'inject_field_options' ) ) ) {
			add_filter( 'woocommerce_react_settings_field_options', array( self::class, 'inject_field_options' ), 10, 4 );
		}
	}

	/**
	 * Return all properties for the item schema.
	 *
	 * @return array The schema properties.
	 */
	public function get_item_schema_properties(): array {
		return array(
			'id'          => array(
				'description' => __( 'Unique identifier for the settings group.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'title'       => array(
				'description' => __( 'Settings title.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'description' => array(
				'description' => __( 'Settings description.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'values'      => array(
				'description'          => __( 'Flat key-value mapping of all setting field values.', 'woocommerce' ),
				'type'                 => 'object',
				'context'              => array( 'view', 'edit' ),
				'additionalProperties' => array(
					'description' => __( 'Setting field value.', 'woocommerce' ),
					'type'        => array( 'string', 'number', 'array', 'boolean' ),
				),
			),
			'groups'      => array(
				'description'          => __( 'Collection of setting groups.', 'woocommerce' ),
				'type'                 => 'object',
				'context'              => array( 'view', 'edit' ),
				'additionalProperties' => array(
					'type'        => 'object',
					'description' => __( 'Settings group.', 'woocommerce' ),
					'properties'  => array(
						'title'       => array(
							'description' => __( 'Group title.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'description' => array(
							'description' => __( 'Group description.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'order'       => array(
							'description' => __( 'Display order for the group.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'fields'      => array(
							'description' => __( 'Settings fields.', 'woocommerce' ),
							'type'        => 'array',
							'context'     => array( 'view', 'edit' ),
							'items'       => $this->get_field_schema(),
						),
					),
				),
			),
		);
	}

	/**
	 * Get the schema for individual setting fields.
	 *
	 * @return array
	 */
	private function get_field_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'id'      => array(
					'description' => __( 'Setting field ID.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'label'   => array(
					'description' => __( 'Setting field label.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'type'    => array(
					'description' => __( 'Setting field type.', 'woocommerce' ),
					'type'        => 'string',
					'enum'        => array( 'text', 'number', 'select', 'multiselect', 'checkbox' ),
					'context'     => array( 'view', 'edit' ),
				),
				'options' => array(
					'description' => __( 'Available options for select/multiselect fields.', 'woocommerce' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
				),
				'desc'    => array(
					'description' => __( 'Description for the setting field.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
			),
		);
	}

	/**
	 * Get product settings data by transforming WC_Settings_Products data into REST API format.
	 *
	 * Delegates the actual transform to ReactSettingsSchema::build_response() so
	 * there's one canonical transformer shared with the modernised admin UI
	 * preloader.
	 *
	 * @param mixed           $item             Settings products instance.
	 * @param WP_REST_Request $request          Request object.
	 * @param array           $include_fields   Fields to include.
	 * @return array
	 */
	public function get_item_response( $item, WP_REST_Request $request, array $include_fields = array() ): array {
		$raw_settings = is_array( $item ) ? $item : array();

		$response = ReactSettingsSchema::build_response(
			'products',
			'',
			$raw_settings,
			new WC_Settings_Products()
		);

		// Preserve the REST-specific title/description.
		$response['id']          = 'products';
		$response['title']       = __( 'Products', 'woocommerce' );
		$response['description'] = __( 'Manage product settings including dimensions, weight units, and display options.', 'woocommerce' );

		if ( ! empty( $include_fields ) ) {
			$response = array_intersect_key( $response, array_flip( $include_fields ) );
		}

		return $response;
	}

	/**
	 * Inject tab-specific field options for product settings fields.
	 *
	 * Callback registered against `woocommerce_react_settings_field_options`.
	 * Only overrides options when the existing array is empty, so authors can
	 * still supply an explicit options list via the settings definition.
	 *
	 * @since 10.8.0
	 *
	 * @param array  $options         Current options array.
	 * @param string $field_id        Setting field ID.
	 * @param array  $setting         Raw setting definition.
	 * @param string $normalized_type Normalized field type.
	 * @return array
	 */
	public static function inject_field_options( $options, string $field_id, array $setting, string $normalized_type ): array {
		unset( $setting, $normalized_type ); // Not needed for this callback.

		if ( ! is_array( $options ) ) {
			$options = array();
		}

		if ( ! empty( $options ) ) {
			return $options;
		}

		switch ( $field_id ) {
			case 'woocommerce_weight_unit':
				return array(
					'kg'  => __( 'kg', 'woocommerce' ),
					'g'   => __( 'g', 'woocommerce' ),
					'lbs' => __( 'lbs', 'woocommerce' ),
					'oz'  => __( 'oz', 'woocommerce' ),
				);

			case 'woocommerce_dimension_unit':
				return array(
					'm'  => __( 'm', 'woocommerce' ),
					'cm' => __( 'cm', 'woocommerce' ),
					'mm' => __( 'mm', 'woocommerce' ),
					'in' => __( 'in', 'woocommerce' ),
					'yd' => __( 'yd', 'woocommerce' ),
				);

			case 'woocommerce_product_type':
				if ( ! function_exists( 'wc_get_product_types' ) ) {
					return array();
				}
				$product_types = wc_get_product_types();
				return is_array( $product_types ) ? $product_types : array();

			case 'woocommerce_shop_page_id':
				return self::get_page_options();
		}

		return $options;
	}

	/**
	 * Get options for page selection fields.
	 *
	 * @since 10.8.0
	 *
	 * @return array
	 */
	private static function get_page_options(): array {
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
}
