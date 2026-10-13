<?php
/* Scenario context: register_sender is injected by bootstrap and registers senders for each supplied type. init callbacks run in numeric priority order. build_mailer memoizes first type snapshot for the request; the shipment sender is required after initialization. */
$email_types = array();
$build_mailer = static function () use ( &$email_types ) {
    static $snapshot;
    if ( null === $snapshot ) { $snapshot = $email_types; }
    return $snapshot;
};
add_action( 'init', static function () use ( &$email_types ) {
    $email_types[] = 'shipment';
}, 5 );
add_action( 'init', static function () use ( $build_mailer, $register_sender ) {
    $register_sender( $build_mailer() );
}, 10 );
