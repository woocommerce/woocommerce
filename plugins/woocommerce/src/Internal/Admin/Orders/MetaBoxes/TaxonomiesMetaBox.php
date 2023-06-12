<?php

namespace Automattic\WooCommerce\Internal\Admin\Orders\MetaBoxes;

/**
 * TaxonomiesMetaBox class, renders taxonomy sidebar widget on order edit screen.
 */
class TaxonomiesMetaBox {

	/**
	 * Registers meta boxes to be rendered in order edit screen for taxonomies.
	 *
	 * Note: This is re-implementation of part of WP core's `register_and_do_post_meta_boxes` function. Since the code block that add meta box for taxonomies is not filterable, we have to re-implement it.
	 *
	 * @param string $screen_id Screen ID.
	 * @param string $order_type Order type to register meta boxes for.
	 *
	 * @return void
	 */
	public function add_taxonomies_meta_boxes( string $screen_id, string $order_type ) {
		include_once ABSPATH . 'wp-admin/includes/meta-boxes.php';
		$taxonomies = get_object_taxonomies( $order_type );
		// All taxonomies.
		foreach ( $taxonomies as $tax_name ) {
			$taxonomy = get_taxonomy( $tax_name );
			if ( ! $taxonomy->show_ui || false === $taxonomy->meta_box_cb ) {
				continue;
			}

			if ( 'post_categories_meta_box' === $taxonomy->meta_box_cb ) {
				$taxonomy->meta_box_cb = array( $this, 'order_categories_meta_box' );
			}

			if ( 'post_tags_meta_box' === $taxonomy->meta_box_cb ) {
				$taxonomy->meta_box_cb = array( $this, 'order_tags_meta_box' );
			}

			$label = $taxonomy->labels->name;

			if ( ! is_taxonomy_hierarchical( $tax_name ) ) {
				$tax_meta_box_id = 'tagsdiv-' . $tax_name;
			} else {
				$tax_meta_box_id = $tax_name . 'div';
			}

			add_meta_box(
				$tax_meta_box_id,
				$label,
				$taxonomy->meta_box_cb,
				$screen_id,
				'side',
				'core',
				array(
					'taxonomy'               => $tax_name,
					'__back_compat_meta_box' => true,
				)
			);
		}
	}

	/**
	 * Save handler for taxonomy data.
	 *
	 * @param \WC_Abstract_Order $order Order object.
	 * @param array|null         $taxonomy_input Taxonomy input passed from input.
	 */
	public function save_taxonomies( \WC_Abstract_Order $order, $taxonomy_input ) {
		if ( ! isset( $taxonomy_input ) ) {
			return;
		}

		$sanitized_tax_input = $this->sanitize_tax_input( $taxonomy_input );

		$this->set_default_taxonomies( $order, $sanitized_tax_input );
		$this->set_custom_taxonomies( $order, $sanitized_tax_input );
	}

	/**
	 * Sanitize taxonomy input by calling sanitize callbacks for each registered taxonomy.
	 *
	 * @param array|null $taxonomy_data Nonce verified taxonomy input.
	 *
	 * @return array Sanitized taxonomy input.
	 */
	private function sanitize_tax_input( $taxonomy_data ) : array {
		$sanitized_tax_input = array();
		if ( ! is_array( $taxonomy_data ) ) {
			return $sanitized_tax_input;
		}

		// Convert taxonomy input to term IDs, to avoid ambiguity.
		foreach ( $taxonomy_data as $taxonomy => $terms ) {
			$tax_object = get_taxonomy( $taxonomy );
			if ( $tax_object && isset( $tax_object->meta_box_sanitize_cb ) ) {
				$sanitized_tax_input[ $taxonomy ] = call_user_func_array( $tax_object->meta_box_sanitize_cb, array( $taxonomy, $terms ) );
			}
		}

		return $sanitized_tax_input;
	}

