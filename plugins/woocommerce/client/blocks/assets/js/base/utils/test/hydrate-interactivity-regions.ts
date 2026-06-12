/**
 * Internal dependencies
 */
import { hydrateInteractivityRegions } from '../hydrate-interactivity-regions';

const mockRender = jest.fn();
const mockToVdom = jest.fn( ( node: Node ) => ( { node } ) );
const mockGetRegionRootFragment = jest.fn( ( region: Element ) => ( {
	region,
} ) );

jest.mock( '@wordpress/interactivity', () => ( {
	privateApis: () => ( {
		getRegionRootFragment: mockGetRegionRootFragment,
		toVdom: mockToVdom,
		render: mockRender,
	} ),
} ) );

describe( 'hydrateInteractivityRegions', () => {
	beforeEach( () => {
		document.body.innerHTML = '';
		jest.clearAllMocks();
	} );

	it( 'hydrates top-level interactive regions inside the container', async () => {
		document.body.innerHTML = `
			<div id="container">
				<div id="region-1" data-wp-interactive="my-plugin/one"></div>
				<div>
					<div id="region-2" data-wp-interactive="my-plugin/two"></div>
				</div>
			</div>
		`;
		const container = document.getElementById( 'container' ) as HTMLElement;

		await hydrateInteractivityRegions( container );

		expect( mockRender ).toHaveBeenCalledTimes( 2 );
		expect( mockToVdom ).toHaveBeenCalledWith(
			document.getElementById( 'region-1' )
		);
		expect( mockToVdom ).toHaveBeenCalledWith(
			document.getElementById( 'region-2' )
		);
	} );

	it( 'skips nested interactive regions', async () => {
		document.body.innerHTML = `
			<div id="container">
				<div id="outer" data-wp-interactive="my-plugin/outer">
					<div id="inner" data-wp-interactive="my-plugin/inner"></div>
				</div>
			</div>
		`;
		const container = document.getElementById( 'container' ) as HTMLElement;

		await hydrateInteractivityRegions( container );

		expect( mockRender ).toHaveBeenCalledTimes( 1 );
		expect( mockToVdom ).toHaveBeenCalledWith(
			document.getElementById( 'outer' )
		);
	} );

	it( 'does not hydrate the same region twice', async () => {
		document.body.innerHTML = `
			<div id="container">
				<div data-wp-interactive="my-plugin/one"></div>
			</div>
		`;
		const container = document.getElementById( 'container' ) as HTMLElement;

		await hydrateInteractivityRegions( container );
		await hydrateInteractivityRegions( container );

		expect( mockRender ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'does nothing when there are no interactive regions', async () => {
		document.body.innerHTML = `<div id="container"><p>No regions</p></div>`;
		const container = document.getElementById( 'container' ) as HTMLElement;

		await hydrateInteractivityRegions( container );

		expect( mockRender ).not.toHaveBeenCalled();
	} );

	it( 'moves template children into the template content fragment', async () => {
		document.body.innerHTML = `
			<div id="container">
				<div id="region" data-wp-interactive="my-plugin/one"></div>
			</div>
		`;
		const region = document.getElementById( 'region' ) as HTMLElement;
		// Recreate what happens when React renders a parsed `<template>`:
		// children are appended as regular child nodes instead of being
		// placed in the `content` fragment.
		const template = document.createElement( 'template' );
		template.appendChild( document.createElement( 'span' ) );
		region.appendChild( template );
		expect( template.content.childNodes ).toHaveLength( 0 );

		await hydrateInteractivityRegions(
			document.getElementById( 'container' ) as HTMLElement
		);

		expect( template.content.childNodes ).toHaveLength( 1 );
		expect( template.childNodes ).toHaveLength( 0 );
	} );
} );
