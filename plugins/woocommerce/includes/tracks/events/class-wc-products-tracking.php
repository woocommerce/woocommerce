<?php
/**
 * WooCommerce Import Tracking
 *
 * @package WooCommerce\Tracks
 */

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Internal\Admin\WCAdminAssets;

defined( 'ABSPATH' ) || exit;

/**
 * This class adds actions to track usage of WooCommerce Products.
 */
class WC_Products_Tracking {
	/**
	 * Init tracking.
	 */
	public function init() {
		add_action( 'load-edit.php', array( $this, 'track_products_view' ), 10 );
		add_action( 'load-edit-tags.php', array( $this, 'track_categories_and_tags_view' ), 10, 2 );
		add_action( 'edit_post', array( $this, 'track_product_updated' ), 10, 2 );
		add_action( 'wp_after_insert_post', array( $this, 'track_product_published' ), 10, 4 );
		add_action( 'created_product_cat', array( $this, 'track_product_category_created' ) );
		add_action( 'edited_product_cat', array( $this, 'track_product_category_updated' ) );
		add_action( 'add_meta_boxes_product', array( $this, 'track_product_updated_client_side' ), 10 );
		add_action( 'admin_enqueue_scripts', array( $this, 'possibly_add_product_tracking_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'possibly_add_attribute_tracking_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'possibly_add_tag_tracking_scripts' ) );
	}

	/**
	 * Send a Tracks event when the Products page is viewed.
	 */
	public function track_products_view() {
		// We only record Tracks event when no `_wp_http_referer` query arg is set, since
		// when searching, the request gets sent from the browser twice,
		// once with the `_wp_http_referer` and once without it.
		//
		// Otherwise, we would double-record the view and search events.

		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification
		if (
			isset( $_GET['post_type'] )
			&& 'product' === wp_unslash( $_GET['post_type'] )
			&& ! isset( $_GET['_wp_http_referer'] )
		) {
			// phpcs:enable

			WC_Tracks::record_event( 'products_view' );

			// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification
			if (
				isset( $_GET['s'] )
				&& 0 < strlen( sanitize_text_field( wp_unslash( $_GET['s'] ) ) )
			) {
				// phpcs:enable

				WC_Tracks::record_event( 'products_search' );
			}
		}
	}

	/**
	 * Send a Tracks event when the Products Categories and Tags page is viewed.
	 */
	public function track_categories_and_tags_view() {
		// We only record Tracks event when no `_wp_http_referer` query arg is set, since
		// when searching, the request gets sent from the browser twice,
		// once with the `_wp_http_referer` and once without it.
		//
		// Otherwise, we would double-record the view and search events.

		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification
		if (
			isset( $_GET['post_type'] )
			&& 'product' === wp_unslash( $_GET['post_type'] )
			&& isset( $_GET['taxonomy'] )
			&& ! isset( $_GET['_wp_http_referer'] )
		) {
			$taxonomy = wp_unslash( $_GET['taxonomy'] );
			// phpcs:enable

			if ( 'product_cat' === $taxonomy ) {
				WC_Tracks::record_event( 'categories_view' );
			} elseif ( 'product_tag' === $taxonomy ) {
				WC_Tracks::record_event( 'tags_view' );
			}

			// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification
			if (
				isset( $_GET['s'] )
				&& 0 < strlen( sanitize_text_field( wp_unslash( $_GET['s'] ) ) )
			) {
				// phpcs:enable

				if ( 'product_cat' === $taxonomy ) {
					WC_Tracks::record_event( 'categories_search' );
				} elseif ( 'product_tag' === $taxonomy ) {
					WC_Tracks::record_event( 'tags_search' );
				}
			}
		}
	}

	/**
	 * Send a Tracks event when a product is updated.
	 *
	 * @param int    $product_id Product id.
	 * @param object $post       WordPress post.
	 */
	public function track_product_updated( $product_id, $post ) {
		if ( 'product' !== $post->post_type ) {
			return;
		}

		$properties = array(
			'product_id' => $product_id,
		);

		WC_Tracks::record_event( 'product_edit', $properties );
	}

