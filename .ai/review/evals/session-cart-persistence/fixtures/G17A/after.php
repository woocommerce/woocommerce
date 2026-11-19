<?php
/* Account logout handler. */
function on_customer_logout( $session, $user_id, $delete_user_meta ) {
    $session->destroy_session();
    $delete_user_meta( $user_id, '_woocommerce_persistent_cart_' . get_current_blog_id() );
}

function saved_account_cart( $user_id ) {
    $saved = get_user_meta( $user_id, '_woocommerce_persistent_cart_' . get_current_blog_id(), true );
    return is_array( $saved ) ? ( $saved['cart'] ?? array() ) : array();
}
