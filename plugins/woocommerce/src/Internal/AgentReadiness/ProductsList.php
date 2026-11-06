<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\Features\FeaturesController;

defined( 'ABSPATH' ) || exit;

/**
 * The Products list filter and column for readiness problems, and keeping each
 * product's stored failures current when it is saved.
 *
 * @since 11.4.0
 */
class ProductsList {

	public const FILTER_PARAM = 'agent_readiness';

	private const COLUMN = 'agent_readiness';

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
		add_filter( 'manage_product_posts_columns', array( $this, 'add_column' ), 20 );
		add_action( 'manage_product_posts_custom_column', array( $this, 'render_column' ), 10, 2 );
		add_action( 'restrict_manage_posts', array( $this, 'render_filter' ) );
		add_action( 'pre_get_posts', array( $this, 'apply_filter' ) );
		add_action( 'admin_notices', array( $this, 'render_editor_notice' ) );
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
	 * Add the column once a scan has run.
	 *
	 * @internal
	 *
	 * @param mixed $columns Columns.
	 * @return mixed
	 */
	public function add_column( $columns ) {
		if ( ! is_array( $columns ) || ! $this->agent_readiness->has_report() || ! $this->enabled() ) {
			return $columns;
		}
		$columns[ self::COLUMN ] = __( 'Agents', 'woocommerce' );
		return $columns;
	}

	/**
	 * Show where a product stands.
	 *
	 * @internal
	 *
	 * @param mixed $column  Column id.
	 * @param mixed $post_id Product id.
	 */
	public function render_column( $column, $post_id ): void {
		if ( self::COLUMN !== $column ) {
			return;
		}
		echo esc_html( $this->status_text( (int) $post_id ) );
	}

	/**
	 * Add the filter once a scan has run. Only problems some product has are offered.
	 *
	 * @internal
	 *
	 * @param mixed $post_type Post type of the list.
	 */
	public function render_filter( $post_type ): void {
		$report = 'product' === $post_type && $this->enabled() ? $this->agent_readiness->get_cached_report() : null;
		if ( null === $report ) {
			return;
		}
		$counts = ProductFailures::counts();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- A list filter that changes nothing.
		$current = isset( $_GET[ self::FILTER_PARAM ] ) ? sanitize_text_field( wp_unslash( $_GET[ self::FILTER_PARAM ] ) ) : '';
		$options = array();
		foreach ( FeedChannels::all() as $id => $channel ) {
			/* translators: %s: channel name. */
			$options[ ProductFailures::channel_value( ProductScan::NOT_LISTABLE, $id ) ] = sprintf( __( 'Can\'t be listed on %s', 'woocommerce' ), $channel['name'] );
		}
		foreach ( is_array( $report['checks'] ?? null ) ? $report['checks'] : array() as $check ) {
			if ( is_array( $check ) && isset( $check['id'], $check['label'] ) ) {
				$options[ (string) $check['id'] ] = (string) $check['label'];
			}
		}
		echo '<select name="' . esc_attr( self::FILTER_PARAM ) . '"><option value="">' . esc_html__( 'Filter by agent readiness', 'woocommerce' ) . '</option>';
		foreach ( $options as $value => $label ) {
			$value = (string) $value;
			if ( isset( $counts[ $value ] ) || $current === $value ) {
				echo '<option value="' . esc_attr( $value ) . '"' . selected( $current, $value, false ) . '>' . esc_html( $label . ' (' . number_format_i18n( $counts[ $value ] ?? 0 ) . ')' ) . '</option>';
			}
		}
		echo '</select>';
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
	 * On the product editor, name what the product fails and for which channels. Read now, so it goes after a fix is saved.
	 *
	 * @internal
	 */
	public function render_editor_notice(): void {
		$screen = get_current_screen();
		if ( null === $screen || 'post' !== $screen->base || 'product' !== $screen->post_type || 'add' === $screen->action || ! $this->enabled() ) {
			return;
		}
		$post_id = get_the_ID();
		$product = false !== $post_id ? wc_get_product( $post_id ) : null;
		$result  = $product instanceof \WC_Product ? $this->agent_readiness->check_product( $product ) : null;
		if ( null === $result || array() === $result['checks'] ) {
			return;
		}
		$fixes    = $this->agent_readiness->get_check_fixes();
		$channels = FeedChannels::all();
		echo '<div class="notice notice-warning"><p>' . esc_html__( 'AI shopping apps may skip this product or find it harder to recommend:', 'woocommerce' ) . '</p><ul>';
		foreach ( $result['checks'] as $id => $check_channels ) {
			$names = array_column( array_intersect_key( $channels, $check_channels ), 'name' );
			/* translators: 1: what to change on the product, 2: list of channel names, or "product page". */
			echo '<li>' . esc_html( sprintf( __( '%1$s (%2$s)', 'woocommerce' ), $fixes[ $id ] ?? $id, array() === $names ? __( 'product page', 'woocommerce' ) : implode( ', ', $names ) ) ) . '</li>';
		}
		echo '</ul></div>';
	}

	/**
	 * One line on a product: which channels it can't list on or has gaps on, else whether it passed.
	 *
	 * @param int $product_id Product id.
	 */
	private function status_text( int $product_id ): string {
		$stored = ProductFailures::stored( $product_id );
		if ( ! in_array( ProductFailures::CHECKED, $stored, true ) ) {
			$product = wc_get_product( $product_id );
			return $product instanceof \WC_Product && ProductScan::covers( $product ) ? __( 'Not checked', 'woocommerce' ) : '–';
		}
		$values = array_diff( $stored, array( ProductFailures::CHECKED ) );
		if ( array() === $values ) {
			return __( 'Ready', 'woocommerce' );
		}
		$names = static fn( string $status ): array => array_column(
			array_filter(
				FeedChannels::all(),
				static fn( string $id ): bool => in_array( ProductFailures::channel_value( $status, $id ), $values, true ),
				ARRAY_FILTER_USE_KEY
			),
			'name'
		);
		$parts = array();
		if ( array() !== $names( ProductScan::NOT_LISTABLE ) ) {
			/* translators: %s: list of channel names. */
			$parts[] = sprintf( __( 'Can\'t be listed: %s', 'woocommerce' ), implode( ', ', $names( ProductScan::NOT_LISTABLE ) ) );
		}
		if ( array() !== $names( ProductScan::WITH_GAPS ) ) {
			/* translators: %s: list of channel names. */
			$parts[] = sprintf( __( 'Gaps: %s', 'woocommerce' ), implode( ', ', $names( ProductScan::WITH_GAPS ) ) );
		}
		return array() === $parts ? __( 'Product page problem', 'woocommerce' ) : implode( '. ', $parts );
	}

	/**
	 * Whether the feature is on.
	 */
	private function enabled(): bool {
		return $this->features_controller->feature_is_enabled( AgenticCommerceSettings::FEATURE_ID );
	}
}
