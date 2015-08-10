<?php
/**
 * Order Item Meta
 *
 * A Simple class for managing order item meta so plugins add it in the correct format
 *
 * @class 		order_item_meta
 * @version		2.4
 * @package		WooCommerce/Classes
 * @author 		WooThemes
 */
class WC_Order_Item_Meta {

	/** @var bool For handling backwards comp */
	private $legacy = false;

	/** @var Array Order item */
	private $item   = null;

	/** @var Array Post meta data */
	public $meta    = null;

	/** @var Product object */
	public $product = null;

	/**
	 * Constructor
	 *
	 * @param array $item defaults to array()
	 * @param \WC_Product $product defaults to null
	 * @return \WC_Order_Item_Meta instance
	 */
	public function __construct( $item = array(), $product = null ) {
		// Backwards (pre 2.4) compat
		if ( ! isset( $item['item_meta'] ) ) {
			$this->legacy = true;
			$this->meta   = array_filter( (array) $item );
			return;
		}

		$this->item    = $item;
		$this->meta    = array_filter( (array) $item['item_meta'] );
		$this->product = $product;
	}

	/**
	 * Display meta in a formatted list
	 *
	 * @param bool $flat (default: false)
	 * @param bool $return (default: false)
	 * @param string $hideprefix (default: _)
	 * @param  string $delimiter Delimiter used to separate items when $flat is true
	 * @return string|void
	 */
	public function display( $flat = false, $return = false, $hideprefix = '_', $delimiter = ", \n" ) {
		$output         = '';
		$formatted_meta = $this->get_formatted( $hideprefix );

		if ( ! empty( $formatted_meta ) ) {
			$meta_list = array();

			foreach ( $formatted_meta as $meta ) {
				if ( $flat ) {
					$meta_list[] = wp_kses_post( $meta['label'] . ': ' . $meta['value'] );
				} else {
					$meta_list[] = '
						<dt class="variation-' . sanitize_html_class( sanitize_text_field( $meta['key'] ) ) . '">' . wp_kses_post( $meta['label'] ) . ':</dt>
						<dd class="variation-' . sanitize_html_class( sanitize_text_field( $meta['key'] ) ) . '">' . wp_kses_post( wpautop( make_clickable( $meta['value'] ) ) ) . '</dd>
					';
				}
			}

			if ( ! empty( $meta_list ) ) {
				if ( $flat ) {
					$output .= implode( $delimiter, $meta_list );
				} else {
					$output .= '<dl class="variation">' . implode( '', $meta_list ) . '</dl>';
				}
			}
		}

		$output = apply_filters( 'woocommerce_order_items_meta_display', $output, $this );

		if ( $return ) {
			return $output;
		} else {
			echo $output;
		}
	}

	/**
	 * Return an array of formatted item meta in format e.g.
	 *
	 * array(
	 *   'pa_size' => array(
	 *     'label' => 'Size',
	 *     'value' => 'Medium',
	 *   )
	 * )
	 *
	 * @since 2.4
	 * @param string $hideprefix exclude meta when key is prefixed with this, defaults to `_`
	 * @return array
	 */
	public function get_formatted( $hideprefix = '_' ) {
		if ( $this->legacy ) {
			return $this->get_formatted_legacy( $hideprefix );
		}

		$formatted_meta = array();

		foreach ( $this->item['item_meta_array'] as $meta_id => $meta ) {
			if ( empty( $meta->value ) || is_serialized( $meta->value ) || ( ! empty( $hideprefix ) && substr( $meta->key, 0, 1 ) == $hideprefix ) ) {
				continue;
			}

			$attribute_key = urldecode( str_replace( 'attribute_', '', $meta->key ) );

			// If this is a term slug, get the term's nice name
			if ( taxonomy_exists( $attribute_key ) ) {
				$term = get_term_by( 'slug', $meta->value, $attribute_key );
				if ( ! is_wp_error( $term ) && is_object( $term ) && $term->name ) {
					$meta->value = $term->name;
				}
			}

			$formatted_meta[ $meta_id ] = array(
				'key'   => $meta->key,
				'label' => wc_attribute_label( $attribute_key, $this->product ),
				'value' => apply_filters( 'woocommerce_order_item_display_meta_value', $meta->value ),
			);
		}

		return apply_filters( 'woocommerce_order_items_meta_get_formatted', $formatted_meta, $this );
	}

	/**
	 * Return an array of formatted item meta in format e.g.
	 * Handles @deprecated args
	 * @return array
	 */
	public function get_formatted_legacy( $hideprefix = '_' ) {
		_deprecated_function( 'get_formatted_legacy', '2.4', 'Item Meta Data is being called with legacy arguments' );

		$formatted_meta = array();

		foreach ( $this->meta as $meta_key => $meta_values ) {
			if ( empty( $meta_values ) || ( ! empty( $hideprefix ) && substr( $meta_key, 0, 1 ) == $hideprefix ) ) {
				continue;
			}
			foreach ( (array) $meta_values as $meta_value ) {
				// Skip serialised meta
				if ( is_serialized( $meta_value ) ) {
					continue;
				}

				$attribute_key = urldecode( str_replace( 'attribute_', '', $meta_key ) );

				// If this is a term slug, get the term's nice name
				if ( taxonomy_exists( $attribute_key ) ) {
					$term = get_term_by( 'slug', $meta_value, $attribute_key );
					if ( ! is_wp_error( $term ) && is_object( $term ) && $term->name ) {
						$meta_value = $term->name;
					}
				}

				// Unique key required
				$formatted_meta_key = $meta_key;
				$loop               = 0;
				while ( isset( $formatted_meta[ $formatted_meta_key ] ) ) {
					$loop ++;
					$formatted_meta_key = $meta_key . '-' . $loop;
				}

				$formatted_meta[ $formatted_meta_key ] = array(
					'key'   => $meta_key,
					'label' => wc_attribute_label( $attribute_key, $this->product ),
					'value' => apply_filters( 'woocommerce_order_item_display_meta_value', $meta_value ),
				);
			}
		}

		return $formatted_meta;
	}
}
