<?php
/**
 * MultiCurrencyAnalyticsController class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency;

use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyAnalyticsProjectionService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyAnalyticsSqlProjectionService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyStateBuilder;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyStateBuilderFactory;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * Registers native multi-currency analytics hooks when core owns multi-currency.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencyAnalyticsController implements RegisterHooksInterface {

	private const DISABLE_SELECT_FILTER       = 'wcpay_multi_currency_disable_filter_select_clauses';
	private const EXTEND_SELECT_FILTER        = 'wcpay_multi_currency_filter_select_clauses';
	private const DISABLE_JOIN_FILTER         = 'wcpay_multi_currency_disable_filter_join_clauses';
	private const EXTEND_JOIN_FILTER          = 'wcpay_multi_currency_filter_join_clauses';
	private const DISABLE_WHERE_FILTER        = 'wcpay_multi_currency_disable_filter_where_clauses';
	private const EXTEND_WHERE_FILTER         = 'wcpay_multi_currency_filter_where_clauses';
	private const DISABLE_ORDER_SELECT_FILTER = 'wcpay_multi_currency_disable_filter_select_orders_clauses';
	private const EXTEND_ORDER_SELECT_FILTER  = 'wcpay_multi_currency_filter_select_orders_clauses';

	/**
	 * Runtime owner arbiter.
	 *
	 * @var MultiCurrencyRuntimeArbiter
	 */
	private MultiCurrencyRuntimeArbiter $arbiter;

	/**
	 * State builder factory.
	 *
	 * @var MultiCurrencyStateBuilderFactory
	 */
	private MultiCurrencyStateBuilderFactory $state_builder_factory;

	/**
	 * Analytics projection service.
	 *
	 * @var MultiCurrencyAnalyticsProjectionService|null
	 */
	private ?MultiCurrencyAnalyticsProjectionService $analytics_projection_service = null;

	/**
	 * Analytics SQL projection service.
	 *
	 * @var MultiCurrencyAnalyticsSqlProjectionService|null
	 */
	private ?MultiCurrencyAnalyticsSqlProjectionService $sql_projection_service = null;

	/**
	 * Dev mode resolver.
	 *
	 * @var callable|null
	 */
	private $dev_mode_resolver = null;

	/**
	 * REST request resolver.
	 *
	 * @var callable|null
	 */
	private $rest_request_resolver = null;

	/**
	 * Multi-currency orders resolver.
	 *
	 * @var callable|null
	 */
	private $multi_currency_orders_resolver = null;

	/**
	 * HPOS enabled resolver.
	 *
	 * @var callable|null
	 */
	private $hpos_resolver = null;

	/**
	 * Request args resolver.
	 *
	 * @var callable|null
	 */
	private $request_args_resolver = null;

	/**
	 * Default currency resolver.
	 *
	 * @var callable|null
	 */
	private $default_currency_resolver = null;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param MultiCurrencyRuntimeArbiter      $arbiter               Runtime owner arbiter.
	 * @param MultiCurrencyStateBuilderFactory $state_builder_factory State builder factory.
	 */
	final public function init( MultiCurrencyRuntimeArbiter $arbiter, MultiCurrencyStateBuilderFactory $state_builder_factory ): void {
		$this->arbiter               = $arbiter;
		$this->state_builder_factory = $state_builder_factory;
	}

	/**
	 * Set the dev mode resolver.
	 *
	 * @internal Used by tests.
	 *
	 * @param callable $resolver Resolver returning whether dev mode is enabled.
	 */
	public function set_dev_mode_resolver( callable $resolver ): void {
		$this->dev_mode_resolver = $resolver;
	}

	/**
	 * Set the REST request resolver.
	 *
	 * @internal Used by tests.
	 *
	 * @param callable $resolver Resolver returning whether the request is REST.
	 */
	public function set_rest_request_resolver( callable $resolver ): void {
		$this->rest_request_resolver = $resolver;
	}

	/**
	 * Set the multi-currency orders resolver.
	 *
	 * @internal Used by tests.
	 *
	 * @param callable $resolver Resolver returning whether multi-currency orders exist.
	 */
	public function set_multi_currency_orders_resolver( callable $resolver ): void {
		$this->multi_currency_orders_resolver = $resolver;
	}

	/**
	 * Set the HPOS enabled resolver.
	 *
	 * @internal Used by tests.
	 *
	 * @param callable $resolver Resolver returning true when HPOS order storage is enabled.
	 */
	public function set_hpos_resolver( callable $resolver ): void {
		$this->hpos_resolver = $resolver;
	}

	/**
	 * Set the request args resolver.
	 *
	 * @internal Used by tests.
	 *
	 * @param callable $resolver Resolver returning request args.
	 */
	public function set_request_args_resolver( callable $resolver ): void {
		$this->request_args_resolver = $resolver;
	}

	/**
	 * Set the default currency resolver.
	 *
	 * @internal Used by tests.
	 *
	 * @param callable $resolver Resolver returning the default currency.
	 */
	public function set_default_currency_resolver( callable $resolver ): void {
		$this->default_currency_resolver = $resolver;
	}

	/**
	 * Set the analytics projection service.
	 *
	 * @internal Used by tests and future explicit bootstrap definitions.
	 *
	 * @param MultiCurrencyAnalyticsProjectionService $service Analytics projection service.
	 */
	public function set_analytics_projection_service( MultiCurrencyAnalyticsProjectionService $service ): void {
		$this->analytics_projection_service = $service;
	}

	/**
	 * Set the analytics SQL projection service.
	 *
	 * @internal Used by tests and future explicit bootstrap definitions.
	 *
	 * @param MultiCurrencyAnalyticsSqlProjectionService $service Analytics SQL projection service.
	 */
	public function set_sql_projection_service( MultiCurrencyAnalyticsSqlProjectionService $service ): void {
		$this->sql_projection_service = $service;
	}

	/**
	 * Register analytics hooks.
	 */
	public function register() {
		if ( ! $this->arbiter->should_core_register() ) {
			return;
		}

		if ( $this->is_dev_mode() ) {
			$this->add_filter_once( 'woocommerce_analytics_report_should_use_cache', array( $this, 'handle_woocommerce_analytics_report_should_use_cache' ) );
		}

		$this->add_filter_once( 'woocommerce_analytics_update_order_stats_data', array( $this, 'handle_woocommerce_analytics_update_order_stats_data' ), 99999, 2 );
		$this->add_filter_once( 'woocommerce_analytics_orders_query_args', array( $this, 'handle_woocommerce_analytics_orders_query_args' ) );
		$this->add_filter_once( 'woocommerce_analytics_orders_stats_query_args', array( $this, 'handle_woocommerce_analytics_orders_query_args' ) );

		if ( ! $this->is_rest_api_request() || ! $this->has_multi_currency_orders() ) {
			return;
		}

		$this->add_filter_once( 'woocommerce_analytics_clauses_select', array( $this, 'handle_woocommerce_analytics_clauses_select' ), 20, 2 );
		$this->add_filter_once( 'woocommerce_analytics_clauses_join', array( $this, 'handle_woocommerce_analytics_clauses_join' ), 20, 2 );
		$this->add_filter_once( 'woocommerce_analytics_clauses_where_orders_subquery', array( $this, 'handle_woocommerce_analytics_clauses_where' ) );
		$this->add_filter_once( 'woocommerce_analytics_clauses_where_orders_stats_total', array( $this, 'handle_woocommerce_analytics_clauses_where' ) );
		$this->add_filter_once( 'woocommerce_analytics_clauses_where_orders_stats_interval', array( $this, 'handle_woocommerce_analytics_clauses_where' ) );

		if ( $this->has_selected_non_default_currency() ) {
			$this->add_filter_once( 'woocommerce_analytics_clauses_select_orders_subquery', array( $this, 'handle_woocommerce_analytics_clauses_select_orders' ) );
			$this->add_filter_once( 'woocommerce_analytics_clauses_select_orders_stats_total', array( $this, 'handle_woocommerce_analytics_clauses_select_orders' ) );
		}
	}

	/**
	 * Disable analytics report caching for multi-currency dev mode.
	 *
	 * @internal
	 *
	 * @param mixed $args Filter value.
	 * @return bool
	 */
	public function handle_woocommerce_analytics_report_should_use_cache( $args ): bool {
		return false;
	}

	/**
	 * Convert order stats data to store default currency for multi-currency orders.
	 *
	 * @internal
	 *
	 * @param array<string,mixed> $args  Order stats args.
	 * @param mixed               $order Order.
	 * @return array<string,mixed>
	 */
	public function handle_woocommerce_analytics_update_order_stats_data( array $args, $order ): array {
		if ( ! $order instanceof \WC_Order ) {
			return $args;
		}

		return $this->get_analytics_projection_service()->update_order_stats_data( $args, $order );
	}

	/**
	 * Apply customer-currency request args to analytics query args.
	 *
	 * @internal
	 *
	 * @param array<string,mixed> $args Analytics args.
	 * @return array<string,mixed>
	 */
	public function handle_woocommerce_analytics_orders_query_args( array $args ): array {
		return $this->get_analytics_projection_service()->apply_customer_currency_args( $args, $this->get_request_args() );
	}

	/**
	 * Project multi-currency analytics SELECT clauses.
	 *
	 * @internal
	 *
	 * @param string[] $clauses SELECT clauses.
	 * @param mixed    $context Analytics context.
	 * @return string[]
	 */
	public function handle_woocommerce_analytics_clauses_select( array $clauses, $context = null ): array {
		if ( $this->is_clause_filter_disabled( self::DISABLE_SELECT_FILTER ) ) {
			return $clauses;
		}

		$projected = $this->get_sql_projection_service()->project_select_clauses(
			$clauses,
			is_string( $context ) ? $context : null,
			$this->is_hpos_enabled()
		);

		return $this->apply_clause_filter( self::EXTEND_SELECT_FILTER, $projected );
	}

	/**
	 * Project multi-currency analytics JOIN clauses.
	 *
	 * @internal
	 *
	 * @param string[] $clauses JOIN clauses.
	 * @param mixed    $context Analytics context.
	 * @return string[]
	 */
	public function handle_woocommerce_analytics_clauses_join( array $clauses, $context = '' ): array {
		if ( $this->is_clause_filter_disabled( self::DISABLE_JOIN_FILTER ) || ! is_string( $context ) ) {
			return $clauses;
		}

		$projected = $this->get_sql_projection_service()->project_join_clauses( $clauses, $context, $this->is_hpos_enabled() );

		return $this->apply_clause_filter( self::EXTEND_JOIN_FILTER, $projected );
	}

	/**
	 * Project multi-currency analytics WHERE clauses.
	 *
	 * @internal
	 *
	 * @param string[] $clauses WHERE clauses.
	 * @return string[]
	 */
	public function handle_woocommerce_analytics_clauses_where( array $clauses ): array {
		if ( $this->is_clause_filter_disabled( self::DISABLE_WHERE_FILTER ) ) {
			return $clauses;
		}

		$projected = $this->get_sql_projection_service()->project_where_clauses( $clauses, $this->get_request_args(), $this->is_hpos_enabled() );

		return $this->apply_clause_filter( self::EXTEND_WHERE_FILTER, $projected );
	}

	/**
	 * Project selected-currency order SELECT clauses.
	 *
	 * @internal
	 *
	 * @param string[] $clauses SELECT clauses.
	 * @return string[]
	 */
	public function handle_woocommerce_analytics_clauses_select_orders( array $clauses ): array {
		if ( $this->is_clause_filter_disabled( self::DISABLE_ORDER_SELECT_FILTER ) ) {
			return $clauses;
		}

		$projected = $this->get_sql_projection_service()->project_selected_currency_order_select_clauses( $clauses );

		return $this->apply_clause_filter( self::EXTEND_ORDER_SELECT_FILTER, $projected );
	}

	/**
	 * Get the analytics projection service.
	 *
	 * @return MultiCurrencyAnalyticsProjectionService
	 */
	private function get_analytics_projection_service(): MultiCurrencyAnalyticsProjectionService {
		if ( null === $this->analytics_projection_service ) {
			$this->analytics_projection_service = new MultiCurrencyAnalyticsProjectionService( $this->create_state_builder() );
		}

		return $this->analytics_projection_service;
	}

	/**
	 * Get the analytics SQL projection service.
	 *
	 * @return MultiCurrencyAnalyticsSqlProjectionService
	 */
	private function get_sql_projection_service(): MultiCurrencyAnalyticsSqlProjectionService {
		if ( null === $this->sql_projection_service ) {
			$this->sql_projection_service = new MultiCurrencyAnalyticsSqlProjectionService();
		}

		return $this->sql_projection_service;
	}

	/**
	 * Create a default state builder.
	 *
	 * @return MultiCurrencyStateBuilder
	 */
	private function create_state_builder(): MultiCurrencyStateBuilder {
		return $this->state_builder_factory->create();
	}

	/**
	 * Tell whether multi-currency dev mode is enabled.
	 *
	 * @return bool
	 */
	private function is_dev_mode(): bool {
		if ( null !== $this->dev_mode_resolver ) {
			return (bool) call_user_func( $this->dev_mode_resolver );
		}

		return defined( 'WC_STRIPE_DEV_MODE' ) && (bool) WC_STRIPE_DEV_MODE;
	}

	/**
	 * Tell whether the current request is a REST API request.
	 *
	 * @return bool
	 */
	private function is_rest_api_request(): bool {
		if ( null !== $this->rest_request_resolver ) {
			return (bool) call_user_func( $this->rest_request_resolver );
		}

		return function_exists( 'WC' ) && WC()->is_rest_api_request();
	}

	/**
	 * Tell whether the store has multi-currency orders.
	 *
	 * @return bool
	 */
	private function has_multi_currency_orders(): bool {
		if ( null !== $this->multi_currency_orders_resolver ) {
			return (bool) call_user_func( $this->multi_currency_orders_resolver );
		}

		global $wpdb;

		if ( $this->is_hpos_enabled() ) {
			$result = $wpdb->get_var(
				"SELECT EXISTS(
					SELECT 1
					FROM {$wpdb->prefix}wc_orders_meta
					WHERE meta_key = '_wcpay_multi_currency_order_exchange_rate'
					LIMIT 1)
				AS count;"
			);
		} else {
			$result = $wpdb->get_var(
				"SELECT EXISTS(
					SELECT 1
					FROM {$wpdb->postmeta}
					WHERE meta_key = '_wcpay_multi_currency_order_exchange_rate'
					LIMIT 1)
				AS count;"
			);
		}

		return 1 === (int) $result;
	}

	/**
	 * Tell whether HPOS order storage is enabled.
	 *
	 * @return bool
	 */
	private function is_hpos_enabled(): bool {
		if ( null !== $this->hpos_resolver ) {
			return (bool) call_user_func( $this->hpos_resolver );
		}

		return class_exists( OrderUtil::class ) && OrderUtil::custom_orders_table_usage_is_enabled();
	}

	/**
	 * Get request args.
	 *
	 * @return array<string,mixed>
	 */
	private function get_request_args(): array {
		if ( null !== $this->request_args_resolver ) {
			$args = call_user_func( $this->request_args_resolver );

			return is_array( $args ) ? $args : array();
		}

		return $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Get the default store currency.
	 *
	 * @return string
	 */
	private function get_default_currency(): string {
		if ( null !== $this->default_currency_resolver ) {
			return strtoupper( trim( (string) call_user_func( $this->default_currency_resolver ) ) );
		}

		return strtoupper( get_woocommerce_currency() );
	}

	/**
	 * Tell whether analytics should project selected-currency order totals.
	 *
	 * @return bool
	 */
	private function has_selected_non_default_currency(): bool {
		$currency = $this->get_request_args()['currency'] ?? null;
		if ( ! is_scalar( $currency ) ) {
			return false;
		}

		$currency = strtoupper( trim( (string) $currency ) );

		return '' !== $currency && $currency !== $this->get_default_currency();
	}

	/**
	 * Tell whether an extension disabled a clause filter.
	 *
	 * @param string $hook Hook name.
	 * @return bool
	 */
	private function is_clause_filter_disabled( string $hook ): bool {
		/**
		 * Filters whether a native multi-currency analytics SQL clause filter should be disabled.
		 *
		 * @param bool $disabled Whether the SQL clause filter should be disabled.
		 *
		 * @since 11.0.0
		 */
		return (bool) apply_filters( $hook, false );
	}

	/**
	 * Apply a WooPayments-compatible clause extension filter.
	 *
	 * @param string   $hook    Hook name.
	 * @param string[] $clauses Clauses.
	 * @return string[]
	 */
	private function apply_clause_filter( string $hook, array $clauses ): array {
		/**
		 * Filters native multi-currency analytics SQL clauses.
		 *
		 * @param string[] $clauses SQL clauses.
		 *
		 * @since 11.0.0
		 */
		$filtered = apply_filters( $hook, $clauses );

		return is_array( $filtered ) ? $filtered : $clauses;
	}

	/**
	 * Register a filter only once for this controller instance.
	 *
	 * @param string   $hook          Hook name.
	 * @param callable $callback      Hook callback.
	 * @param int      $priority      Hook priority.
	 * @param int      $accepted_args Accepted argument count.
	 */
	private function add_filter_once( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): void {
		if ( false === has_filter( $hook, $callback ) ) {
			add_filter( $hook, $callback, $priority, $accepted_args );
		}
	}
}
