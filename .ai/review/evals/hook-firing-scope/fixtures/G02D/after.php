<?php
/* Scenario context: Synthetic production fixture. WordPress add_action identifies the same object-method callback at the same priority as one registration. register runs once. */
class WC_Export_Notifier {
    public function register() {
        add_action( 'woocommerce_export_done', array( $this, 'notify' ), 10, 1 );
    }
    public function notify( $id ) { error_log( 'export ' . $id ); }
}
