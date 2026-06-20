<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments;

use Automattic\WooCommerce\Enums\PaymentGatewayFeature;
use Automattic\WooCommerce\Internal\Payments\NativeWooPaymentsGateway;
use Automattic\WooCommerce\Internal\Payments\OrderPaymentStore;
use Automattic\WooCommerce\Internal\Payments\PaymentContext;
use Automattic\WooCommerce\Internal\Payments\PaymentOutcome;
use Automattic\WooCommerce\Internal\Payments\ProviderContract;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiClient;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsAccountService;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsCheckoutBridge;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsExpressPaymentMethodTypes;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsProvider;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsTokenService;
use WC_Order;
use WC_Payment_Token_CC;
use WC_Unit_Test_Case;

/**
 * Tests for the NativeWooPaymentsGateway class.
 */
class NativeWooPaymentsGatewayTest extends WC_Unit_Test_Case {

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_all_actions( 'woocommerce_scheduled_subscription_payment_' . OrderPaymentStore::GATEWAY_ID );
		remove_all_actions( 'woocommerce_subscription_failing_payment_method_updated_' . OrderPaymentStore::GATEWAY_ID );
		remove_all_actions( 'woocommerce_woocommerce_payments_payment_requires_action' );
		remove_all_filters( 'woocommerce_native_woopayments_subscriptions_for_renewal_order' );
		remove_all_filters( 'woocommerce_email_classes' );
		remove_all_filters( 'wcs_get_retry_rule_raw' );
		unset( $_POST['wcpay-setup-intent'] );
		unset( $_POST['wcpay-payment-method'] );
		unset( $_POST['wcpay-is-platform-payment-method'] );
		unset( $_POST['wcpay-express-payment-method-types'] );
		unset( $_POST['wcpay-express-checkout-context'] );
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	/**
	 * @testdox Should preserve the WooPayments gateway identity and settings option key.
	 */
	public function test_preserves_gateway_identity(): void {
		$this->with_gateway_settings(
			array( 'saved_cards' => 'no' ),
			function (): void {
				$gateway = new NativeWooPaymentsGateway();

				$this->assertSame( OrderPaymentStore::GATEWAY_ID, $gateway->id );
				$this->assertSame( 'woocommerce_woocommerce_payments_settings', $gateway->get_option_key() );
				$this->assertSame( 'Card', $gateway->title );
				$this->assertStringContainsString( '/assets/images/payment-methods/visa.svg', $gateway->get_icon() );
				$this->assertStringContainsString( 'alt="Visa"', $gateway->get_icon() );
				$this->assertStringContainsString( '/assets/images/payment-methods/mastercard.svg', $gateway->get_icon() );
				$this->assertStringContainsString( '+ 3', $gateway->get_icon() );
				$this->assertContains( 'products', $gateway->supports );
				$this->assertContains( 'refunds', $gateway->supports );
				$this->assertNotContains( PaymentGatewayFeature::TOKENIZATION, $gateway->supports );
				$this->assertNotContains( PaymentGatewayFeature::ADD_PAYMENT_METHOD, $gateway->supports );
				$this->assertNotContains( 'subscriptions', $gateway->supports );
			}
		);
	}

	/**
	 * @testdox Should show the checkout test-mode badge when test mode is enabled.
	 */
	public function test_gateway_icon_shows_test_mode_badge_when_test_mode_is_enabled(): void {
		$this->with_gateway_settings(
			array( 'test_mode' => 'yes' ),
			function (): void {
				$gateway = new NativeWooPaymentsGateway();

				$this->assertStringContainsString( '/assets/images/payment-methods/visa.svg', $gateway->get_icon() );
				$this->assertStringContainsString( 'test-mode badge', $gateway->get_icon() );
				$this->assertStringContainsString( 'background-color:#fff2d7', $gateway->get_icon() );
				$this->assertStringContainsString( 'Test Mode', $gateway->get_icon() );
			}
		);
	}

	/**
	 * @testdox Should hide the checkout test-mode badge when test mode is disabled.
	 */
	public function test_gateway_icon_hides_test_mode_badge_when_test_mode_is_disabled(): void {
		$this->with_gateway_settings(
			array( 'test_mode' => 'no' ),
			function (): void {
				$gateway = new NativeWooPaymentsGateway();

				$this->assertStringContainsString( '/assets/images/payment-methods/visa.svg', $gateway->get_icon() );
				$this->assertStringNotContainsString( 'test-mode badge', $gateway->get_icon() );
				$this->assertStringNotContainsString( 'Test Mode', $gateway->get_icon() );
			}
		);
	}

	/**
	 * @testdox Should show Cartes Bancaires in the checkout gateway icon for France merchants.
	 */
	public function test_gateway_icon_includes_cartes_bancaires_for_france_merchants(): void {
		$account_service = $this->getMockBuilder( WooPaymentsAccountService::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'is_test_mode_enabled', 'get_cached_account_data' ) )
			->getMock();
		$account_service->method( 'is_test_mode_enabled' )->willReturn( false );
		$account_service->method( 'get_cached_account_data' )->willReturn( array( 'country' => 'FR' ) );

