<?php
/* Scenario context: Synthetic production fixture. Released action fires once synchronously with integer id. The local result array is neither global nor passed to callbacks. */
function wc_emit_export_done( $id ) {
    do_action( 'woocommerce_export_done', $id );
    $result = array( 'id' => $id );
    return $result;
}
