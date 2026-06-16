<?php
/**
 * MultiCurrencySwitcherBlockController class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency;

use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyStateBuilderFactory;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencySwitcherProjectionService;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Registers native multi-currency switcher block rendering when core owns multi-currency.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencySwitcherBlockController implements RegisterHooksInterface {

	private const BLOCK_NAME = 'woocommerce-payments/multi-currency-switcher';

	/**
	 * Runtime owner arbiter.
	 *
	 * @var MultiCurrencyRuntimeArbiter
	 */
	private MultiCurrencyRuntimeArbiter $arbiter;

	/**
	 * Compatibility controller.
	 *
	 * @var MultiCurrencyCompatibilityController
	 */
	private MultiCurrencyCompatibilityController $compatibility_controller;

	/**
	 * Switcher projection service.
	 *
	 * @var MultiCurrencySwitcherProjectionService|null
	 */
	private ?MultiCurrencySwitcherProjectionService $switcher_projection_service = null;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param MultiCurrencyRuntimeArbiter          $arbiter                  Runtime owner arbiter.
	 * @param MultiCurrencyCompatibilityController $compatibility_controller Compatibility controller.
	 */
	final public function init(
		MultiCurrencyRuntimeArbiter $arbiter,
		MultiCurrencyCompatibilityController $compatibility_controller
	): void {
		$this->arbiter                  = $arbiter;
		$this->compatibility_controller = $compatibility_controller;
	}

	/**
	 * Set the switcher projection service.
	 *
	 * @internal Used by tests and future explicit bootstrap definitions.
	 *
	 * @param MultiCurrencySwitcherProjectionService $switcher_projection_service Switcher projection service.
	 */
	public function set_switcher_projection_service( MultiCurrencySwitcherProjectionService $switcher_projection_service ): void {
		$this->switcher_projection_service = $switcher_projection_service;
	}

	/**
	 * Register switcher block hooks.
	 */
	public function register() {
		if ( ! $this->arbiter->should_core_register() ) {
			return;
		}

		$this->add_action_once( 'init', array( $this, 'handle_init' ) );
	}

	/**
	 * Register the native switcher block type.
	 *
	 * @internal
	 */
	public function handle_init(): void {
		if ( \WP_Block_Type_Registry::get_instance()->is_registered( self::BLOCK_NAME ) ) {
			return;
		}

		register_block_type(
			self::BLOCK_NAME,
			// @phpstan-ignore-next-line argument.type (WordPress accepts integer api_version values and stores them unchanged at runtime.)
			array(
				'api_version'     => 3,
				'render_callback' => array( $this, 'render_block_widget' ),
				'attributes'      => self::get_block_attributes(),
			)
		);
	}

	/**
	 * Render the native switcher block.
	 *
	 * @param mixed $block_attributes Block attributes.
	 * @return string
	 */
	public function render_block_widget( $block_attributes ): string {
		return $this->get_switcher_projection_service()->get_block_markup(
			is_array( $block_attributes ) ? $block_attributes : array(),
			$this->get_current_query_args(),
			$this->compatibility_controller->should_disable_currency_switching()
		);
	}

	/**
	 * Get preserved WooPayments switcher block attributes.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	private static function get_block_attributes(): array {
		return array(
			'symbol'          => array(
				'type'    => 'boolean',
				'default' => true,
			),
			'flag'            => array(
				'type'    => 'boolean',
				'default' => false,
			),
			'fontSize'        => array(
				'type'    => 'integer',
				'default' => 14,
			),
			'fontLineHeight'  => array(
				'type'    => 'number',
				'default' => 1.5,
			),
			'fontColor'       => array(
				'type'    => 'string',
				'default' => '#000000',
			),
			'border'          => array(
				'type'    => 'boolean',
				'default' => true,
			),
			'borderRadius'    => array(
				'type'    => 'integer',
				'default' => 3,
			),
			'borderColor'     => array(
				'type'    => 'string',
				'default' => '#000000',
			),
			'backgroundColor' => array(
				'type'    => 'string',
				'default' => 'transparent',
			),
		);
	}

	/**
	 * Get sanitized current query arguments for switcher forms.
	 *
	 * @return array<string,mixed>
	 */
	private function get_current_query_args(): array {
		$query_args = wc_clean( wp_unslash( $_GET ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only query preservation for switcher form inputs.

		return is_array( $query_args ) ? $query_args : array();
	}

	/**
	 * Get the switcher projection service.
	 *
	 * @return MultiCurrencySwitcherProjectionService
	 */
	private function get_switcher_projection_service(): MultiCurrencySwitcherProjectionService {
		if ( null === $this->switcher_projection_service ) {
			$this->switcher_projection_service = new MultiCurrencySwitcherProjectionService(
				wc_get_container()->get( MultiCurrencyStateBuilderFactory::class )->create()
			);
		}

		return $this->switcher_projection_service;
	}

	/**
	 * Register an action only once for this controller instance.
	 *
	 * @param string   $hook          Hook name.
	 * @param callable $callback      Hook callback.
	 * @param int      $priority      Hook priority.
	 * @param int      $accepted_args Accepted argument count.
	 */
	private function add_action_once( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): void {
		if ( false === has_action( $hook, $callback ) ) {
			add_action( $hook, $callback, $priority, $accepted_args );
		}
	}
}
