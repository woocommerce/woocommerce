// Store the original jQuery.fn.ready method
const originalReady = jQuery.fn.ready;
const callbacks = [];
// Override the jQuery.fn.ready method
jQuery.fn.ready = function ( callback ) {
	// Queue the callback
	callbacks.push( callback );
};

// Function to release the queued callbacks
function releaseReady() {
	// Restore the original jQuery.fn.ready method
	jQuery.fn.ready = originalReady;

	// Execute all queued callbacks but save them for when switching tabs.
	const callbacksCopy = [ ...callbacks ];
	while ( callbacksCopy.length ) {
		callbacksCopy.shift()( jQuery );
	}
}

jQuery( window ).on( 'reactRendered', function () {
	try {
		releaseReady();
	} catch ( error ) {
		// swallow errors here - I'm sorry.
	}
} );
