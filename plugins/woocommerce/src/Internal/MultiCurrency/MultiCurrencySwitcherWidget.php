<?php
/**
 * MultiCurrencySwitcherWidget class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency;

use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencySwitcherProjectionService;

/**
 * Native multi-currency switcher widget.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencySwitcherWidget extends \WC_Widget {

	/**
	 * Switcher projection service.
	 *
	 * @var MultiCurrencySwitcherProjectionService
	 */
	private MultiCurrencySwitcherProjectionService $switcher_projection_service;

	/**
	 * Compatibility controller.
	 *
	 * @var MultiCurrencyCompatibilityController
	 */
	private MultiCurrencyCompatibilityController $compatibility_controller;

	/**
	 * Constructor.
	 *
	 * @param MultiCurrencySwitcherProjectionService $switcher_projection_service Switcher projection service.
	 * @param MultiCurrencyCompatibilityController   $compatibility_controller    Compatibility controller.
	 */
	public function __construct(
		MultiCurrencySwitcherProjectionService $switcher_projection_service,
		MultiCurrencyCompatibilityController $compatibility_controller
	) {
		$this->switcher_projection_service = $switcher_projection_service;
		$this->compatibility_controller    = $compatibility_controller;

		$this->widget_cssclass    = 'woocommerce widget_currency_switcher';
		$this->widget_description = __( 'Let customers switch between enabled currencies.', 'woocommerce' );
		$this->widget_id          = 'currency_switcher_widget';
		$this->widget_name        = __( 'Currency switcher widget', 'woocommerce' );
		$this->settings           = array(
			'title'  => array(
				'type'  => 'text',
				'std'   => '',
				'label' => __( 'Title', 'woocommerce' ),
			),
			'symbol' => array(
				'type'  => 'checkbox',
				'std'   => true,
				'label' => __( 'Display currency symbols', 'woocommerce' ),
			),
			'flag'   => array(
				'type'  => 'checkbox',
				'std'   => false,
				'label' => __( 'Display flags on supported devices', 'woocommerce' ),
			),
		);

		parent::__construct();
	}

	/**
	 * Output widget markup.
	 *
	 * @see \WP_Widget
	 *
	 * @param array<mixed> $args     Widget arguments.
	 * @param array<mixed> $instance Saved values from the database.
	 */
	public function widget( $args, $instance ): void {
		$markup = $this->switcher_projection_service->get_widget_markup(
			is_array( $instance ) ? $instance : array(),
			is_array( $args ) ? $args : array(),
			$this->get_current_query_args(),
			$this->compatibility_controller->should_disable_currency_switching()
		);

		echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Switcher projection service escapes the generated markup.
	}

	/**
	 * Get sanitized query args to preserve in the switcher form.
	 *
	 * @return array<string,mixed>
	 */
	private function get_current_query_args(): array {
		$query_args = wc_clean( wp_unslash( $_GET ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only query preservation for switcher form inputs.

		return is_array( $query_args ) ? $query_args : array();
	}
}