	/**
	 * Track the Update button being clicked on the client side.
	 * This is needed because `track_product_updated` (using the `edit_post`
	 * hook) is called in response to a number of other triggers.
	 *
	 * @param WP_Post $post The post, not used.
	 */
	public function track_product_updated_client_side( $post ) {
		wc_enqueue_js(
			"
			if ( $( 'h1.wp-heading-inline' ).text().trim() === '" . __( 'Edit product', 'woocommerce' ) . "') {
				var initialStockValue = $( '#_stock' ).val();
				var hasRecordedEvent = false;

				$( '#publish' ).on( 'click', function() {
					if ( hasRecordedEvent ) {
						return;
					}

					var currentStockValue = $( '#_stock' ).val();
					var properties = {
						product_type:			$( '#product-type' ).val(),
						is_virtual:				$( '#_virtual' ).is( ':checked' ) ? 'Y' : 'N',
						is_downloadable:		$( '#_downloadable' ).is( ':checked' ) ? 'Y' : 'N',
						manage_stock:			$( '#_manage_stock' ).is( ':checked' ) ? 'Y' : 'N',
						stock_quantity_update:	( initialStockValue != currentStockValue ) ? 'Y' : 'N',
					};

					window.wcTracks.recordEvent( 'product_update', properties );
					hasRecordedEvent = true;
				} );
			}
			"
		);
	}

	/**
	 * Send a Tracks event when a product is published.
	 *
	 * @param int          $post_id     Post ID.
	 * @param WP_Post      $post        Post object.
	 * @param bool         $update      Whether this is an existing post being updated.
	 * @param null|WP_Post $post_before Null for new posts, the WP_Post object prior
	 *                                  to the update for updated posts.
	 */
	public function track_product_published( $post_id, $post, $update, $post_before ) {
		if (
			'product' !== $post->post_type ||
			'publish' !== $post->post_status ||
			( $post_before && 'publish' === $post_before->post_status )
		) {
			return;
		}

		$product = wc_get_product( $post_id );

		$properties = array(
			'product_id'      => $post_id,
			'product_type'    => $product->get_type(),
			'is_downloadable' => $product->is_downloadable() ? 'yes' : 'no',
			'is_virtual'      => $product->is_virtual() ? 'yes' : 'no',
			'manage_stock'    => $product->get_manage_stock() ? 'yes' : 'no',
		);

		WC_Tracks::record_event( 'product_add_publish', $properties );
	}

