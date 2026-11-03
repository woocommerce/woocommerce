<?php
/**
 * Reads only approved settings, excluding password fields before loading values.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\AbilityDefinition;

defined( 'ABSPATH' ) || exit;

/**
 * Reads only approved settings, excluding password fields before loading values.
 */
class SettingsQuery extends AbstractDomainAbility implements AbilityDefinition {
	/**
	 * Get the ability name.
	 *
	 * @since 11.4.0
	 * @return string
	 */
	public static function get_name(): string {
		return 'woocommerce/settings-query';
	}

	/**
	 * Register the settings read.
	 *
	 * @since 11.4.0
	 * @return array
	 */
	public static function get_registration_args(): array {
		return array(
			'label'               => __( 'Query settings', 'woocommerce' ),
			'description'         => __( 'Read approved settings in a group. Use include for specific setting IDs. Unapproved fields and passwords are omitted.', 'woocommerce' ),
			'category'            => 'woocommerce',
			'input_schema'        => self::get_input_schema(),
			'output_schema'       => self::get_output_schema(),
			'execute_callback'    => array( __CLASS__, 'execute' ),
			'permission_callback' => array( StoreSettingsGet::class, 'can_read_settings' ),
			'meta'                => array(
				'show_in_rest' => true,
				'mcp'          => array(
					'public' => true,
					'type'   => 'tool',
				),
				'annotations'  => array(
					'readonly'    => true,
					'idempotent'  => true,
					'destructive' => false,
				),
			),
		);
	}

