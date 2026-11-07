<?php
final class WC_Checkout_Label_Map {
    private $labels;
    private $memo = array();

    public function __construct( array $labels ) {
        $this->labels = array_map( 'strval', array_slice( $labels, 0, 10, true ) );
    }

    private function normalize( $label ) {
        return trim( preg_replace( '/\s+/u', ' ', html_entity_decode( $label, ENT_QUOTES, 'UTF-8' ) ) );
    }

    public function get( $id ) {
        if ( ! array_key_exists( $id, $this->labels ) ) { return ''; }
        if ( ! array_key_exists( $id, $this->memo ) ) {
            $this->memo[ $id ] = $this->normalize( $this->labels[ $id ] );
        }
        return $this->memo[ $id ];
    }
}
