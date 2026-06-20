<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsExpressCheckoutService;

/**
 * Recording express checkout service for controller tests.
 */
class RecordingExpressCheckoutService extends WooPaymentsExpressCheckoutService {

	/**
	 * Whether payment-request express checkout should be shown.
	 *
	 * @var bool
	 */
	public bool $should_show_payment_request_button = true;

	/**
	 * Contexts observed by the service.
	 *
	 * @var array<int,string>
	 */
	public array $contexts = array();

	/**
	 * Tell whether the payment-request button should be shown.
	 *
	 * @param string $context Express checkout context.
	 * @return bool
	 */
	public function should_show_payment_request_button( string $context = 'checkout' ): bool {
		$this->contexts[] = $context;

		return $this->should_show_payment_request_button;
	}

	/**
	 * Get express checkout params.
	 *
	 * @param string $context Express checkout context.
	 * @return array<string,mixed>
	 */
	public function get_express_checkout_params( string $context = 'checkout' ): array {
		return array(
			'ajax_url'        => admin_url( 'admin-ajax.php' ),
			'wc_ajax_url'     => \WC_AJAX::get_endpoint( '%%endpoint%%' ),
			'nonce'           => array(
				'platform_tracker'             => 'tracker-nonce',
				'tokenized_cart_nonce'         => 'cart-nonce',
				'tokenized_cart_session_nonce' => 'cart-session-nonce',
				'store_api_nonce'              => 'store-api-nonce',
			),
			'checkout'        => array(
				'currency_code'     => 'usd',
				'currency_decimals' => 2,
				'stripe_minor_unit' => 2,
				'country_code'      => 'US',
			),
			'button_context'  => $context,
			'enabled_methods' => array( 'payment_request' ),
			'button'          => array(
				'type'   => 'default',
				'theme'  => 'dark',
				'height' => '48',
				'radius' => '4',
				'size'   => 'medium',
			),
			'stripe'          => array(
				'publishableKey' => 'pk_test_123',
				'accountId'      => 'acct_123',
				'locale'         => 'en-us',
			),
			'flags'           => array(
				'isEceUsingConfirmationTokens' => true,
			),
		);
	}
}
