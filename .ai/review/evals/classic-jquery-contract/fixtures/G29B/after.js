/* Classic cart notification and an installed extension’s listener. */
function notifyAddedToCart( fragments, cartHash, button ) { jQuery( document ).trigger( 'added_to_cart', [ fragments, cartHash, button ] ); }

jQuery( document.body ).on( 'added_to_cart', ( event, fragments, cartHash, button ) => { button.removeClass( 'loading' ); } );
