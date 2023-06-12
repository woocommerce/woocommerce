<?php

namespace Automattic\WooCommerce\Internal\Admin\Orders\MetaBoxes;

use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;

/**
 * TaxonomiesMetaBox class, renders taxonomy sidebar widget on order edit screen.
 */
class TaxonomiesMetaBox {

	/**
	 * Order Table data store class.
	 *
	 * @var OrdersTableDataStore
	 */
	private $orders_table_data_store;

	/**
	 * Dependency injection init method.
	 *
	 * @param OrdersTableDataStore $orders_table_data_store Order Table data store class.
	 *
	 * @return void
	 */
	public function init( OrdersTableDataStore $orders_table_data_store ) {
		$this->orders_table_data_store = $orders_table_data_store;
	}

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

		$sanitized_tax_input = $this->orders_table_data_store->init_default_taxonomies( $order, $sanitized_tax_input );
		$this->orders_table_data_store->set_custom_taxonomies( $order, $sanitized_tax_input );
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
