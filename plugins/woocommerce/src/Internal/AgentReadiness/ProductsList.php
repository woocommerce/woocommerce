<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\Features\FeaturesController;

defined( 'ABSPATH' ) || exit;

/**
 * Keeping each product's stored failures current when it is saved, and the Products list
 * filter behind Sales readiness' links to the products a check failed.
 *
 * @since 11.4.0
 */
class ProductsList {

	public const FILTER_PARAM = 'agent_readiness';

	/**
	 * What a save may change without changing a check's result. A checkout saves products with only these.
	 */
	private const STOCK_ONLY_CHANGES = array( 'stock_quantity', 'stock_status', 'total_sales', 'date_modified' );

	/**
	 * Meta the brand and MPN checks read. A save can change only these, and get_changes() lists properties.
	 */
	private const READINESS_META = array( '_wc_gla_brand', '_wc_gla_mpn' );

	/**
	 * Features controller.
	 *
	 * @var FeaturesController
	 */
	private FeaturesController $features_controller;

	/**
	 * Readiness service.
	 *
	 * @var AgentReadiness
	 */
	private AgentReadiness $agent_readiness;

	/**
	 * Ids of products being saved with stock changes only.
	 *
	 * @var array<int, true>
	 */
	private array $stock_only_saves = array();

	/**
	 * Parent ids of variations being deleted, by variation id.
	 *
	 * @var array<int, int>
	 */
	private array $deleted_variation_parents = array();

	/**
	 * Parents to test again at the end of the request, as keys.
	 *
	 * @var array<int, true>
	 */
	private array $parents_to_retest = array();

	/**
	 * Initialize dependencies.
	 *
	 * @internal
	 *
	 * @param FeaturesController $features_controller Features controller.
	 * @param AgentReadiness     $agent_readiness     Readiness service.
	 */
	final public function init( FeaturesController $features_controller, AgentReadiness $agent_readiness ): void {
		$this->features_controller = $features_controller;
		$this->agent_readiness     = $agent_readiness;
	}

	/**
	 * Register the hooks.
	 */
	public function register(): void {
		add_action( 'woocommerce_before_product_object_save', array( $this, 'handle_before_save' ) );
		add_action( 'woocommerce_after_product_object_save', array( $this, 'handle_after_save' ) );
		add_action( 'wp_trash_post', array( $this, 'handle_trash' ) );
		add_action( 'untrashed_post', array( $this, 'handle_untrash' ) );
		add_action( 'woocommerce_trash_product_variation', array( $this, 'handle_variation_removed' ) );
		add_action( 'woocommerce_before_delete_product_variation', array( $this, 'handle_before_variation_delete' ) );
		add_action( 'woocommerce_delete_product_variation', array( $this, 'handle_variation_removed' ) );
		add_action( 'pre_get_posts', array( $this, 'apply_filter' ) );
	}

	/**
	 * The Products list showing the products with a stored value.
	 *
	 * @param string $value Check id or channel value.
	 */
	public static function url( string $value ): string {
		return add_query_arg(
			array(
				'post_type'        => 'product',
				'post_status'      => 'publish',
				self::FILTER_PARAM => $value,
			),
			admin_url( 'edit.php' )
		);
	}

	/**
	 * Note a save that changes only stock, before the changes are applied.
	 *
	 * @internal
	 *
	 * @param mixed $product Product being saved.
	 */
	public function handle_before_save( $product ): void {
		if ( ! $product instanceof \WC_Product ) {
			return;
		}
		$changes = array_keys( $product->get_changes() );
		if ( array() === array_diff( $changes, self::STOCK_ONLY_CHANGES ) && ! self::readiness_meta_changed( $product ) ) {
			$this->stock_only_saves[ $product->get_id() ] = true;
		}
	}

