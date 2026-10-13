<?php
/* Scenario context: Released search endpoint returns plain-text names. Product storage contains HTML entities such as Tea &amp; Coffee. Consumers display response names as text and do not decode HTML. */
function prepare_item_for_response( $product ) {
    return rest_ensure_response( array( 'name' => html_entity_decode( $product['name'], ENT_QUOTES, 'UTF-8' ) ) );
}