	/**
	 * Set default taxonomies for the order.
	 *
	 * Note: This is re-implementation of part of WP core's `wp_insert_post` function. Since the code block that set default taxonomies is not filterable, we have to re-implement it.
	 *
	 * @param \WC_Abstract_Order $order               Order object.
	 * @param array              $sanitized_tax_input Sanitized taxonomy input.
	 *
	 * @return void
	 */
	private function set_default_taxonomies( \WC_Abstract_Order $order, array $sanitized_tax_input ) {
		if ( 'auto-draft' === $order->get_status() ) {
			return;
		}

		foreach ( get_object_taxonomies( $order->get_type(), 'object' ) as $taxonomy => $tax_object ) {
			if ( empty( $tax_object->default_term ) ) {
				return;
			}

			// Filter out empty terms.
			if ( isset( $sanitized_tax_input[ $taxonomy ] ) && is_array( $sanitized_tax_input[ $taxonomy ] ) ) {
				$sanitized_tax_input[ $taxonomy ] = array_filter( $sanitized_tax_input[ $taxonomy ] );
			}

			// Passed custom taxonomy list overwrites the existing list if not empty.
			$terms = wp_get_object_terms( $order->get_id(), $taxonomy, array( 'fields' => 'ids' ) );
			if ( ! empty( $terms ) && empty( $sanitized_tax_input[ $taxonomy ] ) ) {
				$sanitized_tax_input[ $taxonomy ] = $terms;
			}

			if ( empty( $sanitized_tax_input[ $taxonomy ] ) ) {
				$default_term_id = get_option( 'default_term_' . $taxonomy );
				if ( ! empty( $default_term_id ) ) {
					$sanitized_tax_input[ $taxonomy ] = array( (int) $default_term_id );
				}
			}
		}
	}

	/**
	 * Set custom taxonomies for the order.
	 *
	 * Note: This is re-implementation of part of WP core's `wp_insert_post` function. Since the code block that set custom taxonomies is not filterable, we have to re-implement it.
	 *
	 * @param \WC_Abstract_Order $order               Order object.
	 * @param array              $sanitized_tax_input Sanitized taxonomy input.
	 *
	 * @return void
	 */
	private function set_custom_taxonomies( \WC_Abstract_Order $order, array $sanitized_tax_input ) {
		if ( empty( $sanitized_tax_input ) ) {
			return;
		}

		foreach ( $sanitized_tax_input as $taxonomy => $tags ) {
			$taxonomy_obj = get_taxonomy( $taxonomy );

			if ( ! $taxonomy_obj ) {
				/* translators: %s: Taxonomy name. */
				_doing_it_wrong( __FUNCTION__, esc_html( sprintf( __( 'Invalid taxonomy: %s.', 'woocommerce' ), $taxonomy ) ), '7.9.0' );
				continue;
			}

			// array = hierarchical, string = non-hierarchical.
			if ( is_array( $tags ) ) {
				$tags = array_filter( $tags );
			}

			if ( current_user_can( $taxonomy_obj->cap->assign_terms ) ) {
				wp_set_post_terms( $order->get_id(), $tags, $taxonomy );
			}
		}
	}

	/**
	 * Add the categories meta box to the order screen. This is just a wrapper around the post_categories_meta_box.
	 *
	 * @param \WC_Abstract_Order $order Order object.
	 * @param array              $box   Meta box args.
	 *
	 * @return void
	 */
	public function order_categories_meta_box( $order, $box ) {
		$post = get_post( $order->get_id() );
		post_categories_meta_box( $post, $box );
	}

	/**
	 * Add the tags meta box to the order screen. This is just a wrapper around the post_tags_meta_box.
	 *
	 * @param \WC_Abstract_Order $order Order object.
	 * @param array              $box   Meta box args.
	 *
	 * @return void
	 */
	public function order_tags_meta_box( $order, $box ) {
		$post = get_post( $order->get_id() );
		post_tags_meta_box( $post, $box );
	}
}
