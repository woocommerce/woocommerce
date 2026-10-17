/* Classic cart notification. The extension event contract permits optional trailing metadata. */
function notifyAddedToCart( fragments, cartHash, button ) { jQuery( document.body ).trigger( 'added_to_cart', [ fragments, cartHash, button, { source: 'classic' } ] ); }