	/**
	 * Read approved settings in a registered group.
	 *
	 * @since 11.4.0
	 * @param array $input Collection filters.
	 * @return array|\WP_Error
	 */
	public static function execute( array $input ) {
		$group = $input['group'];
		if ( ! in_array( $group, array_column( SettingsGroupsQuery::get_groups(), 'id' ), true ) ) {
			return new \WP_Error( 'woocommerce_ability_settings_group_not_found', __( 'Settings group not found.', 'woocommerce' ), array( 'status' => 404 ) );
		}
		$allowed = self::get_allowed_options()[ $group ] ?? array();
		/**
		 * Filters registered fields for a settings group.
		 *
		 * @since 3.0.0
		 * @param array $settings Registered settings.
		 */
		$settings     = apply_filters( 'woocommerce_settings-' . $group, array() ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
		$readable     = array();
		$option_names = array();
		foreach ( is_array( $settings ) ? $settings : array() as $setting ) {
			if ( ! is_array( $setting ) || ! is_string( $setting['id'] ?? null ) || ! is_string( $setting['type'] ?? null ) ) {
				continue;
			}
			$id  = $setting['id'];
			$key = $setting['option_key'] ?? null;
			if ( ! is_array( $allowed ) || ! isset( $allowed[ $id ] ) || $key !== $allowed[ $id ] || in_array( $setting['type'], array( 'password', 'title', 'sectionend' ), true ) ) {
				continue;
			}
			if ( ! empty( $input['include'] ) && ! in_array( $id, $input['include'], true ) ) {
				continue;
			}
			if ( is_string( $key ) ) {
				$option_names[] = explode( '[', $key, 2 )[0];
			} elseif ( is_array( $key ) && 2 === count( $key ) && is_string( $key[0] ?? null ) && is_string( $key[1] ?? null ) ) {
				$option_names[] = $key[0];
			} else {
				continue;
			}
			$readable[] = $setting;
		}
		wp_prime_option_caches( array_unique( $option_names ) );

		$items = array();
		foreach ( $readable as $setting ) {
			$id      = $setting['id'];
			$key     = $setting['option_key'];
			$default = $setting['default'] ?? '';
			if ( is_string( $key ) ) {
				$value = \WC_Admin_Settings::get_option( $key, $default );
			} else {
				$option = get_option( $key[0], array() );
				$value  = is_array( $option ) ? ( $option[ $key[1] ] ?? $default ) : $default;
			}
			if ( true !== rest_validate_value_from_schema( $value, self::get_value_schema() ) ) {
				continue;
			}
			$item = array(
				'id'          => $id,
				'label'       => is_string( $setting['label'] ?? null ) ? $setting['label'] : '',
				'description' => is_string( $setting['description'] ?? null ) ? $setting['description'] : '',
				'type'        => $setting['type'],
				'value'       => $value,
			);
			if ( true === rest_validate_value_from_schema( $default, self::get_value_schema() ) ) {
				$item['default'] = $default;
			}
			if ( is_array( $setting['options'] ?? null ) ) {
				$item['options'] = (object) array_filter( $setting['options'], 'is_string' );
			}
			$items[] = $item;
		}
		$total    = count( $items );
		$page     = $input['page'] ?? 1;
		$per_page = $input['per_page'] ?? 20;
		return array(
			'settings'    => array_slice( $items, ( $page - 1 ) * $per_page, $per_page ),
			'total'       => $total,
			'total_pages' => (int) ceil( $total / $per_page ),
			'page'        => $page,
			'per_page'    => $per_page,
		);
	}

	/**
	 * Get reviewed option keys by group and field ID.
	 *
	 * @return array
	 */
	private static function get_allowed_options(): array {
		$core    = array(
			'general'  => array(
				'woocommerce_address_autocomplete_enabled',
				'woocommerce_address_autocomplete_provider',
				'woocommerce_store_address',
				'woocommerce_store_address_2',
				'woocommerce_store_city',
				'woocommerce_default_country',
				'woocommerce_store_postcode',
				'woocommerce_allowed_countries',
				'woocommerce_all_except_countries',
				'woocommerce_specific_allowed_countries',
				'woocommerce_ship_to_countries',
				'woocommerce_specific_ship_to_countries',
				'woocommerce_default_customer_address',
				'woocommerce_calc_taxes',
				'woocommerce_enable_coupons',
				'woocommerce_calc_discounts_sequentially',
				'woocommerce_currency',
				'woocommerce_currency_pos',
				'woocommerce_price_thousand_sep',
				'woocommerce_price_decimal_sep',
				'woocommerce_price_num_decimals',
			),
			'products' => array(
				'woocommerce_shop_page_id',
				'woocommerce_cart_redirect_after_add',
				'woocommerce_enable_ajax_add_to_cart',
				'woocommerce_placeholder_image',
				'woocommerce_weight_unit',
				'woocommerce_dimension_unit',
				'woocommerce_enable_reviews',
				'woocommerce_review_rating_verification_label',
				'woocommerce_review_rating_verification_required',
				'woocommerce_enable_review_rating',
				'woocommerce_review_rating_required',
				'woocommerce_manage_stock',
				'woocommerce_hold_stock_minutes',
				'woocommerce_notify_low_stock',
				'woocommerce_notify_no_stock',
				'woocommerce_notify_backorder',
				'woocommerce_notify_low_stock_amount',
				'woocommerce_notify_no_stock_amount',
				'woocommerce_hide_out_of_stock_items',
				'woocommerce_stock_format',
				'woocommerce_file_download_method',
				'woocommerce_downloads_redirect_fallback_allowed',
				'woocommerce_downloads_require_login',
				'woocommerce_downloads_grant_access_after_payment',
				'woocommerce_downloads_deliver_inline',
				'woocommerce_downloads_add_hash_to_filename',
				'woocommerce_downloads_count_partial',
			),
			'account'  => array(
				'woocommerce_enable_guest_checkout',
				'woocommerce_enable_checkout_login_reminder',
				'woocommerce_enable_delayed_account_creation',
				'woocommerce_enable_signup_and_login_from_checkout',
				'woocommerce_enable_myaccount_registration',
				'woocommerce_registration_generate_password',
				'woocommerce_registration_generate_username',
				'woocommerce_erasure_request_removes_order_data',
				'woocommerce_erasure_request_removes_download_data',
				'woocommerce_allow_bulk_remove_personal_data',
				'woocommerce_registration_privacy_policy_text',
				'woocommerce_checkout_privacy_policy_text',
				'woocommerce_delete_inactive_accounts',
				'woocommerce_trash_pending_orders',
				'woocommerce_trash_failed_orders',
				'woocommerce_trash_cancelled_orders',
				'woocommerce_anonymize_refunded_orders',
				'woocommerce_anonymize_completed_orders',
			),
			'shipping' => array(
				'woocommerce_enable_shipping_calc',
				'woocommerce_shipping_cost_requires_address',
				'woocommerce_shipping_hide_rates_when_free',
				'woocommerce_ship_to_destination',
				'woocommerce_shipping_debug_mode',
			),
			'advanced' => array(
				'woocommerce_cart_page_id',
				'woocommerce_checkout_page_id',
				'woocommerce_myaccount_page_id',
				'woocommerce_terms_page_id',
				'woocommerce_refund_returns_page_id',
				'woocommerce_force_ssl_checkout',
				'woocommerce_unforce_ssl_checkout',
				'woocommerce_checkout_pay_endpoint',
				'woocommerce_checkout_order_received_endpoint',
				'woocommerce_myaccount_add_payment_method_endpoint',
				'woocommerce_myaccount_delete_payment_method_endpoint',
				'woocommerce_myaccount_set_default_payment_method_endpoint',
				'woocommerce_myaccount_orders_endpoint',
				'woocommerce_myaccount_view_order_endpoint',
				'woocommerce_myaccount_downloads_endpoint',
				'woocommerce_myaccount_edit_account_endpoint',
				'woocommerce_myaccount_edit_address_endpoint',
				'woocommerce_myaccount_payment_methods_endpoint',
				'woocommerce_myaccount_lost_password_endpoint',
				'woocommerce_logout_endpoint',
				'woocommerce_allow_tracking',
				'woocommerce_show_marketplace_suggestions',
				'woocommerce_rest_api_enable_backend_caching',
				'woocommerce_rest_api_enable_cache_headers',
			),
			'email'    => array(
				'woocommerce_email_from_name',
				'woocommerce_email_reply_to_enabled',
				'woocommerce_email_reply_to_name',
				'woocommerce_email_header_image',
				'woocommerce_email_header_image_width',
				'woocommerce_email_header_alignment',
				'woocommerce_email_font_family',
				'woocommerce_email_footer_text',
				'woocommerce_email_base_color',
				'woocommerce_email_background_color',
				'woocommerce_email_body_background_color',
				'woocommerce_email_text_color',
				'woocommerce_email_footer_text_color',
				'woocommerce_email_auto_sync_with_theme',
			),
			'tax'      => array(
				'woocommerce_prices_include_tax',
				'woocommerce_tax_based_on',
				'woocommerce_shipping_tax_class',
				'woocommerce_tax_round_at_subtotal',
				'woocommerce_tax_classes',
				'woocommerce_tax_display_shop',
				'woocommerce_tax_display_cart',
				'woocommerce_price_display_suffix',
				'woocommerce_tax_total_display',
			),
		);
		$options = array();
		foreach ( $core as $group => $ids ) {
			$options[ $group ] = array_combine( $ids, $ids );
		}
		foreach ( array( 'customer_order_withdrawal_requested', 'customer_fulfillment_updated', 'customer_new_account', 'customer_review_request', 'admin_payment_gateway_enabled', 'customer_processing_order', 'new_order', 'customer_note', 'customer_on_hold_order', 'customer_fulfillment_created', 'customer_partially_refunded_order', 'customer_pos_completed_order', 'customer_reset_password', 'customer_pos_refunded_order', 'customer_completed_order', 'failed_order', 'customer_failed_order', 'customer_invoice', 'customer_cancelled_order', 'cancelled_order', 'customer_refunded_order', 'customer_fulfillment_deleted', 'order_withdrawal_requested' ) as $email ) {
			foreach ( array( 'enabled', 'subject', 'heading', 'additional_content', 'email_type' ) as $id ) {
				$options[ 'email_' . $email ][ $id ] = array( 'woocommerce_' . $email . '_settings', $id );
			}
		}
		/**
		 * Filters reviewed, non-secret setting IDs and their exact storage keys, indexed by group.
		 *
		 * Only opt in fields whose values are safe for agents. Password fields remain excluded.
		 *
		 * @since 11.4.0
		 * @param array $options Group IDs mapped to setting IDs and option keys (string or two-part array).
		 */
		$options = apply_filters( 'woocommerce_ability_settings_allowed_options', $options );
		return is_array( $options ) ? $options : array();
	}

	/**
	 * Get collection filters.
	 *
	 * @return array
	 */
	private static function get_input_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'group'    => array(
					'type'      => 'string',
					'minLength' => 1,
				),
				'include'  => array(
					'type'     => 'array',
					'items'    => array(
						'type'      => 'string',
						'minLength' => 1,
					),
					'maxItems' => 100,
				),
				'page'     => array(
					'type'    => 'integer',
					'minimum' => 1,
					'default' => 1,
				),
				'per_page' => array(
					'type'    => 'integer',
					'minimum' => 1,
					'maximum' => 100,
					'default' => 20,
				),
			),
			'required'             => array( 'group' ),
			'additionalProperties' => false,
		);
	}

	/**
	 * Permit scalar values, scalar lists and the closed retention-period shape.
	 *
	 * @return array
	 */
	private static function get_value_schema(): array {
		return array(
			'type'                 => array( 'string', 'number', 'boolean', 'null', 'array', 'object' ),
			'items'                => array( 'type' => array( 'string', 'number', 'boolean', 'null' ) ),
			'properties'           => array(
				'number' => array( 'type' => array( 'string', 'number' ) ),
				'unit'   => array( 'type' => 'string' ),
			),
			'additionalProperties' => false,
		);
	}

	/**
	 * Get the settings collection schema.
	 *
	 * @return array
	 */
	private static function get_output_schema(): array {
		$schema                        = self::get_collection_output_schema(
			'settings',
			array(
				'type'                 => 'object',
				'properties'           => array(
					'id'          => array( 'type' => 'string' ),
					'label'       => array( 'type' => 'string' ),
					'type'        => array( 'type' => 'string' ),
					'value'       => self::get_value_schema(),
					'default'     => self::get_value_schema(),
					'description' => array( 'type' => 'string' ),
					'options'     => array(
						'type'                 => 'object',
						'additionalProperties' => array( 'type' => 'string' ),
					),
				),
				'additionalProperties' => false,
			)
		);
		$schema['properties']['total'] = array( 'type' => 'integer' );
		return $schema;
	}
}
