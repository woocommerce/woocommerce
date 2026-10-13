<?php
// Extension bootstrap is loaded in web requests and Action Scheduler runner requests.
final class Order_Export_Queue {
    public function register() {
        add_action( 'action_scheduler_init', array( $this, 'register_producer' ) );
        add_action( 'woocommerce_export_order', array( $this, 'run' ) );
    }

    public function register_producer() {
        add_action( 'woocommerce_order_status_completed', array( $this, 'queue' ) );
    }

    public function queue( $order_id ) {
        return as_enqueue_async_action( 'woocommerce_export_order', array( (int) $order_id ), 'wc-export' );
    }

    public function run( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) { return; }
        update_post_meta( $order->get_id(), '_last_exported_status', $order->get_status() );
    }
}

( new Order_Export_Queue() )->register();

add_action( 'woocommerce_export_order', static function ( $order_id ) {
    update_option( 'last_export_observed_order', $order_id );
}, 20 );