		$gateway = new NativeWooPaymentsGateway();
		$gateway->init( new RecordingPaymentProcessingService(), new WooPaymentsProvider(), null, null, $account_service );

		$icon = $gateway->get_icon();

		$this->assertStringContainsString( '<span class="payment-methods--logos-count">+ 4</span>', $icon );
	}

	/**
	 * @testdox Should expose saved-card support only when saved cards are enabled.
	 */
	public function test_saved_card_support_tracks_saved_cards_setting(): void {
		$this->with_gateway_settings(
			array( 'saved_cards' => 'yes' ),
			function (): void {
				$gateway = new NativeWooPaymentsGateway();

				$this->assertContains( PaymentGatewayFeature::TOKENIZATION, $gateway->supports );
				$this->assertContains( PaymentGatewayFeature::ADD_PAYMENT_METHOD, $gateway->supports );
			}
		);

		$this->with_gateway_settings(
			array( 'saved_cards' => 'no' ),
			function (): void {
				$gateway = new NativeWooPaymentsGateway();

				$this->assertNotContains( PaymentGatewayFeature::TOKENIZATION, $gateway->supports );
				$this->assertNotContains( PaymentGatewayFeature::ADD_PAYMENT_METHOD, $gateway->supports );
			}
		);
	}

	/**
	 * @testdox Should expose WooPayments subscription support when subscriptions are enabled.
	 */
	public function test_subscription_support_tracks_subscriptions_availability(): void {
		$this->with_gateway_settings(
			array( 'saved_cards' => 'yes' ),
			function (): void {
				$gateway = new class() extends NativeWooPaymentsGateway {
					/**
					 * Tell whether subscriptions support is available.
					 *
					 * @return bool
					 */
					public function is_subscriptions_enabled(): bool {
						return true;
					}

				};

				$this->assertContains( 'multiple_subscriptions', $gateway->supports );
				$this->assertContains( 'subscription_cancellation', $gateway->supports );
				$this->assertContains( 'subscription_payment_method_change_admin', $gateway->supports );
				$this->assertContains( 'subscription_payment_method_change_customer', $gateway->supports );
				$this->assertContains( 'subscription_payment_method_change', $gateway->supports );
				$this->assertContains( 'subscription_reactivation', $gateway->supports );
				$this->assertContains( 'subscription_suspension', $gateway->supports );
				$this->assertContains( 'subscriptions', $gateway->supports );
				$this->assertContains( 'subscription_amount_changes', $gateway->supports );
				$this->assertContains( 'subscription_date_changes', $gateway->supports );
				$this->assertContains( PaymentGatewayFeature::TOKENIZATION, $gateway->supports );
				$this->assertContains( PaymentGatewayFeature::ADD_PAYMENT_METHOD, $gateway->supports );
				$this->assertNotContains( 'gateway_scheduled_payments', $gateway->supports );
			}
		);
	}

	/**
	 * @testdox Should ignore deprecated Stripe Billing flags for native subscription support.
	 */
	public function test_subscription_support_ignores_deprecated_stripe_billing_mode(): void {
		update_option( '_wcpay_feature_subscriptions', '1' );
		update_option( '_wcpay_feature_stripe_billing', '1' );

		try {
			$this->with_gateway_settings(
				array( 'saved_cards' => 'yes' ),
				function (): void {
					$gateway = new class() extends NativeWooPaymentsGateway {
						/**
						 * Tell whether subscriptions support is available.
						 *
						 * @return bool
						 */
						public function is_subscriptions_enabled(): bool {
							return true;
						}
					};

					$this->assertNotContains( 'gateway_scheduled_payments', $gateway->supports );
					$this->assertContains( 'subscription_amount_changes', $gateway->supports );
					$this->assertContains( 'subscription_date_changes', $gateway->supports );
				}
			);
		} finally {
			delete_option( '_wcpay_feature_subscriptions' );
			delete_option( '_wcpay_feature_stripe_billing' );
		}
	}

	/**
	 * @testdox Should register subscription renewal handlers when subscriptions are supported.
	 */
	public function test_subscription_support_registers_subscription_handlers(): void {
		$gateway = new class() extends NativeWooPaymentsGateway {
			/**
			 * Tell whether subscriptions support is available.
			 *
			 * @return bool
			 */
			public function is_subscriptions_enabled(): bool {
				return true;
			}
		};

		$this->assertSame( 10, has_action( 'woocommerce_scheduled_subscription_payment_' . OrderPaymentStore::GATEWAY_ID, array( $gateway, 'scheduled_subscription_payment' ) ) );
		$this->assertSame( 10, has_action( 'woocommerce_subscription_failing_payment_method_updated_' . OrderPaymentStore::GATEWAY_ID, array( $gateway, 'update_failing_payment_method' ) ) );

		remove_action( 'woocommerce_scheduled_subscription_payment_' . OrderPaymentStore::GATEWAY_ID, array( $gateway, 'scheduled_subscription_payment' ) );
		remove_action( 'woocommerce_subscription_failing_payment_method_updated_' . OrderPaymentStore::GATEWAY_ID, array( $gateway, 'update_failing_payment_method' ) );
	}

	/**
	 * @testdox Should register WooPayments failed-renewal authentication emails when subscriptions are supported.
	 */
	public function test_subscription_support_registers_failed_renewal_authentication_emails(): void {
		new class() extends NativeWooPaymentsGateway {
			/**
			 * Tell whether subscriptions support is available.
			 *
			 * @return bool
			 */
			public function is_subscriptions_enabled(): bool {
				return true;
			}
		};

		/**
		 * Filters the registered WooCommerce email classes.
		 *
		 * @since 2.1.0
		 *
		 * @param array $emails Email classes.
		 */
		$emails = apply_filters( 'woocommerce_email_classes', array() );

		$this->assertArrayHasKey( 'WC_Payments_Email_Failed_Renewal_Authentication', $emails );
		$this->assertSame( 'failed_renewal_authentication', $emails['WC_Payments_Email_Failed_Renewal_Authentication']->id );
		$this->assertSame( 'failed-renewal-authentication.php', $emails['WC_Payments_Email_Failed_Renewal_Authentication']->template_html );
		$this->assertSame( 'plain/failed-renewal-authentication.php', $emails['WC_Payments_Email_Failed_Renewal_Authentication']->template_plain );
		$this->assertSame( 10, has_action( 'woocommerce_woocommerce_payments_payment_requires_action', array( $emails['WC_Payments_Email_Failed_Renewal_Authentication'], 'trigger' ) ) );
		$this->assertArrayHasKey( 'WC_Payments_Email_Failed_Authentication_Retry', $emails );
		$this->assertSame( 'failed_authentication_requested', $emails['WC_Payments_Email_Failed_Authentication_Retry']->id );
		$this->assertSame( 'failed-renewal-authentication-requested.php', $emails['WC_Payments_Email_Failed_Authentication_Retry']->template_html );
		$this->assertSame( 'plain/failed-renewal-authentication-requested.php', $emails['WC_Payments_Email_Failed_Authentication_Retry']->template_plain );
	}

	/**
	 * @testdox Should register WooPayments failed-renewal authentication emails only once across gateway instances.
	 */
	public function test_subscription_email_registration_is_idempotent_across_gateway_instances(): void {
		new class() extends NativeWooPaymentsGateway {
			/**
			 * Tell whether subscriptions support is available.
			 *
			 * @return bool
			 */
			public function is_subscriptions_enabled(): bool {
				return true;
			}
		};
		new class() extends NativeWooPaymentsGateway {
			/**
			 * Tell whether subscriptions support is available.
			 *
			 * @return bool
			 */
			public function is_subscriptions_enabled(): bool {
				return true;
			}
		};

		global $wp_filter;
		$callbacks = $wp_filter['woocommerce_email_classes']->callbacks[20] ?? array();
		$matches   = array_filter(
			$callbacks,
			static function ( array $callback ): bool {
				return is_array( $callback['function'] ?? null )
					&& NativeWooPaymentsGateway::class === ( $callback['function'][0] ?? null )
					&& 'add_subscription_emails' === ( $callback['function'][1] ?? null );
			}
		);

		$this->assertCount( 1, $matches );
	}

	/**
	 * @testdox Should update retry rules for failed renewals that need authentication.
	 */
	public function test_failed_renewal_authentication_email_updates_retry_rules(): void {
		new class() extends NativeWooPaymentsGateway {
			/**
			 * Tell whether subscriptions support is available.
			 *
			 * @return bool
			 */
			public function is_subscriptions_enabled(): bool {
				return true;
			}
		};

		/**
		 * Filters the registered WooCommerce email classes.
		 *
		 * @since 2.1.0
		 *
		 * @param array $emails Email classes.
		 */
		$emails = apply_filters( 'woocommerce_email_classes', array() );
		$order  = $this->create_order();
		$email  = $emails['WC_Payments_Email_Failed_Renewal_Authentication'];

		$email->object = $order;

		$customer_rule = $email->prevent_retry_notification_email(
			array(
				'email_template_customer' => 'WCS_Email_Customer_Renewal_Invoice',
				'email_template_admin'    => 'WCS_Email_Payment_Retry',
			),
			1,
			$order->get_id()
		);
		$admin_rule    = $email->set_store_owner_custom_email(
			array(
				'email_template_customer' => 'WCS_Email_Customer_Renewal_Invoice',
				'email_template_admin'    => 'WCS_Email_Payment_Retry',
			),
			1,
			$order->get_id()
		);

		$this->assertSame( '', $customer_rule['email_template_customer'] );
		$this->assertSame( 'WC_Payments_Email_Failed_Authentication_Retry', $admin_rule['email_template_admin'] );
	}

	/**
	 * @testdox Should process scheduled subscription payments with the saved renewal token.
	 */
	public function test_scheduled_subscription_payment_uses_saved_renewal_token(): void {
		$user_id = self::factory()->user->create();
		$order   = $this->create_order();
		$order->set_customer_id( $user_id );
		$order->add_payment_token( $this->create_card_token( $user_id, 'pm_renewal' ) );
		$order->save();

		$service = new RecordingPaymentProcessingService();
		$gateway = new NativeWooPaymentsGateway();
		$gateway->init( $service, new WooPaymentsProvider() );

		$gateway->scheduled_subscription_payment( 12.0, wc_get_order( $order->get_id() ) );

		$this->assertInstanceOf( PaymentContext::class, $service->last_checkout_context );
		$this->assertSame( $order->get_id(), $service->last_checkout_context->get_order_id() );
		$this->assertSame(
			array(
				'payment_token'       => (string) $order->get_payment_tokens()[0],
				'save_payment_method' => false,
			),
			$service->last_checkout_context->get_payment_data()
		);
		$this->assertSame( array( 'scheduled_subscription_payment' => true ), $service->last_checkout_context->get_provider_data() );
	}

	/**
	 * @testdox Should keep tokenized WC Subscriptions renewals independent of deprecated Stripe Billing flags.
	 */
	public function test_scheduled_subscription_payment_uses_tokenized_renewal_when_deprecated_stripe_billing_flags_remain(): void {
		update_option( '_wcpay_feature_subscriptions', '1' );
		update_option( '_wcpay_feature_stripe_billing', '1' );

		try {
			$user_id = self::factory()->user->create();
			$order   = $this->create_order();
			$order->set_customer_id( $user_id );
			$order->add_payment_token( $this->create_card_token( $user_id, 'pm_tokenized_renewal' ) );
			$order->save();

			$service = new RecordingPaymentProcessingService();
			$gateway = new NativeWooPaymentsGateway();
			$gateway->init( $service, new WooPaymentsProvider() );

			$gateway->scheduled_subscription_payment( 12.0, wc_get_order( $order->get_id() ) );

			$this->assertInstanceOf( PaymentContext::class, $service->last_checkout_context );
			$this->assertSame(
				array(
					'payment_token'       => (string) $order->get_payment_tokens()[0],
					'save_payment_method' => false,
				),
				$service->last_checkout_context->get_payment_data()
			);
			$this->assertSame( array( 'scheduled_subscription_payment' => true ), $service->last_checkout_context->get_provider_data() );
		} finally {
			delete_option( '_wcpay_feature_subscriptions' );
			delete_option( '_wcpay_feature_stripe_billing' );
		}
	}

	/**
	 * @testdox Should use the current subscription customer when processing a scheduled renewal.
	 */
	public function test_scheduled_subscription_payment_uses_current_subscription_customer(): void {
		$user_id      = self::factory()->user->create();
		$parent_order = $this->create_order();
		$subscription = $this->create_order();
		$renewal      = $this->create_order();

		$parent_order->update_meta_data( '_stripe_customer_id', 'cus_parent_stale' );
		$parent_order->update_meta_data( '_stripe_mandate_id', 'mandate_parent' );
		$parent_order->save();
		$subscription->set_parent_id( $parent_order->get_id() );
		$subscription->update_meta_data( '_stripe_customer_id', 'cus_subscription_current' );
		$subscription->save();
		$renewal->set_customer_id( $user_id );
		$renewal->add_payment_token( $this->create_card_token( $user_id, 'pm_renewal' ) );
		$renewal->save();

		add_filter(
			'woocommerce_native_woopayments_subscriptions_for_renewal_order',
			static function ( array $subscriptions, WC_Order $filtered_order ) use ( $renewal, $subscription ): array {
				return $renewal->get_id() === $filtered_order->get_id() ? array( $subscription ) : $subscriptions;
			},
			10,
			2
		);

		$service = new RecordingPaymentProcessingService();
		$gateway = new NativeWooPaymentsGateway();
		$gateway->init( $service, new WooPaymentsProvider() );

		$gateway->scheduled_subscription_payment( 12.0, wc_get_order( $renewal->get_id() ) );

		$renewal = wc_get_order( $renewal->get_id() );

		$this->assertInstanceOf( WC_Order::class, $renewal );
		$this->assertSame( 'cus_subscription_current', $renewal->get_meta( '_stripe_customer_id', true ) );
		$this->assertSame(
			array(
				'scheduled_subscription_payment' => true,
				'renewal_mandate'                => 'mandate_parent',
			),
			$service->last_checkout_context->get_provider_data()
		);
	}

	/**
	 * @testdox Should fail scheduled renewals and fire the preserved action when customer authentication is required.
	 */
	public function test_scheduled_subscription_payment_fails_and_fires_requires_action_hook(): void {
		$user_id = self::factory()->user->create();
		$order   = $this->create_order();
		$order->set_customer_id( $user_id );
		$order->set_currency( 'USD' );
		$order->add_payment_token( $this->create_card_token( $user_id, 'pm_requires_action' ) );
		$order->save();

		$service  = new class( new PaymentOutcome(
			PaymentOutcome::STATUS_REQUIRES_CUSTOMER_ACTION,
			'pi_requires_action',
			'#wcpay-confirm-pi:' . $order->get_id() . ':secret:nonce',
			'pm_requires_action',
			'cus_requires_action',
			array(
				'charge_id' => 'ch_requires_action',
				'meta'      => array(
					'_charge_id' => 'ch_legacy_meta',
				),
			)
		) ) extends RecordingPaymentProcessingService {
			/**
			 * Outcome returned by process_checkout_outcome.
			 *
			 * @var PaymentOutcome
			 */
			private PaymentOutcome $outcome;

			/**
			 * Constructor.
			 *
			 * @param PaymentOutcome $outcome Outcome returned by process_checkout_outcome.
			 */
			public function __construct( PaymentOutcome $outcome ) {
				$this->outcome = $outcome;
			}

			/**
			 * Process checkout payment and return the neutral outcome.
			 *
			 * @param PaymentContext   $context  Payment context.
			 * @param ProviderContract $provider Provider.
			 * @return PaymentOutcome
			 */
			public function process_checkout_outcome( PaymentContext $context, ProviderContract $provider ): PaymentOutcome {
				$this->last_checkout_context = $context;

				return $this->outcome;
			}
		};
		$received = array();
		add_action(
			'woocommerce_woocommerce_payments_payment_requires_action',
			static function ( WC_Order $hook_order, string $intent_id, string $payment_method_id, string $customer_id, string $charge_id, string $currency ) use ( &$received ): void {
				$received = array(
					'hook_order'        => $hook_order,
					'intent_id'         => $intent_id,
					'payment_method_id' => $payment_method_id,
					'customer_id'       => $customer_id,
					'charge_id'         => $charge_id,
					'currency'          => $currency,
				);
			},
			10,
			6
		);

		$gateway = new NativeWooPaymentsGateway();
		$gateway->init( $service, new WooPaymentsProvider() );

		$gateway->scheduled_subscription_payment( 12.0, wc_get_order( $order->get_id() ) );
		$order = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertSame( 'failed', $order->get_status() );
		$this->assertSame( $order->get_id(), $received['hook_order']->get_id() );
		$this->assertSame( 'pi_requires_action', $received['intent_id'] );
		$this->assertSame( 'pm_requires_action', $received['payment_method_id'] );
		$this->assertSame( 'cus_requires_action', $received['customer_id'] );
		$this->assertSame( 'ch_requires_action', $received['charge_id'] );
		$this->assertSame( 'USD', $received['currency'] );
	}

	/**
	 * @testdox Should still fail scheduled renewals when a customer-action hook callback throws.
	 */
	public function test_scheduled_subscription_payment_fails_when_requires_action_hook_throws(): void {
		$user_id = self::factory()->user->create();
		$order   = $this->create_order();
		$order->set_customer_id( $user_id );
		$order->set_currency( 'USD' );
		$order->add_payment_token( $this->create_card_token( $user_id, 'pm_requires_action' ) );
		$order->save();

		$service                   = new RecordingPaymentProcessingService();
		$service->checkout_outcome = new PaymentOutcome(
			PaymentOutcome::STATUS_REQUIRES_CUSTOMER_ACTION,
			'pi_requires_action',
			'#wcpay-confirm-pi:' . $order->get_id() . ':secret:nonce',
			'pm_requires_action',
			'cus_requires_action',
			array(
				'meta' => array(
					'_charge_id' => 'ch_requires_action',
				),
			)
		);

		add_action(
			'woocommerce_woocommerce_payments_payment_requires_action',
			static function (): void {
				throw new \RuntimeException( 'email callback failed' );
			}
		);

		$gateway = new NativeWooPaymentsGateway();
		$gateway->init( $service, new WooPaymentsProvider() );

		try {
			$gateway->scheduled_subscription_payment( 12.0, wc_get_order( $order->get_id() ) );
		} catch ( \RuntimeException $exception ) {
			$this->fail( 'Requires-action hook exceptions should not prevent renewal failure handling.' );
		}

		$order = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertSame( 'failed', $order->get_status() );
	}

	/**
	 * @testdox Should copy the successful renewal token onto a failing subscription.
	 */
	public function test_update_failing_payment_method_copies_renewal_token(): void {
		$user_id      = self::factory()->user->create();
		$subscription = $this->create_order();
		$renewal      = $this->create_order();
		$token        = $this->create_card_token( $user_id, 'pm_recovered' );

		$subscription->set_customer_id( $user_id );
		$subscription->save();
		$renewal->set_customer_id( $user_id );
		$renewal->add_payment_token( $token );
		$renewal->save();

		$gateway = new NativeWooPaymentsGateway();

		$gateway->update_failing_payment_method( wc_get_order( $subscription->get_id() ), wc_get_order( $renewal->get_id() ) );

		$subscription = wc_get_order( $subscription->get_id() );
		$this->assertContains( $token->get_id(), array_map( 'absint', $subscription->get_payment_tokens() ) );
	}

	/**
	 * @testdox Should save setup-intent payment methods from the account add-payment-method form.
	 */
	public function test_add_payment_method_saves_successful_setup_intent_token(): void {
		$user_id = self::factory()->user->create();
		wp_set_current_user( $user_id );
		$_POST['wcpay-setup-intent'] = 'seti_native';

		$api_client = $this->getMockBuilder( WooPaymentsApiClient::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_setup_intention' ) )
			->getMock();
		$api_client
			->expects( $this->once() )
			->method( 'get_setup_intention' )
			->with( 'seti_native' )
			->willReturn(
				array(
					'id'             => 'seti_native',
					'status'         => 'succeeded',
					'payment_method' => 'pm_added',
				)
			);

		$token_service = $this->getMockBuilder( WooPaymentsTokenService::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_or_create_card_token_for_user' ) )
			->getMock();
		$token_service
			->expects( $this->once() )
			->method( 'get_or_create_card_token_for_user' )
			->with( 'pm_added', $user_id )
			->willReturn( $this->create_card_token( $user_id, 'pm_added' ) );

		$gateway = new NativeWooPaymentsGateway();
		$gateway->init( new RecordingPaymentProcessingService(), new WooPaymentsProvider(), null, $api_client, null, $token_service );

		$result = $gateway->add_payment_method();

		$this->assertSame( 'success', $result['result'] );
		$this->assertSame( wc_get_endpoint_url( 'payment-methods' ), $result['redirect'] );
	}

	/**
	 * @testdox Should not translate gateway labels during construction before init.
	 */
	public function test_constructor_does_not_translate_gateway_labels_before_init(): void {
		global $wp_actions;

		$had_init_action_count = is_array( $wp_actions ) && array_key_exists( 'init', $wp_actions );
		$previous_init_count   = $had_init_action_count ? $wp_actions['init'] : null;
		$translated            = array();
		$filter                = static function ( $translation, $text, $domain ) use ( &$translated ) {
			if ( 'woocommerce' === $domain ) {
				$translated[] = $text;
			}

			return $translation;
		};

		unset( $wp_actions['init'] );
		add_filter( 'gettext', $filter, 10, 3 );

		try {
			new NativeWooPaymentsGateway();
		} finally {
			remove_filter( 'gettext', $filter, 10 );

			if ( $had_init_action_count ) {
				// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Simulate pre-init construction for the WP 6.7 textdomain guard.
				$wp_actions['init'] = $previous_init_count;
			} else {
				unset( $wp_actions['init'] );
			}
		}

		$this->assertSame( array(), $translated, 'Native gateway construction must not call translation APIs before init.' );
	}

	/**
	 * @testdox Should translate gateway labels from the init hook.
	 */
	public function test_translates_gateway_labels_from_init_hook(): void {
		global $wp_actions;

		$had_init_action_count = is_array( $wp_actions ) && array_key_exists( 'init', $wp_actions );
		$previous_init_count   = $had_init_action_count ? $wp_actions['init'] : null;
		$filter                = static function ( $translation, $text, $domain ) {
			return 'woocommerce' === $domain ? 'Translated: ' . $text : $translation;
		};

		unset( $wp_actions['init'] );
		add_filter( 'gettext', $filter, 10, 3 );

		try {
			$gateway = new NativeWooPaymentsGateway();

			$this->assertSame( 'Card', $gateway->title );
			$this->assertSame( 'WooPayments', $gateway->method_title );
			$this->assertSame( 'Accept payments with WooPayments.', $gateway->method_description );

			$gateway->handle_init();

			$this->assertSame( 'Translated: Card', $gateway->title );
			$this->assertSame( 'Translated: WooPayments', $gateway->method_title );
			$this->assertSame( 'Translated: Accept payments with WooPayments.', $gateway->method_description );
		} finally {
			remove_filter( 'gettext', $filter, 10 );

			if ( $had_init_action_count ) {
				// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Simulate pre-init construction for the WP 6.7 textdomain guard.
				$wp_actions['init'] = $previous_init_count;
			} else {
				unset( $wp_actions['init'] );
			}
		}
	}

	/**
	 * @testdox Should process payments through the native processing service.
	 */
	public function test_process_payment_delegates_to_processing_service(): void {
		$order   = $this->create_order();
		$service = new RecordingPaymentProcessingService();
		$gateway = new NativeWooPaymentsGateway();
		$gateway->init( $service, new WooPaymentsProvider() );

		$result = $gateway->process_payment( $order->get_id() );

		$this->assertSame( 'success', $result['result'] );
		$this->assertInstanceOf( PaymentContext::class, $service->last_checkout_context );
		$this->assertSame( $order->get_id(), $service->last_checkout_context->get_order_id() );
		$this->assertSame( OrderPaymentStore::GATEWAY_ID, $service->last_checkout_context->get_gateway_id() );
		$this->assertFalse( $service->last_checkout_context->get_provider_data()['is_platform_payment_method'] );
	}

	/**
	 * @testdox Should pass platform-created payment method state through provider data.
	 */
	public function test_process_payment_passes_platform_payment_method_state_to_provider_data(): void {
		$order   = $this->create_order();
		$service = new RecordingPaymentProcessingService();
		$gateway = new NativeWooPaymentsGateway();
		$gateway->init( $service, new WooPaymentsProvider() );

		$_POST['wcpay-payment-method']             = 'pm_platform';
		$_POST['wcpay-is-platform-payment-method'] = 'true';

		$gateway->process_payment( $order->get_id() );

		$this->assertInstanceOf( PaymentContext::class, $service->last_checkout_context );
		$this->assertSame( 'pm_platform', $service->last_checkout_context->get_payment_method_id() );
		$this->assertTrue( $service->last_checkout_context->get_provider_data()['is_platform_payment_method'] );
	}

	/**
	 * @testdox Should pass submitted express checkout payment method types through provider data.
	 */
	public function test_process_payment_passes_express_payment_method_types_to_provider_data(): void {
		$order   = $this->create_order();
		$service = new RecordingPaymentProcessingService();
		$gateway = new NativeWooPaymentsGateway();
		$gateway->init( $service, new WooPaymentsProvider() );

		$_POST['wcpay-confirmation-token']           = 'ctoken_express';
		$_POST['wcpay-express-payment-method-types'] = wp_json_encode( array( 'card', 'amazon_pay', 'unknown_method', array( 'nested' ) ) );
		$_POST['wcpay-express-checkout-context']     = 'pay_for_order';
		$_POST['wcpay-is-platform-payment-method']   = 'true';

		$gateway->process_payment( $order->get_id() );

		$this->assertInstanceOf( PaymentContext::class, $service->last_checkout_context );
		$this->assertSame( 'ctoken_express', $service->last_checkout_context->get_payment_method_id() );
		$this->assertSame( array( 'card', 'amazon_pay' ), $service->last_checkout_context->get_provider_data()[ WooPaymentsExpressPaymentMethodTypes::PROVIDER_DATA_KEY ] );
		$this->assertSame( 'pay_for_order', $service->last_checkout_context->get_provider_data()[ WooPaymentsExpressPaymentMethodTypes::PROVIDER_CONTEXT_KEY ] );
		$this->assertTrue( $service->last_checkout_context->get_provider_data()['is_platform_payment_method'] );
	}

	/**
	 * @testdox Should process refunds through the native processing service.
	 */
	public function test_process_refund_delegates_to_processing_service(): void {
		$order = $this->create_order();
		$order->update_meta_data( '_charge_id', 'ch_test' );
		$order->save();

		$service = new RecordingPaymentProcessingService();
		$gateway = new NativeWooPaymentsGateway();
		$gateway->init( $service, new WooPaymentsProvider() );

		$result = $gateway->process_refund( $order->get_id(), 4.25, 'Adjustment' );

		$this->assertTrue( $result );
		$this->assertInstanceOf( PaymentContext::class, $service->last_refund_context );
		$this->assertSame(
			array(
				'amount' => 4.25,
				'reason' => 'Adjustment',
			),
			$service->last_refund_context->get_payment_data()
		);
	}

	/**
	 * @testdox Should only allow refunds for orders with a WooPayments charge.
	 */
	public function test_can_refund_order_requires_charge_id(): void {
		$order   = $this->create_order();
		$gateway = new NativeWooPaymentsGateway();

		$this->assertFalse( $gateway->can_refund_order( $order ) );

		$order->update_meta_data( '_charge_id', 'ch_test' );
		$order->save();

		$this->assertTrue( $gateway->can_refund_order( wc_get_order( $order->get_id() ) ) );
	}

	/**
	 * @testdox Should fail refunds that do not have a WooPayments charge.
	 */
	public function test_process_refund_fails_without_charge_id(): void {
		$order   = $this->create_order();
		$service = new RecordingPaymentProcessingService();
		$gateway = new NativeWooPaymentsGateway();
		$gateway->init( $service, new WooPaymentsProvider() );

		$result = $gateway->process_refund( $order->get_id(), 4.25, 'Adjustment' );

		$this->assertWPError( $result );
		$this->assertSame( 'native_payment_refund_missing_charge', $result->get_error_code() );
		$this->assertNull( $service->last_refund_context );
	}

	/**
	 * @testdox Should resolve native dependencies when WooCommerce instantiates the gateway directly.
	 */
	public function test_process_payment_resolves_dependencies_without_explicit_init(): void {
		$order   = $this->create_order();
		$gateway = new NativeWooPaymentsGateway();

		$result = $gateway->process_payment( $order->get_id() );

		$this->assertSame(
			array(
				'result'         => 'fail',
				'redirect'       => '',
				'payment_method' => '',
			),
			$result
		);
	}

	/**
	 * @testdox Should delegate payment fields rendering to the checkout bridge.
	 */
	public function test_payment_fields_delegate_to_checkout_bridge(): void {
		add_filter( 'woocommerce_is_checkout', '__return_true' );

		$service = new RecordingPaymentProcessingService();
		$bridge  = $this->getMockBuilder( WooPaymentsCheckoutBridge::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'render_payment_fields' ) )
			->getMock();
		$bridge
			->expects( $this->once() )
			->method( 'render_payment_fields' )
			->willReturnCallback(
				static function (): void {
					echo '<div id="wcpay-bridge-marker"></div>';
				}
			);

		$output = '';
		try {
			$this->with_gateway_settings(
				array( 'saved_cards' => 'yes' ),
				function () use ( $service, $bridge, &$output ): void {
					$gateway = new NativeWooPaymentsGateway();
					$gateway->init( $service, new WooPaymentsProvider(), $bridge );

					ob_start();
					$gateway->payment_fields();
					$output = (string) ob_get_clean();
				}
			);
		} finally {
			remove_filter( 'woocommerce_is_checkout', '__return_true' );
		}

		$this->assertStringContainsString( 'wcpay-bridge-marker', $output );
		$this->assertStringContainsString( 'wc-woocommerce_payments-new-payment-method', $output );
		$this->assertStringContainsString( 'wc-woocommerce_payments-payment-token-new', $output );
	}

	/**
	 * @testdox Should resolve checkout bridge dependencies when payment fields are rendered directly.
	 */
	public function test_payment_fields_resolve_dependencies_without_explicit_init(): void {
		$gateway = new NativeWooPaymentsGateway();

		ob_start();
		$gateway->payment_fields();
		$output = (string) ob_get_clean();

		$this->assertStringContainsString( 'wcpay-core-checkout-form', $output );
	}

	/**
	 * @testdox Should expose recommended payment methods for the settings provider list.
	 */
	public function test_get_recommended_payment_methods_delegates_to_native_api_client(): void {
		delete_transient( 'woocommerce_woocommerce_payments_recommended_payment_methods' );

		$api_client = $this->getMockBuilder( WooPaymentsApiClient::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_recommended_payment_methods' ) )
			->getMock();
		$api_client
			->expects( $this->once() )
			->method( 'get_recommended_payment_methods' )
			->with( 'GB', 'en_US' )
			->willReturn(
				array(
					array(
						'id'    => 'card',
						'title' => 'Cards',
					),
					array(
						'id'       => 'link',
						'title'    => 'Link',
						'type'     => 'available',
						'priority' => '7',
					),
					array(
						'title' => 'Invalid recommendation',
					),
				)
			);

		$gateway = new NativeWooPaymentsGateway();
		$gateway->init( new RecordingPaymentProcessingService(), new WooPaymentsProvider(), null, $api_client );

		$result = $gateway->get_recommended_payment_methods( 'GB' );

		$this->assertCount( 2, $result );
		$this->assertSame( 'card', $result[0]['id'] );
		$this->assertTrue( $result[0]['enabled'] );
		$this->assertSame( 0, $result[0]['priority'] );
		$this->assertSame( 'link', $result[1]['id'] );
		$this->assertFalse( $result[1]['enabled'] );
		$this->assertSame( 7, $result[1]['priority'] );

		delete_transient( 'woocommerce_woocommerce_payments_recommended_payment_methods' );
	}

	/**
	 * @testdox Should cache recommended payment methods by country and locale.
	 */
	public function test_get_recommended_payment_methods_uses_cached_country_locale_data(): void {
		delete_transient( 'woocommerce_woocommerce_payments_recommended_payment_methods' );

		$api_client = $this->getMockBuilder( WooPaymentsApiClient::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_recommended_payment_methods' ) )
			->getMock();
		$api_client
			->expects( $this->once() )
			->method( 'get_recommended_payment_methods' )
			->with( 'GB', 'en_US' )
			->willReturn(
				array(
					array(
						'id'    => 'card',
						'title' => 'Cards',
					),
				)
			);

		$gateway = new NativeWooPaymentsGateway();
		$gateway->init( new RecordingPaymentProcessingService(), new WooPaymentsProvider(), null, $api_client );

		$first_result  = $gateway->get_recommended_payment_methods( 'GB' );
		$second_result = $gateway->get_recommended_payment_methods( 'GB' );

		$this->assertSame( $first_result, $second_result );
		$this->assertSame( 'card', $second_result[0]['id'] );

		delete_transient( 'woocommerce_woocommerce_payments_recommended_payment_methods' );
	}

	/**
	 * Create an order for gateway tests.
	 *
	 * @return WC_Order
	 */
	private function create_order(): WC_Order {
		$order = wc_create_order();
		$order->set_total( '12.00' );
		$order->save();

		return $order;
	}

	/**
	 * Create a saved WooPayments card token.
	 *
	 * @param int    $user_id           User ID.
	 * @param string $payment_method_id Provider payment method ID.
	 * @return WC_Payment_Token_CC
	 */
	private function create_card_token( int $user_id, string $payment_method_id ): WC_Payment_Token_CC {
		$token = new WC_Payment_Token_CC();
		$token->set_gateway_id( OrderPaymentStore::GATEWAY_ID );
		$token->set_user_id( $user_id );
		$token->set_token( $payment_method_id );
		$token->set_card_type( 'visa' );
		$token->set_last4( '4242' );
		$token->set_expiry_month( '12' );
		$token->set_expiry_year( '2030' );
		$token->save();

		return $token;
	}

	/**
	 * Run assertions with controlled WooPayments gateway settings.
	 *
	 * @param array<string,mixed> $settings Gateway settings.
	 * @param callable            $callback Assertion callback.
	 * @return void
	 */
	private function with_gateway_settings( array $settings, callable $callback ): void {
		$filter = static function () use ( $settings ) {
			return $settings;
		};

		add_filter( 'pre_option_woocommerce_woocommerce_payments_settings', $filter );

		try {
			$callback();
		} finally {
			remove_filter( 'pre_option_woocommerce_woocommerce_payments_settings', $filter );
		}
	}
}
