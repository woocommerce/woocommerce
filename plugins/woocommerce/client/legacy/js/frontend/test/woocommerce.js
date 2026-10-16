/**
 * @jest-environment jest-fixed-jsdom
 */

describe( 'refresh_sorted_by_live_region', () => {
	const loadScript = () => {
		global.jQuery = jest.fn();
		jest.isolateModules( () => {
			require( '../woocommerce' );
		} );
		document.dispatchEvent( new Event( 'DOMContentLoaded' ) );
	};

	beforeEach( () => {
		jest.useFakeTimers();
		window.history.pushState( {}, '', '/shop/?orderby=price' );
	} );

	afterEach( () => {
		jest.useRealTimers();
		document.body.innerHTML = '';
		window.history.pushState( {}, '', '/' );
	} );

	it( 'turns the classic result count into an alert after a sorted page load', () => {
		document.body.innerHTML =
			'<p class="woocommerce-result-count">Showing 1–16 of 18 results</p>';

		loadScript();
		jest.runAllTimers();

		const resultCount = document.querySelector( '.woocommerce-result-count' );
		expect( resultCount.getAttribute( 'role' ) ).toBe( 'alert' );
		expect( resultCount.getAttribute( 'aria-hidden' ) ).toBe( 'false' );
	} );

	it( 'leaves the Product Results Count block untouched', () => {
		document.body.innerHTML =
			'<div class="wc-block-product-results-count"><p class="woocommerce-result-count">Showing 1–16 of 18 results</p></div>';
		const resultCount = document.querySelector( '.woocommerce-result-count' );
		const textNode = resultCount.firstChild;

		loadScript();
		jest.runAllTimers();

		expect( resultCount.hasAttribute( 'role' ) ).toBe( false );
		expect( resultCount.hasAttribute( 'aria-hidden' ) ).toBe( false );
		// The block's text node must survive, or client-side navigation updates a detached node.
		expect( resultCount.firstChild ).toBe( textNode );
	} );
} );
