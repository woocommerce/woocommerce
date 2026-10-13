<?php
/* Scenario context: items is an ID-keyed string map. Bootstrap registers the exact route-to-callback map returned by item_response_routes; these are the only callers of the internal response helper. /wc/v3/items is released. The optional /wc/experimental/items-preview route has never shipped. Feature values come from trusted server configuration and default off; clients cannot override the boolean passed to the helper. */
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
