<?php
/**
 * Class Payment_Method
 *
 * The class represents a payment method.
 */
class Payment_Method {

    private $type;

    public function __construct( $type ) {
        if ( Payment_Method_Types::is_valid_type( $type ) ) {
            $this->type = $type;
        } else {
            throw new Exception( 'Invalid payment method type. Choose one from Payment_Method_Types class.' );
        }
    }

    public function get_type() {
        return $this->type;
    }
}