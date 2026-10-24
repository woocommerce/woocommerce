/* Cart reads this localStorage cache before initial render; a cache miss is refreshed from the server. */
export function readCartCache( storage ) { try { const value = JSON.parse( storage.getItem( 'wc-cart' ) ); return value && Array.isArray( value.items ) ? value.items : []; } catch ( error ) { return []; } }
