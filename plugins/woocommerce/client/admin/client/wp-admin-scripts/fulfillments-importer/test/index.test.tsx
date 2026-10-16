/**
 * External dependencies
 */
import { screen } from '@testing-library/react';

// The factory requires React itself so the stub belongs to the same React
// copy as the isolated bootstrap that renders it.
jest.mock( '../components/fulfillments-importer-modal', () => {
	const { createElement } = jest.requireActual( 'react' );
	return ( props: { isOpen: boolean } ) =>
		props.isOpen ? createElement( 'div', null, 'MODAL_OPEN' ) : null;
} );

const SLOT_ID = 'wc-fulfillments-importer-trigger-slot';
const CONTAINER = '<div id="wc_fulfillments_importer_panel_container"></div>';
const TITLE_ACTION =
	'<div class="wrap"><h1>Orders</h1><a class="page-title-action" href="#">Add order</a></div>';

/**
 * The bootstrap runs at import time, so load it in a fresh module registry
 * for every test and drive React through the act() of that same registry.
 */
function loadScript() {
	let act!: typeof import('react').act;
	jest.isolateModules( () => {
		( { act } = jest.requireActual( 'react' ) );
		act( () => {
			jest.requireActual( '../index' );
		} );
	} );
	return act;
}

function mountScreen( bodyClasses: string, html = TITLE_ACTION + CONTAINER ) {
	document.body.className = bodyClasses;
	document.body.innerHTML = html;
}

describe( 'fulfillments importer bootstrap', () => {
	afterEach( () => {
		document.body.innerHTML = '';
		document.body.className = '';
		delete window.wcFulfillmentsImporterSettings;
		window.history.replaceState( null, '', '/' );
	} );

	it.each( [
		[ 'HPOS', 'woocommerce_page_wc-orders' ],
		[ 'legacy', 'edit-php post-type-shop_order' ],
	] )(
		'inserts the trigger after the page title action on the %s orders list',
		( _storage, bodyClasses ) => {
			mountScreen( bodyClasses );

			loadScript();

			const slot = document.getElementById( SLOT_ID );
			expect( slot ).not.toBeNull();
			expect( slot?.previousElementSibling ).toHaveClass(
				'page-title-action'
			);
			expect(
				screen.getByRole( 'button', { name: 'Import fulfillments' } )
			).toBeInTheDocument();
			expect( screen.queryByText( 'MODAL_OPEN' ) ).toBeNull();
		}
	);

	it( 'adds no trigger on other admin screens', () => {
		mountScreen( 'dashboard' );

		loadScript();

		expect( document.getElementById( SLOT_ID ) ).toBeNull();
	} );

	it( 'reuses a slot that already exists instead of adding a second one', () => {
		mountScreen( 'woocommerce_page_wc-orders' );
		const existing = document.createElement( 'span' );
		existing.id = SLOT_ID;
		document.querySelector( '.wrap' )?.appendChild( existing );

		loadScript();

		expect( document.querySelectorAll( `#${ SLOT_ID }` ) ).toHaveLength(
			1
		);
		expect( existing.querySelector( 'button' ) ).not.toBeNull();
	} );

	it( 'does nothing without the mount point', () => {
		mountScreen( 'woocommerce_page_wc-orders', TITLE_ACTION );

		loadScript();

		expect( document.getElementById( SLOT_ID ) ).toBeNull();
	} );

	it( 'opens the wizard from the trigger', () => {
		mountScreen( 'woocommerce_page_wc-orders' );
		const act = loadScript();

		const trigger = screen.getByRole( 'button', {
			name: 'Import fulfillments',
		} );
		act( () => {
			trigger.click();
		} );

		expect( screen.getByText( 'MODAL_OPEN' ) ).toBeInTheDocument();
	} );

	it( 'opens the wizard when PHP localizes the auto-open flag', () => {
		window.wcFulfillmentsImporterSettings = {
			importRoute: '/wc/v3/fulfillments/import',
			autoOpen: '1',
			providers: [],
		};
		mountScreen( 'woocommerce_page_wc-orders' );

		loadScript();

		expect( screen.getByText( 'MODAL_OPEN' ) ).toBeInTheDocument();
	} );

	it( 'opens the wizard from the query arg and strips it from the URL', () => {
		window.history.replaceState(
			null,
			'',
			'/wp-admin/admin.php?page=wc-orders&fulfillments_importer=open'
		);
		mountScreen( 'woocommerce_page_wc-orders' );

		loadScript();

		expect( screen.getByText( 'MODAL_OPEN' ) ).toBeInTheDocument();
		expect( window.location.search ).toBe( '?page=wc-orders' );
	} );

	it( 'drops the arg without opening when it carries another value', () => {
		window.history.replaceState(
			null,
			'',
			'/wp-admin/admin.php?page=wc-orders&fulfillments_importer=closed'
		);
		mountScreen( 'woocommerce_page_wc-orders' );

		loadScript();

		expect( screen.queryByText( 'MODAL_OPEN' ) ).toBeNull();
		expect( window.location.search ).toBe( '?page=wc-orders' );
	} );
} );
