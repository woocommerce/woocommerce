<?php
/* Scenario context: Released logout clears browser session but retains saved account cart for next login. This fixture uses _woocommerce_persistent_cart as its saved cart key; login restores that key. delete_user_meta permanently removes it. */
function on_customer_logout( $session, $user_id, $delete_user_meta ) {
    $session->destroy_session();
}
