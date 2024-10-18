<?php
/**
 * Class Payment_Method_Types
 *
 * This class contains constants for payment method types.
 * If a payment method type is missing, add it.
 */
class Payment_Method_Types {

    const PAYPAL = 'paypal';
    const AUTHORIZE_NET = 'authorize_net';
    const APPLE_PAY = 'apple_pay';
    const GOOGLE_PAY = 'google_pay';
    const AFFIRM = 'affirm';
    const AFTERPAY = 'afterpay_clearpay';
    const KLARNA = 'klarna';
    const CREDIT_CARD = 'credit_card';
    const BANCONTACT = 'bancontact';
    const IDEAL = 'ideal';

    private static $express_checkout_methods = [
        self::APPLE_PAY,
        self::GOOGLE_PAY
    ];

    public static function is_valid_type( $type ) {
        return in_array( $type, self::get_types(), true );
    }

    public static function is_express_checkout( $type ) {
        return in_array($type, self::$express_checkout_methods);
    }

    private static function get_types() {
        return array_keys(self::$express_checkout_methods);
    }
}
