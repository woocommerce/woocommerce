<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Traits;

use Automattic\WooCommerce\Vendor\Detection\MobileDetect;
use Exception;
use WC_Meta_Data;
use WC_Order;
use WP_Post;

/**
 * Trait OrderAttributionMeta
 *
 * @since 8.5.0
 *
 * phpcs:disable Generic.Commenting.DocComment.MissingShort
 */
trait OrderAttributionMeta {

	/**
	 * The default fields and their sourcebuster accesors,
	 * to show in the source data metabox.
	 *
	 * @var string[]
	 * */
	private $default_fields = array(
		// main fields.
		'source_type'          => 'current.typ',
		'referrer'             => 'current_add.rf',

		// utm fields.
		'utm_campaign'         => 'current.cmp',
		'utm_source'           => 'current.src',
		'utm_medium'           => 'current.mdm',
		'utm_content'          => 'current.cnt',
		'utm_id'               => 'current.id',
		'utm_term'             => 'current.trm',
		'utm_source_platform'  => 'current.plt',
		'utm_creative_format'  => 'current.fmt',
		'utm_marketing_tactic' => 'current.tct',

		// additional fields.
		'session_entry'        => 'current_add.ep',
		'session_start_time'   => 'current_add.fd',
		'session_pages'        => 'session.pgs',
		'session_count'        => 'udata.vst',
		'user_agent'           => 'udata.uag',
	);

	/** @var array */
	private $fields = array();

	/**
	 * Cached `array_keys( $fields )`.
	 *
	 * @var array
	 * */
	private $field_names = array();

	/** @var string */
	private $field_prefix = '';

	/**
	 * Get the device type based on the other meta fields.
	 *
	 * @param array $values The meta values.
	 *
	 * @return string The device type.
	 */
	protected function get_device_type( array $values ): string {
		$detector = new MobileDetect( array(), $values['user_agent'] );

		if ( $detector->isMobile() ) {
			return 'Mobile';
		} elseif ( $detector->isTablet() ) {
			return 'Tablet';
		} else {
			return 'Desktop';
		}
	}

	/**
	 * Set the fields and the field prefix.
	 *
	 * @return void
	 */
	private function set_fields_and_prefix() {
		/**
		 * Filter the fields to show in the source data metabox.
		 *
		 * @since 8.5.0
		 *
		 * @param string[] $fields The fields to show.
		 */
		$this->fields      = (array) apply_filters( 'wc_order_attribution_tracking_fields', $this->default_fields );
		$this->field_names = array_keys( $this->fields );
		$this->set_field_prefix();
	}

	/**
	 * Set the meta prefix for our fields.
	 *
	 * @return void
	 */
	private function set_field_prefix(): void {
		/**
		 * Filter the prefix for the meta fields.
		 *
		 * @since 8.5.0
		 *
		 * @param string $prefix The prefix for the meta fields.
		 */
		$prefix = (string) apply_filters(
			'wc_order_attribution_tracking_field_prefix',
			'wc_order_attribution_'
		);

		// Remove leading and trailing underscores.
		$prefix = trim( $prefix, '_' );

		// Ensure the prefix ends with _, and set the prefix.
		$this->field_prefix = "{$prefix}_";
	}

	/**
	 * Filter an order's meta data to only the keys that we care about.
	 *
	 * Sets the origin value based on the source type.
	 *
	 * @param WC_Meta_Data[] $meta The meta data.
	 *
	 * @return array
	 */
	private function filter_meta_data( array $meta ): array {
		$return = array();
		$prefix = $this->get_meta_prefixed_field_name( '' );

		foreach ( $meta as $item ) {
			if ( str_starts_with( $item->key, $prefix ) ) {
				$return[ $this->unprefix_meta_field_name( $item->key ) ] = $item->value;
			}
		}

		// Determine the device type from the user agent.
		if ( ! array_key_exists( 'device_type', $return ) && array_key_exists( 'user_agent', $return ) ) {
			$return['device_type'] = $this->get_device_type( $return );
		}

		// Determine the origin based on source type and referrer.
		$source_type      = $return['source_type'] ?? '';
		$source           = $return['utm_source'] ?? '';
		$return['origin'] = $this->get_origin_label( $source_type, $source, true );

		return $return;
	}

	/**
	 * Get the field name with the appropriate prefix.
	 *
	 * @param string $name Field name.
	 *
	 * @return string The prefixed field name.
	 */
	private function get_prefixed_field_name( $name ): string {
		return "{$this->field_prefix}{$name}";
	}

	/**
	 * Get the field name with the meta prefix.
	 *
	 * @param string $name The field name.
	 *
	 * @return string The prefixed field name.
	 */
	private function get_meta_prefixed_field_name( string $name ): string {
		return "_{$this->get_prefixed_field_name( $name )}";
	}

	/**
	 * Remove the meta prefix from the field name.
	 *
	 * @param string $name The prefixed fieldname .
	 *
	 * @return string
	 */
	private function unprefix_meta_field_name( string $name ): string {
		return str_replace( "_{$this->field_prefix}", '', $name );
	}

