<?php
/**
 * GeneralSettingsSchema class.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4\Settings\General\Schema;

use Automattic\WooCommerce\Internal\Admin\Settings\ReactSettingsSchema;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\AbstractSchema;
use WC_Settings_General;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

/**
 * GeneralSettingsSchema class.
 */
class GeneralSettingsSchema extends AbstractSchema {
	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'general_settings';

	/**
	 * Whether the tab-specific field options filter callback has been registered.
	 *
	 * @var bool
	 */
	private static $field_options_filter_registered = false;

	/**
	 * Constructor.
	 *
	 * Registers the tab-specific field options callback on the shared
	 * `woocommerce_react_settings_field_options` filter exposed by
	 * ReactSettingsSchema. The callback only injects options for field IDs
	 * owned by the general settings tab, so it is safe to register globally.
	 */
	public function __construct() {
		if ( ! self::$field_options_filter_registered ) {
			add_filter(
				'woocommerce_react_settings_field_options',
				array( self::class, 'inject_field_options' ),
				10,
				4
			);
			self::$field_options_filter_registered = true;
		}
	}

	/**
	 * Return all properties for the item schema.
	 *
	 * @return array
	 */
	public function get_item_schema_properties(): array {
		return array(
			'id'          => array(
				'description' => __( 'Unique identifier for the settings group.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_CONTEXT,
				'readonly'    => true,
			),
			'title'       => array(
				'description' => __( 'Settings title.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_CONTEXT,
				'readonly'    => true,
			),
			'description' => array(
				'description' => __( 'Settings description.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_CONTEXT,
				'readonly'    => true,
			),
			'values'      => array(
				'description'          => __( 'Flat key-value mapping of all setting field values.', 'woocommerce' ),
				'type'                 => 'object',
				'context'              => self::VIEW_EDIT_CONTEXT,
				'additionalProperties' => array(
					'description' => __( 'Setting field value.', 'woocommerce' ),
					'type'        => array( 'string', 'number', 'array', 'boolean' ),
				),
			),
			'groups'      => array(
				'description'          => __( 'Collection of setting groups.', 'woocommerce' ),
				'type'                 => 'object',
				'context'              => self::VIEW_EDIT_CONTEXT,
				'additionalProperties' => array(
					'type'        => 'object',
					'description' => __( 'Settings group.', 'woocommerce' ),
					'properties'  => array(
						'title'       => array(
							'description' => __( 'Group title.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => self::VIEW_EDIT_CONTEXT,
						),
						'description' => array(
							'description' => __( 'Group description.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => self::VIEW_EDIT_CONTEXT,
						),
						'order'       => array(
							'description' => __( 'Display order for the group.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => self::VIEW_EDIT_CONTEXT,
							'readonly'    => true,
						),
						'fields'      => array(
							'description' => __( 'Settings fields.', 'woocommerce' ),
							'type'        => 'array',
							'context'     => self::VIEW_EDIT_CONTEXT,
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
					'context'     => self::VIEW_EDIT_CONTEXT,
				),
				'label'   => array(
					'description' => __( 'Setting field label.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => self::VIEW_EDIT_CONTEXT,
				),
				'type'    => array(
					'description' => __( 'Setting field type.', 'woocommerce' ),
					'type'        => 'string',
					'enum'        => array( 'text', 'number', 'select', 'multiselect', 'checkbox' ),
					'context'     => self::VIEW_EDIT_CONTEXT,
				),
				'options' => array(
					'description' => __( 'Available options for select/multiselect fields.', 'woocommerce' ),
					'type'        => 'object',
					'context'     => self::VIEW_EDIT_CONTEXT,
				),
				'desc'    => array(
					'description' => __( 'Description for the setting field.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => self::VIEW_EDIT_CONTEXT,
				),
			),
		);
	}

	/**
	 * Get general settings data by transforming raw settings into REST API format.
	 *
	 * Delegates the actual transform to ReactSettingsSchema::build_response() so
	 * there's one canonical transformer shared with the modernised admin UI
	 * preloader.
	 *
	 * @param mixed           $item             Raw settings array.
	 * @param WP_REST_Request $request          Request object.
	 * @param array           $include_fields   Fields to include.
	 * @return array
	 */
	public function get_item_response( $item, WP_REST_Request $request, array $include_fields = array() ): array {
		$raw_settings = is_array( $item ) ? $item : array();

		$response = ReactSettingsSchema::build_response(
			'general',
			'',
			$raw_settings,
			new WC_Settings_General()
		);

		// Preserve the REST-specific title/description. ReactSettingsSchema
		// derives title from the page label, which is fine for the admin
		// preloader but the REST endpoint exposes richer copy.
		$response['id']          = 'general';
		$response['title']       = __( 'General', 'woocommerce' );
		$response['description'] = __( 'Set your store\'s address, visibility, currency, language, and timezone.', 'woocommerce' );

		if ( ! empty( $include_fields ) ) {
			$response = array_intersect_key( $response, array_flip( $include_fields ) );
		}

		return $response;
	}

	/**
	 * Inject tab-specific field options for general settings fields.
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
			case 'woocommerce_currency':
				if ( ! function_exists( 'get_woocommerce_currencies' ) || ! function_exists( 'get_woocommerce_currency_symbol' ) ) {
					return array();
				}

				$currencies = get_woocommerce_currencies();
				$generated  = array();

				foreach ( $currencies as $code => $name ) {
					$label               = wp_specialchars_decode( (string) $name );
					$symbol              = wp_specialchars_decode( (string) get_woocommerce_currency_symbol( $code ) );
					$generated[ $code ] = $label . ' (' . $symbol . ') — ' . $code;
				}

				return $generated;

			case 'woocommerce_default_country':
				if ( ! function_exists( 'WC' ) ) {
					return array();
				}

				$countries = WC()->countries->get_countries();
				$states    = WC()->countries->get_states();
				$generated = array();

				foreach ( $countries as $country_code => $country_name ) {
					$country_states = $states[ $country_code ] ?? array();

					if ( empty( $country_states ) ) {
						$generated[ $country_code ] = $country_name;
						continue;
					}

					foreach ( $country_states as $state_code => $state_name ) {
						$generated[ $country_code . ':' . $state_code ] = $country_name . ' — ' . $state_name;
					}
				}

				return $generated;

			case 'woocommerce_all_except_countries':
			case 'woocommerce_specific_allowed_countries':
			case 'woocommerce_specific_ship_to_countries':
				if ( ! function_exists( 'WC' ) ) {
					return array();
				}
				return WC()->countries->get_countries();
		}

		return $options;
	}
}
