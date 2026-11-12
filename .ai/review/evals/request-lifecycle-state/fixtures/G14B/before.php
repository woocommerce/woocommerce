<?php
/* Request bootstrap. register_sender receives the mailer type list. */
$email_types = array();
$registered_email_types = array();
$register_sender = static function ( $types ) use ( &$registered_email_types ) {
    $registered_email_types = $types;
};
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
