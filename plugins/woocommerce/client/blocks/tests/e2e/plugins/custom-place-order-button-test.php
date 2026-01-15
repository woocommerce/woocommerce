<?php
/**
 * Plugin Name: WooCommerce Blocks Test Custom Place Order Button
 * Description: Registers a test payment method with a custom place order button for e2e testing.
 * Plugin URI: https://github.com/woocommerce/woocommerce
 * Author: WooCommerce
 *
 * @package woocommerce-blocks-test-custom-place-order-button
 */

declare(strict_types=1);

add_action(
	'plugins_loaded',
	function () {
		if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
			return;
		}

		/**
		 * Test payment gateway with custom place order button.
		 */
		class WC_Gateway_Test_Custom_Button extends WC_Payment_Gateway {
			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->id                            = 'test-custom-button';
				$this->method_title                  = 'Test Custom Button';
				$this->method_description            = 'Test payment method with custom place order button';
				$this->title                         = 'Test Custom Button Payment';
				$this->description                   = 'Test payment method for e2e testing custom place order button';
				$this->has_fields                    = false;
				$this->supports                      = array( 'products' );
				$this->enabled                       = 'yes';
				$this->has_custom_place_order_button = true; // For shortcode checkout.
			}

			/**
			 * Process the payment.
			 *
			 * @param int $order_id Order ID.
			 * @return array
			 */
			public function process_payment( $order_id ) {
				$order = wc_get_order( $order_id );
				$order->payment_complete();

				return array(
					'result'   => 'success',
					'redirect' => $this->get_return_url( $order ),
				);
			}
		}

		add_filter(
			'woocommerce_payment_gateways',
			function ( $gateways ) {
				$gateways[] = 'WC_Gateway_Test_Custom_Button';
				return $gateways;
			}
		);
	}
);

add_action(
	'woocommerce_blocks_payment_method_type_registration',
	function ( $registry ) {
		$registry->register(
			new class() extends \Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType {
				/**
				 * Payment method name.
				 *
				 * @var string
				 */
				protected $name = 'test-custom-button';

				/**
				 * Initialize.
				 */
				public function initialize() {}

				/**
				 * Check if payment method is active.
				 *
				 * @return bool
				 */
				public function is_active() {
					return true;
				}

				/**
				 * Get payment method script handles.
				 *
				 * @return array
				 */
				public function get_payment_method_script_handles() {
					wp_register_script( 'test-custom-button-blocks', '', array( 'wc-blocks-registry' ), '1.0.0', true );
					wp_add_inline_script( 'test-custom-button-blocks', $this->get_inline_script() );
					return array( 'test-custom-button-blocks' );
				}

				/**
				 * Get payment method data.
				 *
				 * @return array
				 */
				public function get_payment_method_data() {
					return array(
						'title'       => 'Test Custom Button Payment',
						'description' => 'Test payment method for e2e testing',
					);
				}

				/**
				 * Get inline script for registering the payment method.
				 *
				 * @return string
				 */
				private function get_inline_script() {
					return <<<'JS'
(
	function() {
		const { registerPaymentMethod } = wc.wcBlocksRegistry;
		const { createElement } = wp.element;

		const CustomButton = function(props) {
			const handleClick = async function() {
				const result = await props.validate();
				if (result.hasError) {
					return;
				}

				props.onSubmit();
			};

			return createElement('button', {
				type: 'button',
				'data-testid': 'custom-place-order-button',
				onClick: handleClick,
				disabled: props.disabled,
				className: 'wc-block-components-button wp-element-button',
			}, 'Custom Payment Button');
		};

		const NoContent = function() {
			return null;
		};

		registerPaymentMethod({
			name: 'test-custom-button',
			label: 'Test Custom Button Payment',
			ariaLabel: 'Test Custom Button Payment',
			content: createElement(NoContent, null, null),
			edit: createElement(NoContent, null, null),
			canMakePayment: function() { return true; },
			placeOrderButton: CustomButton,
			supports: { features: ['products'] },
		});
	}
)();
JS;
				}
			}
		);
	}
);

add_action(
	'wp_enqueue_scripts',
	function () {
		if ( ! function_exists( 'is_checkout' ) || ! function_exists( 'is_wc_endpoint_url' ) ) {
			return;
		}

		$is_shortcode_checkout = is_checkout() && ! has_block( 'woocommerce/checkout' );
		$is_pay_for_order      = is_wc_endpoint_url( 'order-pay' );
		$is_add_payment_method = function_exists( 'is_add_payment_method_page' ) && is_add_payment_method_page();

		if ( ! $is_shortcode_checkout && ! $is_pay_for_order && ! $is_add_payment_method ) {
			return;
		}

		wp_register_script(
			'test-custom-button-shortcode',
			'',
			array( 'jquery', 'wc-custom-place-order-button' ),
			'1.0.0',
			true
		);

		$inline_script = <<<'JS'
(function($) {
	'use strict';

	wc.customPlaceOrderButton.register('test-custom-button', {
		render: function(container, api) {
			var $button = $('<button>', {
				type: 'button',
				'data-testid': 'custom-place-order-button',
				'class': 'button alt',
				text: 'Custom Payment Button',
			});

			$button.on('click', function(e) {
				e.preventDefault();

				api.validate().then(function(result) {
					if (result.hasError) {
						return;
					}

					api.submit();
				});
			});

			$(container).append($button);
		},
		cleanup: function() {},
	});
})(jQuery);
JS;

		wp_add_inline_script( 'test-custom-button-shortcode', $inline_script );
		wp_enqueue_script( 'test-custom-button-shortcode' );
	}
);