	/**
	 * Send a Tracks event when a product category is created.
	 *
	 * @param int $category_id Category ID.
	 */
	public function track_product_category_created( $category_id ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		// Only track category creation from the edit product screen or the
		// category management screen (which both occur via AJAX).
		if (
			! Constants::is_defined( 'DOING_AJAX' ) ||
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

		$category        = get_term( $category_id, 'product_cat' );
		$parent_category = $category->parent > 0 ? 'Other' : 'None';
		if ( $category->parent > 0 ) {
			$parent = get_term( $category_id, 'product_cat' );
			if ( 'uncategorized' === $parent->name ) {
				$parent_category = 'Uncategorized';
			}
		}
		$properties = array(
			'category_id'     => $category_id,
			'parent_id'       => $category->parent,
			'parent_category' => $parent_category,
			'page'            => ( 'add-tag' === $_POST['action'] ) ? 'categories' : 'product',
			'display_type'    => isset( $_POST['display_type'] ) ? wp_unslash( $_POST['display_type'] ) : '',
			'image'           => isset( $_POST['product_cat_thumbnail_id'] ) && '' !== $_POST['product_cat_thumbnail_id'] ? 'Yes' : 'No',
		);
		// phpcs:enable

		WC_Tracks::record_event( 'product_category_add', $properties );
	}

	/**
	 * Send a Tracks event when a product category is updated.
	 *
	 * @param int $category_id Category ID.
	 */
	public function track_product_category_updated( $category_id ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		// Only track category creation from the edit product screen or the
		// category management screen (which both occur via AJAX).
		if (
			empty( $_POST['action'] ) ||
			( 'editedtag' !== $_POST['action'] && 'inline-save-tax' !== $_POST['action'] )
		) {
			return;
		}
		// phpcs:enable

		WC_Tracks::record_event( 'product_category_update' );
	}

	/**
	 * Adds the tracking scripts for product filtering actions.
	 *
	 * @param string $hook Hook of the current page.
	 * @return string|boolean
	 */
	protected function get_product_screen( $hook ) {
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification
		if (
			'edit.php' === $hook &&
			isset( $_GET['post_type'] ) &&
			'product' === wp_unslash( $_GET['post_type'] )
		) {
			return 'list';
		}

		if (
			'post-new.php' === $hook &&
			'product' === wp_unslash( $_GET['post_type'] )
		) {
			return 'new';
		}

		if (
			'post.php' === $hook &&
			isset( $_GET['post'] ) &&
			'product' === get_post_type( intval( $_GET['post'] ) )
		) {
			return 'edit';
		}
		// phpcs:enable

		return false;
	}

	/**
	 * Adds the tracking scripts for product filtering actions.
	 *
	 * @param string $hook Page hook.
	 */
	public function possibly_add_product_tracking_scripts( $hook ) {
		$product_screen = $this->get_product_screen( $hook );
		if ( ! $product_screen ) {
			return;
		}

		$script_assets_filename = WCAdminAssets::get_script_asset_filename( 'wp-admin-scripts', 'product-tracking' );
		$script_assets          = require WC_ADMIN_ABSPATH . WC_ADMIN_DIST_JS_FOLDER . 'wp-admin-scripts/' . $script_assets_filename;

		wp_enqueue_script(
			'wc-admin-product-tracking',
			WCAdminAssets::get_url( 'wp-admin-scripts/product-tracking', 'js' ),
			array_merge( array( WC_ADMIN_APP ), $script_assets ['dependencies'] ),
			WCAdminAssets::get_file_version( 'js' ),
			true
		);

		wp_localize_script(
			'wc-admin-product-tracking',
			'productScreen',
			array(
				'name' => $product_screen,
			)
		);
	}

	/**
	 * Adds the tracking scripts for product attributes filtering actions.
	 *
	 * @param string $hook Page hook.
	 */
	public function possibly_add_attribute_tracking_scripts( $hook ) {
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification
		if (
			'product_page_product_attributes' !== $hook ||
			! isset( $_GET['page'] ) ||
			'product_attributes' !== wp_unslash( $_GET['page'] )
		) {
			return;
		}
		// phpcs:enable

		$script_assets_filename = WCAdminAssets::get_script_asset_filename( 'wp-admin-scripts', 'attributes-tracking' );
		$script_assets          = require WC_ADMIN_ABSPATH . WC_ADMIN_DIST_JS_FOLDER . 'wp-admin-scripts/' . $script_assets_filename;

		wp_enqueue_script(
			'wc-admin-attributes-tracking',
			WCAdminAssets::get_url( 'wp-admin-scripts/attributes-tracking', 'js' ),
			array_merge( array( WC_ADMIN_APP ), $script_assets ['dependencies'] ),
			WCAdminAssets::get_file_version( 'js' ),
			true
		);
	}

	/**
	 * Adds the tracking scripts for tags and categories filtering actions.
	 *
	 * @param string $hook Page hook.
	 */
	public function possibly_add_tag_tracking_scripts( $hook ) {
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification
		if (
			'edit-tags.php' !== $hook ||
			! isset( $_GET['post_type'] ) ||
			'product' !== wp_unslash( $_GET['post_type'] )
		) {
			return;
		}
		// phpcs:enable

		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if (
			isset( $_GET['taxonomy'] ) &&
			'product_tag' === wp_unslash( $_GET['taxonomy'] )
		) {
			// phpcs:enable
			$tags_script_assets_filename = WCAdminAssets::get_script_asset_filename( 'wp-admin-scripts', 'tags-tracking' );
			$tags_script_assets          = require WC_ADMIN_ABSPATH . WC_ADMIN_DIST_JS_FOLDER . 'wp-admin-scripts/' . $tags_script_assets_filename;

			wp_enqueue_script(
				'wc-admin-tags-tracking',
				WCAdminAssets::get_url( 'wp-admin-scripts/tags-tracking', 'js' ),
				array_merge( array( WC_ADMIN_APP ), $tags_script_assets ['dependencies'] ),
				WCAdminAssets::get_file_version( 'js' ),
				true
			);
			return;
		}

		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if (
			isset( $_GET['taxonomy'] ) &&
			'product_cat' === wp_unslash( $_GET['taxonomy'] )
		) {
			// phpcs:enable
			$category_script_assets_filename = WCAdminAssets::get_script_asset_filename( 'wp-admin-scripts', 'category-tracking' );
			$category_script_assets          = require WC_ADMIN_ABSPATH . WC_ADMIN_DIST_JS_FOLDER . 'wp-admin-scripts/' . $category_script_assets_filename;

			wp_enqueue_script(
				'wc-admin-category-tracking',
				WCAdminAssets::get_url( 'wp-admin-scripts/category-tracking', 'js' ),
				array_merge( array( WC_ADMIN_APP ), $category_script_assets ['dependencies'] ),
				WCAdminAssets::get_file_version( 'js' ),
				true
			);
			return;
		}

		$script_assets_filename = WCAdminAssets::get_script_asset_filename( 'wp-admin-scripts', 'add-term-tracking' );
		$script_assets          = require WC_ADMIN_ABSPATH . WC_ADMIN_DIST_JS_FOLDER . 'wp-admin-scripts/' . $script_assets_filename;

		wp_enqueue_script(
			'wc-admin-add-term-tracking',
			WCAdminAssets::get_url( 'wp-admin-scripts/add-term-tracking', 'js' ),
			array_merge( array( WC_ADMIN_APP ), $script_assets ['dependencies'] ),
			WCAdminAssets::get_file_version( 'js' ),
			true
		);
	}
}
