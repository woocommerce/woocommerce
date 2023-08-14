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
		if ( array_key_exists( 'user_agent', $return ) ) {
			$detector = new MobileDetect( array(), $return['user_agent'] );
			if ( $detector->isMobile() ) {
				$return['device_type'] = __( 'Mobile', 'woocommerce' );
			} elseif ( $detector->isTablet() ) {
				$return['device_type'] = __( 'Tablet', 'woocommerce' );
			} else {
				$return['device_type'] = __( 'Desktop', 'woocommerce' );
			}
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
	 * Returns the label based on the source type.
	 *
	 * @param string $source_type The source type.
	 *
	 * @return string The label for the source type.
	 */
	private function get_source_label( string $source_type ) {
		$label = '';

		switch ( $source_type ) {
			case 'utm':
				/* translators: %s is the source value */
				$label = esc_html__( 'Source: %s', 'woocommerce' );
				break;
			case 'organic':
				/* translators: %s is the source value */
				$label = esc_html__( 'Organic: %s', 'woocommerce' );
				break;
			case 'referral':
				/* translators: %s is the source value */
				$label = esc_html__( 'Referral: %s', 'woocommerce' );
				break;
		}

		return $label;
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
}
