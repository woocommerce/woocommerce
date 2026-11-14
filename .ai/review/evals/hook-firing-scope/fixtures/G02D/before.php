<?php
class WC_Export_Notifier {
    public function register() {
        add_action( 'woocommerce_export_done', array( $this, 'notify' ), 10, 1 );
        add_action( 'woocommerce_export_done', array( $this, 'notify' ), 10, 1 );
    }
    public function notify( $id ) { error_log( 'export ' . $id ); }
}