	/**
	 * Get the order object with HPOS compatibility.
	 *
	 * @param WC_Order|WP_Post|int $post_or_order The post ID or object.
	 *
	 * @return WC_Order The order object
	 * @throws Exception When the order isn't found.
	 */
	private function get_hpos_order_object( $post_or_order ) {
		// If we've already got an order object, just return it.
		if ( $post_or_order instanceof WC_Order ) {
			return $post_or_order;
		}

		// If we have a post ID, get the post object.
		if ( is_numeric( $post_or_order ) ) {
			$post_or_order = wc_get_order( $post_or_order );
		}

		// Throw an exception if we don't have an order object.
		if ( ! $post_or_order instanceof WC_Order ) {
			throw new Exception( __( 'Order not found.', 'woocommerce' ) );
		}

		return $post_or_order;
	}


	/**
	 * Map posted, prefixed values to field values.
	 * Used for the classic forms.
	 *
	 * @param array $raw_values The raw values from the POST form.
	 *
	 * @return array
	 */
	private function get_unprefixed_field_values( array $raw_values = array() ): array {
		$values = array();

		// Look through each field in POST data.
		foreach ( $this->field_names as $field_name ) {
			$values[ $field_name ] = $raw_values[ $this->get_prefixed_field_name( $field_name ) ] ?? '(none)';
		}

		return $values;
	}

	/**
	 * Map submitted values to meta values.
	 *
	 * @param array $raw_values The raw (unprefixed) values from the submitted data.
	 *
	 * @return array
	 */
	private function get_source_values( array $raw_values = array() ): array {
		$values = array();

		// Look through each field in given data.
		foreach ( $this->field_names as $field_name ) {
			$value = sanitize_text_field( wp_unslash( $raw_values[ $field_name ] ) );
			if ( '(none)' === $value ) {
				continue;
			}

			$values[ $field_name ] = $value;
		}

		// Set the device type if possible using the user agent.
		if ( array_key_exists( 'user_agent', $values ) && ! empty( $values['user_agent'] ) ) {
			$values['device_type'] = $this->get_device_type( $values );
		}

		return $values;
	}

	/**
	 * Get the label for the Order origin with placeholder where appropriate. Can be
	 * translated (for DB / display) or untranslated (for Tracks).
	 *
	 * @param string $source_type The source type.
	 * @param string $source      The source.
	 * @param bool   $translated  Whether the label should be translated.
	 *
	 * @return string
	 */
	private function get_origin_label( string $source_type, string $source, bool $translated = true ): string {
		// Set up the label based on the source type.
		switch ( $source_type ) {
			case 'utm':
				$label = $translated ?
					/* translators: %s is the source value */
					__( 'Source: %s', 'woocommerce' )
					: 'Source: %s';
				break;
			case 'organic':
				$label = $translated ?
					/* translators: %s is the source value */
					__( 'Organic: %s', 'woocommerce' )
					: 'Organic: %s';
				break;
			case 'referral':
				$label = $translated ?
					/* translators: %s is the source value */
					__( 'Referral: %s', 'woocommerce' )
					: 'Referral: %s';
				break;
			case 'typein':
				$label  = '';
				$source = $translated ?
					__( 'Direct', 'woocommerce' )
					: 'Direct';
				break;
			case 'mobile_app':
				$label  = '';
				$source = $translated ?
					__( 'Mobile app', 'woocommerce' )
					: 'Mobile app';
				break;
			case 'admin':
				$label  = '';
				$source = $translated ?
					__( 'Web admin', 'woocommerce' )
					: 'Web admin';
				break;

			default:
				$label  = '';
				$source = $translated ?
					__( 'Unknown', 'woocommerce' )
					: 'Unknown';
				break;
		}

		/**
		 * Filter the formatted source for the order origin.
		 *
		 * @since 8.5.0
		 *
		 * @param string $formatted_source The formatted source.
		 * @param string $source           The source.
		 */
		$formatted_source = apply_filters(
			'wc_order_attribution_origin_formatted_source',
			ucfirst( trim( $source, '()' ) ),
			$source
		);

		/**
		 * Filter the label for the order origin.
		 *
		 * This label should have a %s placeholder for the formatted source to be inserted
		 * via sprintf().
		 *
		 * @since 8.5.0
		 *
		 * @param string $label            The label for the order origin.
		 * @param string $source_type      The source type.
		 * @param string $source           The source.
		 * @param string $formatted_source The formatted source.
		 */
		$label = (string) apply_filters(
			'wc_order_attribution_origin_label',
			$label,
			$source_type,
			$source,
			$formatted_source
		);

		if ( false === strpos( $label, '%' ) ) {
			return $formatted_source;
		}

		return sprintf( $label, $formatted_source );
	}

	/**
	 * Get the description for the order attribution field.
	 *
	 * @param string $field_name The field name.
	 *
	 * @return string
	 */
	private function get_field_description( string $field_name ): string {
		/* translators: %s is the field name */
		$description = sprintf( __( 'Order attribution field: %s', 'woocommerce' ), $field_name );

		/**
		 * Filter the description for the order attribution field.
		 *
		 * @since 8.5.0
		 *
		 * @param string $description The description for the order attribution field.
		 * @param string $field_name  The field name.
		 */
		return (string) apply_filters( 'wc_order_attribution_field_description', $description, $field_name );
	}
}
