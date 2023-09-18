<?php
/**
 * Order source attribution meta.
 *
 * @since x.x.x
 *
 * phpcs:disable Generic.Commenting.DocComment.MissingShort
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Traits;

use Automattic\WooCommerce\Vendor\Detection\MobileDetect;
use Exception;
use WC_Meta_Data;
use WC_Order;
use WP_Post;

/**
 * Trait SourceAttributionMeta
 *
 * @since x.x.x
 */
trait SourceAttributionMeta {

	/** @var string[] */
	private $default_fields = array(
		// main fields.
		'type',
		'url',

		// utm fields.
		'utm_campaign',
		'utm_source',
		'utm_medium',
		'utm_content',
		'utm_id',
		'utm_term',

		// additional fields.
		'session_entry',
		'session_start_time',
		'session_pages',
		'session_count',
		'user_agent',
	);

	/** @var array */
	private $fields = array();

	/** @var string */
	private $field_prefix = '';

	/**
	 * @since x.x.x
	 *
	 * @param array $values
	 *
	 * @return void
	 */
	protected function get_device_type( array $values ): string {
		$detector = new MobileDetect( array(), $values['user_agent'] );

		if ( $detector->isMobile() ) {
			return __( 'Mobile', 'woocommerce' );
		} elseif ( $detector->isTablet() ) {
			return __( 'Tablet', 'woocommerce' );
		} else {
			return __( 'Desktop', 'woocommerce' );
		}
	}

	/**
	 * Set the meta fields and the field prefix.
	 *
	 * @since x.x.x
	 * @return void
	 */
	private function set_fields_and_prefix() {
		/**
		 * Filter the fields to show in the source data metabox.
		 *
		 * @since x.x.x
		 *
		 * @param string[] $fields The fields to show.
		 */
		$this->fields = (array) apply_filters( 'wc_order_source_attribution_tracking_fields', $this->default_fields );
		$this->set_field_prefix();
	}

	/**
	 * Set the meta prefix for our fields.
	 *
	 * @since x.x.x
	 * @return void
	 */
	private function set_field_prefix(): void {
		/**
		 * Filter the prefix for the meta fields.
		 *
		 * @since x.x.x
		 *
		 * @param string $prefix The prefix for the meta fields.
		 */
		$prefix = (string) apply_filters(
			'wc_order_source_attribution_tracking_field_prefix',
			'wc_order_source_attribution_'
		);

		// Remove leading and trailing underscores.
		$prefix = trim( $prefix, '_' );

		// Ensure the prfix ends with _, and set the prefix.
		$this->field_prefix = "{$prefix}_";
	}

	/**
	 * Filter the meta data to only the keys that we care about.
	 *
	 * @param WC_Meta_Data[] $meta The meta data.
	 *
	 * @return array
	 */
	private function filter_meta_data( array $meta ): array {
		$return = array();
		$prefix = $this->get_meta_prefixed_field( '' );

		foreach ( $meta as $item ) {
			if ( str_starts_with( $item->key, $prefix ) ) {
				$return[ $this->unprefix_meta_field( $item->key ) ] = $item->value;
			}
		}

		// Determine the device type from the user agent.
		if ( ! array_key_exists( 'device_type', $return ) && array_key_exists( 'user_agent', $return ) ) {
			$return['device_type'] = $this->get_device_type( $return );
		}

		// Determine the origin based on source type and referrer.
		$source_type = $return['type'] ?? '';
		switch ( $source_type ) {
			case 'organic':
				$origin = __( 'Organic search', 'woocommerce' );
				break;

			case 'referral':
				$origin = __( 'Referral', 'woocommerce' );
				break;

			case 'typein':
				$origin = __( 'Direct', 'woocommerce' );
				break;

			default:
				$origin = __( 'Unknown', 'woocommerce' );
				break;
		}

		$return['origin'] = $origin;

		return $return;
	}

	/**
	 * Get the field name with the appropriate prefix.
	 *
	 * @param string $field Field name.
	 *
	 * @return string The prefixed field name.
	 */
	private function get_prefixed_field( $field ): string {
		return "{$this->field_prefix}{$field}";
	}

