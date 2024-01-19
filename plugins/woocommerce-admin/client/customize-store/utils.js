/**
 * Internal dependencies
 */
import { DEFAULT_LOGO_WIDTH } from './assembler-hub/sidebar/constants';

export function sendMessageToParent( message ) {
	window.parent.postMessage( message, '*' );
}

export function isIframe( windowObject ) {
	return windowObject.document !== windowObject.parent.document;
}

export function editorIsLoaded() {
	window.parent.postMessage( { type: 'iframe-loaded' }, '*' );
}

export function onIframeLoad( callback ) {
	window.addEventListener( 'message', ( event ) => {
		if ( event.data.type === 'iframe-loaded' ) {
			callback();
		}
	} );
}

export function onBackButtonClicked( callback ) {
	window.addEventListener( 'message', ( event ) => {
		if ( event.data.type === 'assemberBackButtonClicked' ) {
			callback();
		}
	} );
}

/**
 * Attach a listener to the window object to listen for messages from the parent window.
 *
 * @return {() => void} Remove listener function
 */
export function attachParentListeners() {
	const listener = ( event ) => {
		if ( event.data.type === 'navigate' ) {
			window.location.href = event.data.url;
		}
	};

	window.addEventListener( 'message', listener, false );

	return () => {
		window.removeEventListener( 'message', listener, false );
	};
}

/**
 * If iframe, post message. Otherwise, navigate to a URL.
 *
 * @param {*} windowObject
 * @param {*} url
 */
export function navigateOrParent( windowObject, url ) {
	if ( isIframe( windowObject ) ) {
		windowObject.parent.postMessage( { type: 'navigate', url }, '*' );
	} else {
		windowObject.location.href = url;
	}
}

/**
 * Attach listeners to an iframe to intercept and redirect navigation events.
 *
 * @param {HTMLIFrameElement} iframe
 */
export function attachIframeListeners( iframe ) {
	const iframeDocument =
		iframe.contentDocument || iframe.contentWindow?.document;

	// Intercept external link clicks
	iframeDocument?.addEventListener( 'click', function ( event ) {
		if ( event.target ) {
			const anchor = event.target?.closest( 'a' );
			if ( anchor && anchor.target === '_blank' ) {
				event.preventDefault();
				window.open( anchor.href, '_blank' ); // Open in new tab in parent
			} else if ( anchor ) {
				event.preventDefault();
				window.location.href = anchor.href; // Navigate parent to new URL
			}
		}
	} );
}

export const setLogoWidth = ( content, width = DEFAULT_LOGO_WIDTH ) => {
	const logoPatternReg = /<!-- wp:site-logo\s*(\{.*?\})?\s*\/-->/g;

	// Replace the logo width with the default width.
	return content.replaceAll( logoPatternReg, ( match, group ) => {
		if ( group ) {
			const json = JSON.parse( group );
			json.width = width;
			return `<!-- wp:site-logo ${ JSON.stringify( json ) } /-->`;
		}
		return `<!-- wp:site-logo {"width":${ width }} /-->`;
	} );
};

/**
 * Create augmented steps for animation
 *
 * @param {Array}  steps
 * @param {number} numOfDupes
 * @return {Array} augmentedSteps
 *
 */
export const createAugmentedSteps = ( steps, numOfDupes ) => {
	// Duplicate each step, so we can animate each one
	// (e.g. each step will be duplicated 3 times, and each duplicate will
	// have different progress)
	const augmentedSteps = steps
		.map( ( item, index, array ) => {
			// Get the next item in the array
			const nextItem = array[ index + 1 ];
			// If there is no next item, we're at the end of the array
			// so just return the current item
			if ( ! nextItem ) return [ item ];

			// If there is a next item, we're not at the end of the array
			// so return the current item, plus duplicates
			const duplicates = [ item ];
			const progressIncreaseBy =
				( nextItem.progress - item.progress ) / numOfDupes;

			for ( let i = 0; i < numOfDupes; i++ ) {
				duplicates.push( {
					...item,
					progress: item.progress + ( i + 1 ) * progressIncreaseBy,
				} );
			}

			return duplicates;
		} )
		.flat();

	return augmentedSteps;
};
