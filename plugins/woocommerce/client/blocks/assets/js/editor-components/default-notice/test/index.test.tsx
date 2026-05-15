/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { DefaultNotice } from '../index';

jest.mock( '@wordpress/data', () => {
	const originalModule = jest.requireActual( '@wordpress/data' );
	return {
		...originalModule,
		useSelect: jest.fn( () => ( {
			slug: 'checkout-2',
			postPublished: true,
			currentPostId: 99,
		} ) ),
		useDispatch: jest.fn( () => ( {
			saveEntityRecord: jest.fn(),
			editPost: jest.fn(),
			savePost: jest.fn(),
		} ) ),
	};
} );

jest.mock( '@woocommerce/block-settings', () => ( {
	CHECKOUT_PAGE_ID: 10,
	CART_PAGE_ID: 11,
} ) );

jest.mock( '@woocommerce/settings', () => ( {
	getAdminLink: jest.fn(
		( path: string ) => `https://example.test/wp-admin/${ path }`
	),
} ) );

jest.mock( '@woocommerce/utils', () => ( {
	CORE_EDITOR_STORE: 'core/editor',
} ) );

jest.mock( '@wordpress/api-fetch', () => ( {
	__esModule: true,
	default: jest.fn( () => Promise.resolve( {} ) ),
} ) );

describe( 'DefaultNotice', () => {
	it( 'renders the checkout notice with both automatic and manual options', () => {
		const { container } = render( <DefaultNotice block="checkout" /> );

		// The leading copy clarifies the user is choosing between two actions.
		expect( container.textContent ).toMatch(
			/To set this as your store.+default checkout/
		);

		// Two distinct links: one for the auto-assign action, one for settings.
		const assignLinks = screen.getAllByRole( 'link', {
			name: /assign this page automatically/i,
		} );
		expect( assignLinks.length ).toBeGreaterThan( 0 );
		expect( assignLinks[ 0 ] ).toHaveAttribute( 'href', '#' );

		const settingsLinks = screen.getAllByRole( 'link', {
			name: /update your page settings manually/i,
		} );
		expect( settingsLinks.length ).toBeGreaterThan( 0 );
		expect( settingsLinks[ 0 ].getAttribute( 'href' ) ).toMatch(
			/page=wc-settings&tab=advanced/
		);
	} );

	it( 'renders the cart notice with both automatic and manual options', () => {
		const { container } = render( <DefaultNotice block="cart" /> );

		expect( container.textContent ).toMatch(
			/To set this as your store.+default cart/
		);
		expect(
			screen.getAllByRole( 'link', {
				name: /assign this page automatically/i,
			} ).length
		).toBeGreaterThan( 0 );
		expect(
			screen.getAllByRole( 'link', {
				name: /update your page settings manually/i,
			} ).length
		).toBeGreaterThan( 0 );
	} );
} );
