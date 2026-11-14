<?php
/* Released item export response. Items contain integer id and string name. */
function prepare_item_for_response( $items ) {
    $item_data = array();
    foreach ( $items as $item ) { $item_data[] = $item['name']; }
    return rest_ensure_response( array( 'item_data' => $item_data ) );
}

function response_schema() {
    return array( 'properties' => array( 'item_data' => array( 'type' => 'object', 'additionalProperties' => array( 'type' => 'string' ) ) ) );
}
function exported_item_name( $response, $item_id ) {
    $items = (array) $response['item_data'];
    return $items[ (string) $item_id ] ?? '';
}
