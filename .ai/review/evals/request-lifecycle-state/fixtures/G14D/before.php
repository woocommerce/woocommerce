<?php
/* Scenario context: Injected build_mailer initializes its memoized snapshot only when called. Extensions register email types at init priority 5; register_sender runs at priority 10. */
add_action( 'init', static function () use ( $build_mailer, $register_sender ) {
    $register_sender( $build_mailer() );
}, 10 );
