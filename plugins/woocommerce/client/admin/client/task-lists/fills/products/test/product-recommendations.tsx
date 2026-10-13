/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { ProductRecommendations } from '../product-recommendations';

jest.mock( '@woocommerce/tracks', () => ( { recordEvent: jest.fn() } ) );
jest.mock( '~/lib/in-app-purchase', () => ( {
	getInAppPurchaseUrl: ( url: string ) => `${ url }?utm=1`,
} ) );

const items = [
	{
		slug: 'products-list-empty-body-bookings',
		product: 'woocommerce-bookings',
		title: 'Bookings',
		copy: 'Take bookings.',
		icon: 'https://example.com/bookings.png',
		url: 'https://woocommerce.com/products/woocommerce-bookings/',
		promoted: '',
	},
	{
		slug: 'products-list-empty-body-gift-cards',
		product: 'gift-cards',
		title: 'Gift Cards',
		copy: 'Sell gift cards.',
		icon: '',
		url: 'https://woocommerce.com/products/gift-cards/',
		promoted: 'promo',
	},
];

describe( 'ProductRecommendations', () => {
	beforeEach( () => jest.clearAllMocks() );

	it( 'tracks each item as displayed once and links out in a new tab', () => {
		const { rerender } = render(
			<ProductRecommendations items={ items } />
		);
		rerender( <ProductRecommendations items={ items } /> );

		expect( recordEvent ).toHaveBeenCalledTimes( 2 );
		expect( recordEvent ).toHaveBeenCalledWith(
			'marketplace_suggestion_displayed',
			{
				suggestion_slug: 'products-list-empty-body-gift-cards',
				context: 'products-list-empty-body',
				product: 'gift-cards',
				promoted: 'promo',
				target: 'https://woocommerce.com/products/gift-cards/',
			}
		);

		// Rows come from List, whose items carry role="menuitem".
		const link = screen.getByText( 'Bookings' ).closest( 'a' );
		expect( link ).toHaveAttribute( 'target', '_blank' );
		expect( link ).toHaveAttribute(
			'href',
			'https://woocommerce.com/products/woocommerce-bookings/?utm=1'
		);
	} );

	it( 'tracks clicks on an item and on the browse all link', async () => {
		render( <ProductRecommendations items={ items } /> );
		( recordEvent as jest.Mock ).mockClear();

		await userEvent.click( screen.getByText( 'Bookings' ) );
		expect( recordEvent ).toHaveBeenLastCalledWith(
			'marketplace_suggestion_clicked',
			{
				suggestion_slug: 'products-list-empty-body-bookings',
				context: 'products-list-empty-body',
				product: 'woocommerce-bookings',
				promoted: '',
				target: 'https://woocommerce.com/products/woocommerce-bookings/?utm=1',
			}
		);

		await userEvent.click(
			screen.getByRole( 'link', {
				name: 'Visit the WooCommerce Marketplace',
			} )
		);
		expect( recordEvent ).toHaveBeenLastCalledWith(
			'marketplace_suggestion_clicked',
			expect.objectContaining( {
				suggestion_slug: 'products-empty-footer-browse-all',
				context: 'products-list-empty-footer',
			} )
		);
	} );

	it( 'tracks a keyboard click on an item once', async () => {
		render( <ProductRecommendations items={ items } /> );
		( recordEvent as jest.Mock ).mockClear();

		screen.getByText( 'Bookings' ).closest( 'a' )?.focus();
		await userEvent.keyboard( '{Enter}' );

		expect( recordEvent ).toHaveBeenCalledTimes( 1 );
		expect( recordEvent ).toHaveBeenCalledWith(
			'marketplace_suggestion_clicked',
			expect.objectContaining( {
				suggestion_slug: 'products-list-empty-body-bookings',
			} )
		);
	} );
} );
