<?php
function wc_emit_export_done( $id ) {
    do_action( 'woocommerce_export_done', $id );
    $result = array( 'id' => $id );
    return $result;
}
