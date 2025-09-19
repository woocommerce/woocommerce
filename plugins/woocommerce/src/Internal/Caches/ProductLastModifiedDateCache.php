<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Caches;

use Automattic\WooCommerce\Utilities\TimeUtil;
use WC_Product_Variation;
use DateTime;
use get_transient;

/**
 * Class to handle retrieval and caching of last modified date for products.
 *
 * This class exposes a get_last_modified_date method that will retrieve the last modified
 * date of a given product and store it in a transient for further faster retrieval.
 * Product and variation modifications and deletions are intercepted to delete the corresponding
 * cached entries.
 */
class ProductLastModifiedDateCache {

	/**
	 * Time to live for cached entries.
	 */
	private const CACHE_DURATION = DAY_IN_SECONDS;

	/**
	 * Set to true if products are stored as custom post types, false otherwise.
	 *
	 * @var bool
	 */
	private bool $products_are_stored_as_posts;

	/**
	 * Create a new instance of the class.
	 */
	public function __construct() {
		$this->products_are_stored_as_posts = is_a( \WC_Data_Store::load( 'product' )->get_current_class_name(), 'WC_Product_Data_Store_CPT', true );

		add_action( 'woocommerce_new_product', array( $this, 'on_product_created_or_modified' ), 10, 2 );
		add_action( 'woocommerce_update_product', array( $this, 'on_product_created_or_modified' ), 10, 2 );
		add_Action( 'woocommerce_trash_product', array( $this, 'on_product_trashed_or_deleted' ) );
		add_Action( 'woocommerce_before_delete_product', array( $this, 'on_product_trashed_or_deleted' ) );

		add_action( 'woocommerce_new_product_variation', array( $this, 'on_variation_created_or_modified' ), 10, 2 );
		add_action( 'woocommerce_update_product_variation', array( $this, 'on_variation_created_or_modified' ), 10, 2 );
		add_action( 'woocommerce_before_delete_product_variation', array( $this, 'on_variation_trashed_or_deleted' ) );
		add_action( 'woocommerce_trash_product_variation', array( $this, 'on_variation_trashed_or_deleted' ) );
	}

	/**
	 * Get the last modified date of a product given the product slug.
	 * If it's already cached, return the cached value; otherwise, get the value
	 * and cache it.
	 *
	 * The value is retrieved directly from the posts table if products are stored
	 * as posts, with a fallback to wc_get_products when that's not the case.
	 *
	 * @param string $product_slug The slug of the product.
	 * @return DateTime|null The last modification date of the product, null if there's no product with such slug.
	 */
	public function get_last_modified_date( string $product_slug ): ?DateTime {
		$transient_key = self::get_transient_key( $product_slug );

		$last_modified_date = get_transient( $transient_key );
		if ( false !== $last_modified_date ) {
			return $last_modified_date;
		}

		global $wpdb;

		if ( $this->products_are_stored_as_posts ) {
			$date_string = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT post_modified_gmt FROM {$wpdb->posts} WHERE post_name=%s AND post_type=%s AND post_status=%s",
					$product_slug,
					'product',
					'publish'
				)
			);
			if ( is_null( $date_string ) ) {
				return null;
			}

