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
		if ( array() === array_diff( $changes, self::STOCK_ONLY_CHANGES ) ) {
			$this->stock_only_saves[ $product->get_id() ] = true;
		}
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
		if ( $stock_only || ! $this->agent_readiness->has_report() || ! $this->enabled() ) {
			return;
		}
		$result     = $this->agent_readiness->check_product( $product );
		$product_id = $product instanceof \WC_Product_Variation ? $product->get_parent_id() : $product->get_id();
		if ( null === $result ) {
			ProductFailures::forget( $product_id );
			return;
		}
		ProductFailures::store( $product_id, $result['checks'] );
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
		if ( 'product' === get_post_type( $post_id ) ) {
			$this->handle_after_save( wc_get_product( (int) $post_id ) );
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
		$meta_query   = array_filter( (array) $query->get( 'meta_query' ) );
		$meta_query[] = array(
			'key'   => ProductFailures::META_KEY,
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- A list filter that changes nothing.
			'value' => sanitize_text_field( wp_unslash( $_GET[ self::FILTER_PARAM ] ) ),
		);
		$query->set( 'meta_query', $meta_query ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
	}

	/**
	 * Whether the feature is on.
	 */
	private function enabled(): bool {
		return $this->features_controller->feature_is_enabled( AgenticCommerceSettings::FEATURE_ID );
	}
}
