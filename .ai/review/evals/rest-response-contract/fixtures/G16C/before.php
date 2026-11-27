<?php
/* Released search response. Product names use the Woo stored name representation. */
function prepare_item_for_response( $product ) {
    return rest_ensure_response( array( 'name' => html_entity_decode( $product['name'], ENT_QUOTES, 'UTF-8' ) ) );
}

function response_schema() {
    return array( 'properties' => array( 'name' => array( 'type' => 'string', 'description' => 'Product name as plain text.' ) ) );
}
function search_result_label( $response ) {
    return esc_html( $response['name'] );
}
