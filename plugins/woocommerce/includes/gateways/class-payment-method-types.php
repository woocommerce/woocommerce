<?php
/**
 * Class Payment_Method_Types
 *
 * This class contains constants for payment method types.
 * If payment method type is missing, add it.
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
    
    public static function is_valid_type( $type ) {
        return in_array( $type, self::get_types(), true );
    }

    private static  function get_types() {
        return [
            self::PAYPAL,
            self::AUTHORIZE_NET,
            self::APPLE_PAY,
            self::GOOGLE_PAY,
            self::AFFIRM,
            self::AFTERPAY,
            self::KLARNA,
            self::CREDIT_CARD,
            self::BANCONTACT,
            self::IDEAL
        ];
    }

}