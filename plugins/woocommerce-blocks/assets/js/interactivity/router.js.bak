import { hydrate, render } from 'preact';
import { toVdom, hydratedIslands } from './vdom';
import { createRootFragment } from './utils';
import { directivePrefix } from './constants';

// The cache of visited and prefetched pages.
const pages = new Map();

// Keep the same root fragment for each interactive region node.
const regionRootFragments = new WeakMap();
const getRegionRootFragment = ( region ) => {
	if ( ! regionRootFragments.has( region ) ) {
		regionRootFragments.set(
			region,
			createRootFragment( region.parentElement, region )
		);
	}
	return regionRootFragments.get( region );
};

// Helper to remove domain and hash from the URL. We are only interesting in
// caching the path and the query.
const cleanUrl = ( url ) => {
	const u = new URL( url, window.location );
	return u.pathname + u.search;
};

// Fetch a new page and convert it to a static virtual DOM.
const fetchPage = async ( url ) => {
	let dom;
	try {
		const res = await window.fetch( url );
		if ( res.status !== 200 ) return false;
		const html = await res.text();
		dom = new window.DOMParser().parseFromString( html, 'text/html' );
	} catch ( e ) {
		return false;
	}

	return regionsToVdom( dom );
};

// Return an object with VDOM trees of those HTML regions marked with a
// `navigation-id` directive.
const regionsToVdom = ( dom ) => {
	const regions = {};
	const attrName = `data-${ directivePrefix }-navigation-id`;
	dom.querySelectorAll( `[${ attrName }]` ).forEach( ( region ) => {
		const id = region.getAttribute( attrName );
		regions[ id ] = toVdom( region );
	} );

	return { regions };
};

// Prefetch a page. We store the promise to avoid triggering a second fetch for
// a page if a fetching has already started.
export const prefetch = ( url ) => {
	url = cleanUrl( url );
	if ( ! pages.has( url ) ) {
		pages.set( url, fetchPage( url ) );
	}
};

// Render all interactive regions contained in the given page.
const renderRegions = ( page ) => {
	const attrName = `data-${ directivePrefix }-navigation-id`;
	document.querySelectorAll( `[${ attrName }]` ).forEach( ( region ) => {
		const id = region.getAttribute( attrName );
		const fragment = getRegionRootFragment( region );
		render( page.regions[ id ], fragment );
	} );
};

// Navigate to a new page.
export const navigate = async ( href, { replace = false } = {} ) => {
	const url = cleanUrl( href );
	prefetch( url );
	const page = await pages.get( url );
	if ( page ) {
		renderRegions( page );
		window.history[ replace ? 'replaceState' : 'pushState' ](
			{},
			'',
			href
		);
	} else {
		window.location.assign( href );
	}
};

// Listen to the back and forward buttons and restore the page if it's in the
// cache.
window.addEventListener( 'popstate', async () => {
	const url = cleanUrl( window.location ); // Remove hash.
	const page = pages.has( url ) && ( await pages.get( url ) );
	if ( page ) {
		renderRegions( page );
	} else {
		window.location.reload();
	}
} );

// Initialize the router with the initial DOM.
export const init = async () => {
	document
		.querySelectorAll( `[data-${ directivePrefix }-interactive]` )
		.forEach( ( node ) => {
			if ( ! hydratedIslands.has( node ) ) {
				const fragment = getRegionRootFragment( node );
				const vdom = toVdom( node );
				hydrate( vdom, fragment );
			}
		} );

	// Cache the current regions.
	pages.set(
		cleanUrl( window.location ),
		Promise.resolve( regionsToVdom( document ) )
	);
};