	/**
	 * Whether this save changes the brand or MPN meta the checks read.
	 *
	 * @param \WC_Product $product Product being saved.
	 */
	private static function readiness_meta_changed( \WC_Product $product ): bool {
		foreach ( self::READINESS_META as $key ) {
			if ( $product->get_meta( $key ) !== get_post_meta( $product->get_id(), $key, true ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Update a saved product's stored failures. A variation updates its parent.
	 *
	 * @internal
	 *
	 * @param mixed $product Saved product.
	 */
	public function handle_after_save( $product ): void {
		if ( ! $product instanceof \WC_Product ) {
			return;
		}
		$stock_only = isset( $this->stock_only_saves[ $product->get_id() ] );
		unset( $this->stock_only_saves[ $product->get_id() ] );
		if ( ! $stock_only ) {
			$this->retest( $product );
		}
	}

	/**
	 * Store a product's failures again, once a scan has run. A variation updates its parent.
	 *
	 * @param \WC_Product $product Product.
	 */
	private function retest( \WC_Product $product ): void {
		if ( ! $this->agent_readiness->has_report() || ! $this->enabled() ) {
			return;
		}
		$product_id = $product instanceof \WC_Product_Variation ? $product->get_parent_id() : $product->get_id();
		$product    = $this->reload( $product_id, $product );
		if ( null === $product ) {
			ProductFailures::forget( $product_id );
			return;
		}
		$result = $this->agent_readiness->check_product( $product );
		if ( null === $result ) {
			ProductFailures::forget( $product_id );
			return;
		}
		ProductFailures::store( $product_id, $result['checks'] );
	}

	/**
	 * Load the product from the database.
	 * The instance already in memory keeps variations that were removed in this request.
	 *
	 * @param int         $product_id Product id.
	 * @param \WC_Product $product    Product currently in memory.
	 */
	private function reload( int $product_id, \WC_Product $product ): ?\WC_Product {
		if ( $product_id <= 0 ) {
			return null;
		}
		$current = $product instanceof \WC_Product_Variation ? wc_get_product( $product_id ) : $product;
		$ids     = array( $product_id );
		if ( $current instanceof \WC_Product_Variable ) {
			$ids = array_merge( $ids, $current->get_children() );
		}
		ProductFields::forget_variations( $product_id );
		delete_transient( 'wc_product_children_' . $product_id );
		$cache = wc_get_container()->get( \Automattic\WooCommerce\Internal\Caches\ProductCache::class );
		foreach ( $ids as $id ) {
			$cache->remove( (int) $id );
		}
		$fresh = wc_get_product( $product_id );
		return $fresh instanceof \WC_Product ? $fresh : null;
	}

	/**
	 * Remove a trashed product's rows: the list and the counts show published products.
	 *
	 * @internal
	 *
	 * @param mixed $post_id Post id.
	 */
	public function handle_trash( $post_id ): void {
		if ( 'product' === get_post_type( $post_id ) ) {
			ProductFailures::forget( (int) $post_id );
		}
	}

	/**
	 * Test a restored product again.
	 *
	 * @internal
	 *
	 * @param mixed $post_id Post id.
	 */
	public function handle_untrash( $post_id ): void {
		$type = get_post_type( $post_id );
		if ( 'product' === $type ) {
			$this->handle_after_save( wc_get_product( (int) $post_id ) );
		} elseif ( 'product_variation' === $type ) {
			$this->handle_variation_removed( $post_id );
		}
	}

	/**
	 * Note a variation's parent before the variation is deleted, when it can still be read.
	 *
	 * @internal
	 *
	 * @param mixed $variation_id Variation id.
	 */
	public function handle_before_variation_delete( $variation_id ): void {
		$this->deleted_variation_parents[ (int) $variation_id ] = (int) wp_get_post_parent_id( (int) $variation_id );
	}

	/**
	 * Note a parent to test again after one of its variations is trashed, restored or deleted.
	 * Removing a variation doesn't save the parent. Removing many fires this once each, so the
	 * parent is tested once, at the end of the request.
	 *
	 * @internal
	 *
	 * @param mixed $variation_id Variation id.
	 */
	public function handle_variation_removed( $variation_id ): void {
		$variation_id = (int) $variation_id;
		$parent_id    = $this->deleted_variation_parents[ $variation_id ] ?? (int) wp_get_post_parent_id( $variation_id );
		unset( $this->deleted_variation_parents[ $variation_id ] );
		if ( $parent_id <= 0 ) {
			return;
		}
		if ( false === has_action( 'shutdown', array( $this, 'retest_parents' ) ) ) {
			add_action( 'shutdown', array( $this, 'retest_parents' ) );
		}
		$this->parents_to_retest[ $parent_id ] = true;
	}

	/**
	 * Test the noted parents again. A parent deleted in the same request has its rows removed,
	 * so a deleted variable product doesn't keep counting.
	 *
	 * @internal
	 */
	public function retest_parents(): void {
		$parent_ids              = array_keys( $this->parents_to_retest );
		$this->parents_to_retest = array();
		foreach ( $parent_ids as $parent_id ) {
			$parent = 'product' === get_post_type( $parent_id ) ? wc_get_product( $parent_id ) : null;
			if ( $parent instanceof \WC_Product ) {
				$this->retest( $parent );
			} else {
				ProductFailures::forget( $parent_id );
			}
		}
	}

	/**
	 * Limit the Products list to the chosen problem.
	 *
	 * @internal
	 *
	 * @param mixed $query Query.
	 */
	public function apply_filter( $query ): void {
		global $pagenow;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- A list filter that changes nothing.
		if ( ! $query instanceof \WP_Query || ! is_admin() || 'edit.php' !== $pagenow || ! $query->is_main_query() || 'product' !== $query->get( 'post_type' ) || ! $this->enabled() || empty( $_GET[ self::FILTER_PARAM ] ) ) {
			return;
		}
		// The list's own meta query stays a group of its own, so an OR in it can't widen this filter.
		$existing   = array_filter( (array) $query->get( 'meta_query' ) );
		$meta_query = array(
			'relation' => 'AND',
			array(
				'key'   => ProductFailures::META_KEY,
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- A list filter that changes nothing.
				'value' => sanitize_text_field( wp_unslash( $_GET[ self::FILTER_PARAM ] ) ),
			),
		);
		if ( array() !== $existing ) {
			$meta_query[] = $existing;
		}
		$query->set( 'meta_query', $meta_query ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
	}

	/**
	 * Whether the feature is on.
	 */
	private function enabled(): bool {
		return $this->features_controller->feature_is_enabled( AgenticCommerceSettings::FEATURE_ID );
	}
}
