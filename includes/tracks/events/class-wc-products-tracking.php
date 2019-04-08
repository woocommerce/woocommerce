<?php
/**
 * WooCommerce Import Tracking
 *
 * @package WooCommerce\Tracks
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class adds actions to track usage of WooCommerce Products.
 */
class WC_Products_Tracking {
	/**
	 * Init tracking.
	 */
	public function init() {
		add_action( 'edit_post', array( $this, 'track_product_updated' ), 10, 2 );
		add_action( 'transition_post_status', array( $this, 'track_product_published' ), 10, 3 );
		add_action( 'created_product_cat', array( $this, 'track_product_category_created' ) );
		add_action( 'load-post-new.php', array( $this, 'track_product_add_start' ), 10 );
		add_action( 'load-post.php', array( $this, 'track_product_edit' ), 10 );
		add_action( 'woocommerce_attribute_added', array( $this, 'track_add_attribute_product_management' ), 10 );
	}

	/**
	 * Send a Tracks event when an attribute is added from the product management section.
	 */
	public function track_add_attribute_product_management() {
		WC_Tracks::record_event(
			'product_attribute_add',
			array(
				'source' => 'product-management',
			)
		);
	}

	/**
	 * Enqueue js to send event when a new attribute is added.
	 */
	public function track_attributes_client() {
		wc_enqueue_js(
			"
				$( 'button.add_attribute' ).on( 'click', function() {
					window.wcTracks.recordEvent( 'product_attribute_add', { source: 'product-edit' } );
				} );
				$( 'button.save_attributes' ).on( 'click', function() {
					window.wcTracks.recordEvent( 'product_attribute_edit', { source: 'product-edit' } );
				} );
			"
		);
	}

	/**
	 * Add functions when on the product edit screen.
	 */
	public function track_product_edit() {
		if ( ! empty( $_GET['post'] ) && 'product' === get_post_type( $_GET['post'] ) ) {
			$this->track_attributes_client();
		}
	}

	/**
	 * Send a tracks event when a new product is started.
	 */
	public function track_product_add_start() {
		if ( isset( $_GET['post_type'] ) && 'product' === wc_clean( wp_unslash( $_GET['post_type'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.NoNonceVerification, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			WC_Tracks::record_event( 'product_add_start' );
			$this->track_attributes_client();
		}
	}

	/**
	 * Send a Tracks event when a product is updated.
	 *
	 * @param int    $product_id Product id.
	 * @param object $post WordPress post.
	 */
	public function track_product_updated( $product_id, $post ) {
		if ( 'product' !== $post->post_type ) {
			return;
		}

		WC_Tracks::record_event( 'product_edit' );
	}

	/**
	 * Send a Tracks event when a product is published.
	 *
	 * @param string $new_status New post_status.
	 * @param string $old_status Previous post_status.
	 * @param object $post WordPress post.
	 */
	public function track_product_published( $new_status, $old_status, $post ) {
		if (
			'product' !== $post->post_type ||
			'publish' !== $new_status ||
			'publish' === $old_status
		) {
			return;
		}

		$properties = array(
			'product_id' => $post->ID,
		);

		WC_Tracks::record_event( 'product_add_publish', $properties );
	}

	/**
	 * Send a Tracks event when a product category is created.
	 *
	 * @param int $category_id Category ID.
	 */
	public function track_product_category_created( $category_id ) {
		// phpcs:disable WordPress.Security.NonceVerification.NoNonceVerification
		// Only track category creation from the edit product screen or the
		// category management screen (which both occur via AJAX).
		if (
			! defined( 'DOING_AJAX' ) ||
			empty( $_POST['action'] ) ||
			(
				// Product Categories screen.
				'add-tag' !== $_POST['action'] &&
				// Edit Product screen.
				'add-product_cat' !== $_POST['action']
			)
		) {
			return;
		}

		$category   = get_term( $category_id, 'product_cat' );
		$properties = array(
			'category_id' => $category_id,
			'parent_id'   => $category->parent,
			'page'        => ( 'add-tag' === $_POST['action'] ) ? 'categories' : 'product',
		);
		// phpcs:enable

		WC_Tracks::record_event( 'product_category_add', $properties );
	}
}
