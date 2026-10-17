<?php
/* Route table consumed by the server bootstrap. wc/v3/items is released; the opt-in preview route is new in this change. items is an ID-keyed map. */
function item_response_routes( $features ) {
    $routes = array( '/wc/v3/items' => static function ( $items ) {
        return prepare_item_for_response( $items, false );
    } );
    if ( true === ( $features['unreleased_items_preview'] ?? false ) ) {
        $routes['/wc/experimental/items-preview'] = static function ( $items ) {
            return prepare_item_for_response( $items, true );
        };
    }
    return $routes;
}
function prepare_item_for_response( $items, $experimental ) {
    $legacy = array( 'item_data' => (object) $items );
    if ( ! $experimental ) { return rest_ensure_response( $legacy ); }
    return rest_ensure_response( array( 'items' => (object) $items ) );
}
