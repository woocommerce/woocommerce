/**
 * Hydrate WordPress Interactivity API regions that were added to the DOM
 * after the Interactivity API runtime performed its initial hydration on
 * page load.
 *
 * Some blocks (e.g. Cart) are rendered with React on the frontend and
 * re-create their server-rendered inner HTML. Any Interactivity API powered
 * blocks (e.g. Product Collection with its Add to Cart button) contained in
 * that HTML lose their interactivity, because the Interactivity API runtime
 * only hydrates regions present in the DOM when the page loads. This utility
 * hydrates such regions using the same private APIs that
 * `@wordpress/interactivity-router` uses for client-side navigation.
 */

/**
 * Consent string required to unlock the private APIs of
 * `@wordpress/interactivity`. It must match the string defined in the
 * package.
 */
const PRIVATE_APIS_CONSENT =
	'I acknowledge that using private APIs means my theme or plugin will inevitably break in the next version of WordPress.';

interface InteractivityPrivateApis {
	getRegionRootFragment: ( region: Element ) => unknown;
	toVdom: ( node: Node ) => unknown;
	render: ( vdom: unknown, rootFragment: unknown ) => void;
}

// Tracks regions hydrated by this utility to prevent double hydration.
const hydratedRegions = new WeakSet< Element >();

/**
 * Move child nodes of `<template>` elements into their `content` fragment.
 *
 * When server-rendered HTML is re-created with React (e.g. parsed with
 * `html-react-parser` and rendered again), children of `<template>` elements
 * become regular child nodes instead of being placed in the template
 * `content` fragment, which is where the Interactivity API reads them from
 * (e.g. for `data-wp-each` directives).
 */
const fixTemplateContents = ( region: Element ) => {
	region.querySelectorAll( 'template' ).forEach( ( template ) => {
		if (
			template.content.childNodes.length === 0 &&
			template.childNodes.length > 0
		) {
			template.content.append( ...Array.from( template.childNodes ) );
		}
	} );
};

/**
 * Hydrates all Interactivity API regions (`data-wp-interactive` elements)
 * found within the given container.
 *
 * Nested regions are hydrated as part of their closest ancestor region, like
 * the Interactivity API runtime does on page load.
 */
export const hydrateInteractivityRegions = async (
	container: HTMLElement
): Promise< void > => {
	const regions = Array.from(
		container.querySelectorAll( '[data-wp-interactive]' )
	).filter(
		( region ) =>
			! region.parentElement?.closest( '[data-wp-interactive]' ) &&
			! hydratedRegions.has( region )
	);

	if ( regions.length === 0 ) {
		return;
	}

	try {
		// This must be a native dynamic import resolved through the page's
		// import map (hence the `webpackIgnore` comment), so it returns the
		// exact same Interactivity API runtime instance (and store registry)
		// used by the script modules loaded by WordPress.
		const { privateApis } = await import(
			/* webpackIgnore: true */ '@wordpress/interactivity'
		);
		const { getRegionRootFragment, toVdom, render } = privateApis(
			PRIVATE_APIS_CONSENT
		) as InteractivityPrivateApis;

		regions.forEach( ( region ) => {
			// The region may have been unmounted or hydrated by a concurrent
			// call while the module import was being awaited.
			if ( ! region.isConnected || hydratedRegions.has( region ) ) {
				return;
			}
			hydratedRegions.add( region );
			fixTemplateContents( region );
			render( toVdom( region ), getRegionRootFragment( region ) );
		} );
	} catch {
		// The Interactivity API runtime is not available, e.g. there are no
		// script modules on the page or the browser doesn't support import
		// maps. Regions stay non-interactive, matching the behavior before
		// this utility existed.
	}
};
