/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';

// Counter to keep track of the number of components using a particular class
const classCounter: Record< string, number > = {};

const useBodyClass = ( className: string ) => {
	useEffect( () => {
		if ( typeof document === 'undefined' ) return;

		// Initialize or increment the counter for this class
		classCounter[ className ] = ( classCounter[ className ] || 0 ) + 1;

		// Add the class if this is the first component to request it
		if ( classCounter[ className ] === 1 ) {
			document.body.classList.add( className );
		}

		// Cleanup: Decrement the counter and remove the class if this is the last component to use it
		return () => {
			classCounter[ className ]--;
			if ( classCounter[ className ] === 0 ) {
				document.body.classList.remove( className );
			}
		};
	}, [ className ] );
};

export default useBodyClass;
