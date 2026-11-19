<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\RegulatoryFields;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use WC_Order;

/**
 * Includes regulatory fields in personal data exports and removes them when an order's personal data is erased.
 *
 * @internal Just for internal use.
 *
 * @since 11.4.0
 */
class Privacy implements RegisterHooksInterface {

	/**
	 * Prefix of the names the fields are exported under.
	 */
	private const EXPORT_PROP_PREFIX = 'regulatory_';

	/**
	 * The regulatory fields service.
	 *
	 * @var RegulatoryFields
	 */
	private RegulatoryFields $regulatory_fields;

	/**
	 * Initialize the class with its dependencies.
	 *
	 * @internal
	 *
	 * @param RegulatoryFields $regulatory_fields The regulatory fields service.
	 */
	final public function init( RegulatoryFields $regulatory_fields ): void {
		$this->regulatory_fields = $regulatory_fields;
	}

	/**
	 * Register hooks and filters.
	 */
	public function register(): void {
		add_filter( 'woocommerce_privacy_export_order_personal_data_props', array( $this, 'handle_woocommerce_privacy_export_order_personal_data_props' ), 10, 2 );
		add_filter( 'woocommerce_privacy_export_order_personal_data_prop', array( $this, 'handle_woocommerce_privacy_export_order_personal_data_prop' ), 10, 3 );
		add_filter( 'woocommerce_privacy_remove_order_personal_data_meta', array( $this, 'handle_woocommerce_privacy_remove_order_personal_data_meta' ) );
		add_filter( 'woocommerce_privacy_remove_order_personal_data_meta_value', array( $this, 'handle_woocommerce_privacy_remove_order_personal_data_meta_value' ), 10, 2 );
	}

	/**
	 * Add the fields an order has a value for to the list of exported order data.
	 *
	 * @internal
	 *
	 * @param mixed $props Labels of the exported data, keyed by name.
	 * @param mixed $order Order being exported.
	 * @return mixed
	 */
	public function handle_woocommerce_privacy_export_order_personal_data_props( $props, $order = null ) {
		if ( ! is_array( $props ) || ! $order instanceof WC_Order ) {
			return $props;
		}

		foreach ( $this->regulatory_fields->get_order_display_values( $order ) as $field_id => $field ) {
			$props[ self::EXPORT_PROP_PREFIX . $field_id ] = $field['label'];
		}

		return $props;
	}

	/**
	 * Provide the exported value of a field.
	 *
	 * @internal
	 *
	 * @param mixed $value Value found so far.
	 * @param mixed $prop  Name of the exported data.
	 * @param mixed $order Order being exported.
	 * @return mixed
	 */
	public function handle_woocommerce_privacy_export_order_personal_data_prop( $value, $prop = '', $order = null ) {
		if ( ! is_string( $prop ) || 0 !== strpos( $prop, self::EXPORT_PROP_PREFIX ) || ! $order instanceof WC_Order ) {
			return $value;
		}

		$field_id = substr( $prop, strlen( self::EXPORT_PROP_PREFIX ) );

		return $this->regulatory_fields->get_order_display_values( $order )[ $field_id ]['value'] ?? $value;
	}

	/**
	 * Add the fields' meta keys to the order meta that is erased.
	 *
	 * @internal
	 *
	 * @param mixed $meta Data types of the erased meta, keyed by meta key.
	 * @return mixed
	 */
	public function handle_woocommerce_privacy_remove_order_personal_data_meta( $meta ) {
		if ( ! is_array( $meta ) ) {
			return $meta;
		}

		foreach ( array_keys( $this->regulatory_fields->get_all_fields() ) as $field_id ) {
			$meta[ RegulatoryFields::META_PREFIX . $field_id ] = 'text';
		}

		return $meta;
	}

	/**
	 * Erase a field by deleting its meta, where the default is to replace the value with a placeholder.
	 *
	 * @internal
	 *
	 * @param mixed $anonymized_value Value that would replace the stored one.
	 * @param mixed $meta_key         Meta key being erased.
	 * @return mixed An empty string for regulatory fields, which deletes the meta.
	 */
	public function handle_woocommerce_privacy_remove_order_personal_data_meta_value( $anonymized_value, $meta_key = '' ) {
		return is_string( $meta_key ) && 0 === strpos( $meta_key, RegulatoryFields::META_PREFIX ) ? '' : $anonymized_value;
	}
}