			$date_object = DateTime::createFromFormat( 'Y-m-d H:i:s', $date_string );
			if ( false === $date_object ) {
				return null;
			}
		} else {
			$product = wc_get_products(
				array(
					'name'   => $product_slug,
					'limit'  => 1,
					'status' => 'publish',
				)
			)[0] ?? null;
			if ( ! $product || ( $product instanceof WC_Product_Variation ) ) {
				return null;
			}

			$date_object = $product->get_date_modified();
			$date_object->setTimezone( TimeUtil::get_utc_date_time_zone() );
		}

		set_transient( $transient_key, $date_object, CACHE_DURATION );
		return $date_object;
	}

	/**
	 * Delete the cached last modified date for a given product.
	 *
	 * @param string $product_slug The product slug.
	 */
	public function delete_cached_last_modified_date( string $product_slug ): void {
		delete_transient( self::get_transient_key( $product_slug ) );
	}

	/**
	 * Handler for the woocommerce_new_product and woocommerce_update_product actions.
	 *
	 * @param int         $product_id Product id.
	 * @param \WC_Product $product Product object.
	 *
	 * @internal
	 */
	public function on_product_created_or_modified( int $product_id, \WC_Product $product ) {
		$this->delete_cached_last_modified_date( $product->get_slug() );
	}

	/**
	 * Handler for the woocommerce_trash_product and woocommerce_before_delete_product actions.
	 *
	 * @param int $product_id Product id.
	 *
	 * @internal
	 */
	public function on_product_trashed_or_deleted( int $product_id ) {
		$slug = $this->get_slug_for_product( $product_id );
		if ( $slug ) {
			$this->delete_cached_last_modified_date( $slug );
		}
	}

	/**
	 * Get the slug of a product given its product id.
	 *
	 * @param int $product_id Product id.
	 * @return string|null Product slug, or null if the product doesn't exist.
	 */
	private function get_slug_for_product( int $product_id ): ?string {
		global $wpdb;

		if ( $this->products_are_stored_as_posts ) {
			$slug = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT post_name FROM {$wpdb->posts} WHERE ID=%d",
					$product_id
				)
			);

			return $slug ? $slug : null;
		} else {
			$product = wc_get_product( $product_id );
			return $product ? $product->get_slug() : null;
		}
	}


	/**
	 * Handler for the woocommerce_new_product_variation and woocommerce_new_product_variation actions,
	 * handles the deletion of the cached entry for the parent product.
	 *
	 * @param int                  $variation_id Variation id.
	 * @param WC_Product_Variation $variation Variation object.
	 *
	 * @internal
	 */
	public function on_variation_created_or_modified( int $variation_id, WC_Product_Variation $variation ): void {
		$parent_slug = $this->get_parent_slug_for_variation( $variation );

		if ( $parent_slug ) {
			$this->delete_cached_last_modified_date( $parent_slug );
		}
	}

	/**
	 * Handler for the woocommerce_trash_product_variation and woocommerce_before_delete_product_variation actions,
	 * handles the deletion of the cached entry for the parent product.
	 *
	 * @param int $variation_id Variation id.
	 *
	 * @internal
	 */
	public function on_variation_trashed_or_deleted( int $variation_id ) {
		$parent_slug = $this->get_parent_slug_for_variation( $variation_id );

		if ( $parent_slug ) {
			$this->delete_cached_last_modified_date( $parent_slug );
		}
	}

	/**
	 * Returns the slug of the parent product for a given product variation.
	 *
	 * @param WC_Product_Variation|int $variation_id_or_object Product variation object or id.
	 * @return string|null Slug of the parent product, or null if the variation doesn't exist or doesn't have a parent.
	 */
	private function get_parent_slug_for_variation( $variation_id_or_object ): ?string {
		if ( $this->products_are_stored_as_posts ) {
			global $wpdb;

			$variation_id = is_numeric( $variation_id_or_object ) ? $variation_id_or_object : $variation_id_or_object->get_id();

			$parent_slug = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT post_name
			         FROM {$wpdb->posts}
         			 WHERE ID = (
						SELECT post_parent
             			FROM {$wpdb->posts}
             			WHERE ID = %d
					 AND post_type = 'product_variation'
         			 )
         			AND post_type = 'product'
					AND post_status = 'publish'",
					$variation_id
				)
			);

			return $parent_slug ? $parent_slug : null;
		} else {
			$variation = is_numeric( $variation_id_or_object ) ? wc_get_product( $variation_id_or_object ) : $variation_id_or_object;
			if ( $variation instanceof WC_Product_Variation ) {
				$parent_id      = $variation->get_parent_id();
				$parent_product = wc_get_product( $parent_id );
				return $parent_product ? $parent_product->get_slug() : null;
			}
		}

		return null;
	}

	/**
	 * Get the transient key for a given product slug.
	 *
	 * @param string $product_slug Product slug.
	 * @return string String to used as the transient key for the product.
	 */
	private static function get_transient_key( string $product_slug ): string {
		return 'wc_order_last_modified-' . md5( $product_slug );
	}
}
