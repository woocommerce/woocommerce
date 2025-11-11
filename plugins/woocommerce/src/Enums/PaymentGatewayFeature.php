<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Enums;

/**
 * Enum class for all the payment gateway feature's values.
 */
final class PaymentGatewayFeature {
	/**
	 * Payment gateway supports add payment methods.
	 *
	 * @var string
	 */
	public const ADD_PAYMENT_METHOD = 'add_payment_method';

	/**
	 * Payment gateway supports credit card form on saved method.
	 *
	 * @var string
	 */
	public const CREDIT_CARD_FORM_CVC_ON_SAVED_METHOD = 'credit_card_form_cvc_on_saved_method';

	/**
	 * Payment gateway supports default credit card form.
	 *
	 * @var string
	 */
	public const DEFAULT_CREDIT_CARD_FORM = 'default_credit_card_form';

	/**
	 * Payment gateway supports deposits.
	 *
	 * @var string
	 */
	public const DEPOSITS = 'deposits';

	/**
	 * Payment gateway supports multiple subscriptions.
	 *
	 * @var string
	 */
	public const MULTIPLE_SUBSCRIPTIONS = 'multiple_subscriptions';

	/**
	 * Payment gateway supports pay button.
	 *
	 * @var string
	 */
	public const PAY_BUTTON = 'pay_button';

	/**
	 * Payment gateway supports pre-orders.
	 *
	 * @var string
	 */
	public const PRE_ORDERS = 'pre-orders';

	/**
	 * Payment gateway supports products.
	 *
	 * @var string
	 */
	public const PRODUCTS = 'products';

	/**
	 * Payment gateway supports refunds.
	 *
	 * @var string
	 */
	public const REFUNDS = 'refunds';

	/**
	 * Payment gateway supports subscription amount changes.
	 *
	 * @var string
	 */
	public const SUBSCRIPTION_AMOUNT_CHANGES = 'subscription_amount_changes';

	/**
	 * Payment gateway supports subscription cancellation.
	 *
	 * @var string
	 */
	public const SUBSCRIPTION_CANCELLATION = 'subscription_cancellation';

	/**
	 * Payment gateway supports subscription date changes.
	 *
	 * @var string
	 */
	public const SUBSCRIPTION_DATE_CHANGES = 'subscription_date_changes';

	/**
	 * Payment gateway supports subscription payment method changes.
	 *
	 * @var string
	 */
	public const SUBSCRIPTION_PAYMENT_METHOD_CHANGE = 'subscription_payment_method_change';

	/**
	 * Payment gateway supports subscription payment method changes by admin.
	 *
	 * @var string
	 */
	public const SUBSCRIPTION_PAYMENT_METHOD_CHANGE_ADMIN = 'subscription_payment_method_change_admin';

	/**
	 * Payment gateway supports subscription payment method changes by customer or admin.
	 *
	 * @var string
	 */
	public const SUBSCRIPTION_PAYMENT_METHOD_CHANGE_CUSTOMER = 'subscription_payment_method_change_customer';

	/**
	 * Payment gateway supports subscription reactivation.
	 *
	 * @var string
	 */
	public const SUBSCRIPTION_REACTIVATION = 'subscription_reactivation';

	/**
	 * Payment gateway supports subscription suspension.
	 *
	 * @var string
	 */
	public const SUBSCRIPTION_SUSPENSION = 'subscription_suspension';

	/**
	 * Payment gateway supports subscriptions.
	 *
	 * @var string
	 */
	public const SUBSCRIPTIONS = 'subscriptions';

	/**
	 * Payment gateway supports tokenization.
	 *
	 * @var string
	 */
	public const TOKENIZATION = 'tokenization';

	/**
	 * Agentic Commerce feature.
	 */
	public const AGENTIC_COMMERCE = 'agentic_commerce';
}
