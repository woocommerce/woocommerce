<?php
/* Scenario context: Released endpoint defines item_data as JSON object keyed by item ID, including {} when empty. Items contain positive integer id and string name. External clients read item_data["42"]. */
function prepare_item_for_response( $items ) {
    $item_data = new stdClass();
    foreach ( $items as $item ) { $item_data->{(string) $item['id']} = $item['name']; }
    return rest_ensure_response( array( 'item_data' => $item_data ) );
}
