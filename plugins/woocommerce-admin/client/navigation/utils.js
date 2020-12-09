/**
 * External dependencies
 */
import { getAdminLink } from '@woocommerce/wc-admin-settings';

/**
 * Get the full URL if a relative path is passed.
 *
 * @param {string} url URL
 * @return {string} Full URL
 */
export const getFullUrl = ( url ) => {
	const { origin, pathname, search } = window.location;

	if ( url.indexOf( '#' ) === 0 ) {
		return origin + pathname + search + url;
	}

	if ( url.indexOf( 'http' ) === 0 ) {
		return url;
	}

	return origin + url;
};

/**
 * Get a match score for a menu item given a location.
 *
 * @param {Object} location Window location
 * @param {string} itemUrl	 URL to compare
 * @param {string} itemExpression Custom match expression
 * @return {number} Number of matches or 0 if not matched.
 */
export const getMatchScore = ( location, itemUrl, itemExpression = null ) => {
	if ( ! itemUrl ) {
		return;
	}

	const fullUrl = getFullUrl( itemUrl );
	const { href } = location;

	// Return highest possible score for exact match.
	if ( fullUrl === href ) {
		return Number.MAX_SAFE_INTEGER;
	}

	const defaultExpression = getDefaultMatchExpression( fullUrl );
	const regexp = new RegExp( itemExpression || defaultExpression, 'i' );
	return ( decodeURIComponent( href ).match( regexp ) || [] ).length;
};

/**
 * Get a default expression to match the path and provided params.
 *
 * @param {string} url URL to match.
 * @return {string} Regex expression.
 */
export const getDefaultMatchExpression = ( url ) => {
	const escapedUrl = url.replace( /[-\/\\^$*+?.()|[\]{}]/gi, '\\$&' );
	const [ path, args, hash ] = escapedUrl.split( /\\\?|#/ );
	const hashExpression = hash ? `(.*#${ hash }$)` : '';
	const argsExpression = args
		? args.split( '&' ).reduce( ( acc, param ) => {
				return `${ acc }(?=.*[?|&]${ param }(&|$|#))`;
		  }, '' )
		: '';
	return '^' + path + argsExpression + hashExpression;
};

/**
 * Adds a listener that runs on history change.
 *
 * @param {Function} listener Listener to add on history change.
 * @return {Function} Function to remove listeners.
 */
export const addHistoryListener = ( listener ) => {
	// Monkey patch pushState to allow trigger the pushstate event listener.
	if ( ! window.wcNavigation.historyPatched ) {
		( ( history ) => {
			/* global CustomEvent */
			const pushState = history.pushState;
			const replaceState = history.replaceState;
			history.pushState = function ( state ) {
				const pushStateEvent = new CustomEvent( 'pushstate', {
					state,
				} );
				window.dispatchEvent( pushStateEvent );
				return pushState.apply( history, arguments );
			};
			history.replaceState = function ( state ) {
				const replaceStateEvent = new CustomEvent( 'replacestate', {
					state,
				} );
				window.dispatchEvent( replaceStateEvent );
				return replaceState.apply( history, arguments );
			};
			window.wcNavigation.historyPatched = true;
		} )( window.history );
	}

	window.addEventListener( 'popstate', listener );
	window.addEventListener( 'pushstate', listener );
	window.addEventListener( 'replacestate', listener );

	return () => {
		window.removeEventListener( 'popstate', listener );
		window.removeEventListener( 'pushstate', listener );
		window.removeEventListener( 'replacestate', listener );
	};
};

/**
 * Get the closest matching item.
 *
 * @param {Array} items An array of items to match against.
 */
export const getMatchingItem = ( items ) => {
	let matchedItem = null;
	let highestMatchScore = 0;

	items.forEach( ( item ) => {
		const score = getMatchScore(
			window.location,
			getAdminLink( item.url ),
			item.matchExpression
		);
		if ( score > 0 && score >= highestMatchScore ) {
			highestMatchScore = score;
			matchedItem = item;
		}
	} );

	return matchedItem || null;
};
