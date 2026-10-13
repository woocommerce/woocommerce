<?php
/* Scenario context: Released endpoint has plain-text name. Input product always includes integer id and encoded name. Schema permits additive properties; clients ignore unknown keys. */
function prepare_item_for_response( $product ) {
    return rest_ensure_response( array( 'id' => $product['id'], 'name' => html_entity_decode( $product['name'], ENT_QUOTES, 'UTF-8' ) ) );
}
