/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import Header from '../';

global.window.wcNavigation = {};

jest.mock( '@woocommerce/settings', () => ( {
	...jest.requireActual( '@woocommerce/settings' ),
	getSetting: jest.fn( ( setting ) => {
		const settings = {
			homeUrl: 'https://fake-site-url.com',
			siteTitle: 'Fake &amp; Title &lt;3',
		};
		return settings[ setting ];
	} ),
} ) );

jest.mock( '@wordpress/data', () => {
	// Require the original module to not be mocked...
	const originalModule = jest.requireActual( '@wordpress/data' );

	return {
		__esModule: true, // Use it when dealing with esModules
		...originalModule,
		useDispatch: jest.fn().mockReturnValue( {} ),
		useSelect: jest.fn().mockReturnValue( {} ),
	};
} );

describe( 'Header', () => {
	test( 'should not show the button when the site icon is requesting', () => {
		useSelect.mockImplementation( () => ( {
			isRequestingSiteIcon: true,
			siteIconUrl: null,
		} ) );

		const { container } = render( <Header /> );

		expect( container.querySelector( 'img' ) ).toBeNull();
	} );

	test( 'should show the button when the site icon has resolved', () => {
		useSelect.mockImplementation( () => ( {
			isRequestingSiteIcon: false,
			siteIconUrl: '#',
		} ) );

		const { container } = render( <Header /> );

		expect( container.querySelector( 'img' ) ).not.toBeNull();
	} );

	test( 'should start with the nav expanded in larger viewports', () => {
		Object.defineProperty( window.HTMLElement.prototype, 'clientWidth', {
			configurable: true,
			value: 2000,
		} );

		render( <Header /> );

		expect( Object.values( document.body.classList ) ).toContain(
			'is-wc-nav-expanded'
		);
	} );

	test( 'should start with the nav folded when the viewport is smaller', () => {
		Object.defineProperty( window.HTMLElement.prototype, 'clientWidth', {
			configurable: true,
			value: 480,
		} );

		render( <Header /> );

		expect( Object.values( document.body.classList ) ).toContain(
			'is-wc-nav-folded'
		);
	} );

	test( 'should decode site titles', () => {
		const { container } = render( <Header /> );

		expect(
			container.querySelector(
				'.woocommerce-navigation-header__site-title'
			).textContent
		).toBe( 'Fake & Title <3' );
	} );
} );
