<?php
/**
 * PayPal Gateway Constants.
 *
 * Provides constants for PayPal payment statuses, intents, and other PayPal-related values.
 *
 * @version     10.3.0
 * @package  WooCommerce\Gateways
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Gateway_Paypal_Constants Class.
 */
class WC_Gateway_Paypal_Constants {

	/**
	 * PayPal payment statuses.
	 */
	const STATUS_COMPLETED = 'COMPLETED';
	const STATUS_APPROVED  = 'APPROVED';
	const STATUS_CAPTURED  = 'CAPTURED';

	/**
	 * PayPal payment intents.
	 */
	const INTENT_CAPTURE   = 'CAPTURE';
	const INTENT_AUTHORIZE = 'AUTHORIZE';

    /**
     * PayPal payment actions.
     */
    const PAYMENT_ACTION_CAPTURE = 'capture';
    const PAYMENT_ACTION_AUTHORIZE = 'authorize';
}
