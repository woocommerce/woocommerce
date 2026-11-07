<?php
$email_types = array();
$build_mailer = static function () use ( &$email_types ) {
    static $snapshot;
    if ( null === $snapshot ) { $snapshot = $email_types; }
    return $snapshot;
};
add_action( 'init', static function () use ( &$email_types ) {
    $email_types[] = 'shipment';
}, 5 );
/* Request bootstrap. register_sender receives the mailer type list. */
add_action( 'init', static function () use ( $build_mailer, $register_sender ) {
    $register_sender( $build_mailer() );
}, 10 );