	/**
	 * Get the field name with the meta prefix.
	 *
	 * @since x.x.x
	 *
	 * @param string $field The field name.
	 *
	 * @return string The prefixed field name.
	 */
	private function get_meta_prefixed_field( string $field ): string {
		// Map some of the fields to the correct meta name.
		if ( 'type' === $field ) {
			$field = 'source_type';
		} elseif ( 'url' === $field ) {
			$field = 'referrer';
		}

		return "_{$this->get_prefixed_field( $field )}";
	}

	/**
	 * Remove the meta prefix from the field name.
	 *
	 * @since x.x.x
	 *
	 * @param string $field The prefixed field.
	 *
	 * @return string
	 */
	private function unprefix_meta_field( string $field ): string {
		$return = str_replace( "_{$this->field_prefix}", '', $field );

		// Map some of the fields to the correct meta name.
		if ( 'source_type' === $return ) {
			$return = 'type';
		} elseif ( 'referrer' === $return ) {
			$return = 'url';
		}

		return $return;
	}

	/**
	 * Get the order object with HPOS compatibility.
	 *
	 * @param WP_Post|int $post The post ID or object.
	 *
	 * @return WC_Order The order object
	 * @throws Exception When the order isn't found.
	 */
	private function get_hpos_order_object( $post ) {
		global $theorder;

		if ( is_numeric( $post ) ) {
			$post = get_post( $post );
		}

		if ( empty( $theorder ) || $theorder->get_id() !== $post->ID ) {
			$theorder = wc_get_order( $post->ID );
		}

		// Throw an exception if we don't have an order object.
		if ( ! $theorder instanceof WC_Order ) {
			throw new Exception( __( 'Order not found.', 'woocommerce' ) );
		}

		return $theorder;
	}

	/**
	 * Map posted values to meta values.
	 *
	 * @return array
	 */
	private function get_source_values(): array {
		$values = array();

		// Look through each field in POST data.
		foreach ( $this->fields as $field ) {
			// phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotValidated
			$value = sanitize_text_field( wp_unslash( $_POST[ $this->get_prefixed_field( $field ) ] ?? '(none)' ) );
			if ( '(none)' === $value ) {
				continue;
			}

			$values[ $field ] = $value;
		}

		// Set the device type if possible using the user agent.
		if ( array_key_exists( 'user_agent', $values ) && ! empty( $values['user_agent'] ) ) {
			$values['device_type'] = $this->get_device_type( $values );
		}

		// Set the origin label
		if ( array_key_exists( 'type', $values ) && array_key_exists( 'utm_source', $values ) ) {
			$values['origin'] = $this->get_origin_label( $values['type'], $values['utm_source'] );
		}

		return $values;
	}

	/**
	 * Get the label for the order origin
	 *
	 * @param string $source_type The source type.
	 * @param string $source      The source.
	 *
	 * @return string
	 */
	private function get_origin_label( string $source_type, string $source ): string {
		// Set up the label based on the source type.
		switch ( $source_type ) {
			case 'utm':
				/* translators: %s is the source value */
				$label = __( 'Source: %s', 'woocommerce' );
				break;
			case 'organic':
				/* translators: %s is the source value */
				$label = __( 'Organic: %s', 'woocommerce' );
				break;
			case 'referral':
				/* translators: %s is the source value */
				$label = __( 'Referral: %s', 'woocommerce' );
				break;

			default:
				$label = '';
				break;
		}

		/**
		 * Filter the formatted source for the order origin.
		 *
		 * @since x.x.x
		 *
		 * @param string $formatted_source The formatted source.
		 * @param string $source           The source.
		 */
		$formatted_source = apply_filters(
			'wc_order_source_attribution_origin_formatted_source',
			ucfirst( trim( $source, '()' ) ),
			$source
		);

		/**
		 * Filter the label for the order origin.
		 *
		 * This label should have a %s placeholder for the formatted source to be inserted
		 * via sprintf().
		 *
		 * @since x.x.x
		 *
		 * @param string $label            The label for the order origin.
		 * @param string $source_type      The source type.
		 * @param string $source           The source.
		 * @param string $formatted_source The formatted source.
		 */
		$label = (string) apply_filters(
			'wc_order_source_attribution_origin_label',
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
}
