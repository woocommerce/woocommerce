/**
 * External dependencies
 */
import { parseAdminUrl } from '@woocommerce/navigation';
import { captureException } from '@woocommerce/remote-logging';

/**
 * Internal dependencies
 */
import { getAdminSetting } from '~/utils/admin-settings';
import { DEFAULT_LOGO_WIDTH } from './assembler-hub/sidebar/constants';

export function isIframe( windowObject ) {
	return (
		windowObject.document !== windowObject.parent.document &&
		windowObject.parent.document.body.querySelector(
			'.woocommerce-customize-store__container'
		) !== null
	);
}

export function editorIsLoaded() {
	window.parent.postMessage(
		{ type: 'iframe-loaded' },
		getAdminSetting( 'homeUrl' )
	);
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
	const allowedOrigins = [ getAdminSetting( 'homeUrl' ) ];

	function handleMessage( event ) {
		// Validate the origin.
		if ( ! allowedOrigins.includes( event.origin ) ) {
			// Blocked message from untrusted origin: event.origin.
			return;
		}

		// Validate the structure of event.data.
		if (
			! event.data ||
			typeof event.data.type !== 'string' ||
			typeof event.data.url !== 'string'
		) {
			// Invalid message structure: event.data.
			return;
		}

		// Only allow the 'navigate' type.
		if ( event.data.type === 'navigate' ) {
			// Validate the URL format.
			try {
				const url = parseAdminUrl( event.data.url );
				// Further restrict navigation to trusted domains.
				if (
					! allowedOrigins.some( ( origin ) => url.origin === origin )
				) {
					throw new Error(
						`Blocked navigation to untrusted URL: ${ url.href }`
					);
				}

				window.location.href = url.href;
			} catch ( error ) {
				// Invalid URL: event.data.url.
				captureException( error );
			}
		}
	}

	window.addEventListener( 'message', handleMessage, false );

	return function removeListener() {
		window.removeEventListener( 'message', handleMessage, false );
	};
}

/**
 * If iframe, post message. Otherwise, navigate to a URL.
 *
 * @param {*} windowObject
 * @param {*} url
 */
export function navigateOrParent( windowObject, url ) {
	try {
		if ( isIframe( windowObject ) ) {
			windowObject.parent.postMessage(
				{ type: 'navigate', url },
				getAdminSetting( 'homeUrl' )
			);
		} else {
			const fullUrl = parseAdminUrl( url );
			windowObject.location.href = fullUrl.href;
		}
	} catch ( error ) {
		captureException( error );
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
