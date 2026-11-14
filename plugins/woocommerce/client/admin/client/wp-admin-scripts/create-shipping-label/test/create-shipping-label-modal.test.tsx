/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { useSelect } from '@wordpress/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { CreateShippingLabelModal } from '../create-shipping-label-modal';

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useSelect: jest.fn(),
	useDispatch: jest.fn( () => ( {} ) ),
} ) );
jest.mock( '@woocommerce/tracks', () => ( { recordEvent: jest.fn() } ) );
jest.mock( '~/components/tracked-link/tracked-link', () => ( {
	TrackedLink: () => <span>the WooCommerce Marketplace</span>,
} ) );
jest.mock( '~/shipping/woocommerce-shipping-item', () => () => (
	<div>WooCommerce Shipping</div>
) );
jest.mock( '~/shipping/shipstation-item', () => () => <div>ShipStation</div> );
jest.mock( '~/shipping/packlink-item', () => () => <div>Packlink PRO</div> );

const mockPlugins = ( activePlugins: string[] = [] ) => {
	( useSelect as jest.Mock ).mockImplementation( ( selector ) =>
		selector( () => ( {
			getInstalledPlugins: () => activePlugins,
			getActivePlugins: () => activePlugins,
		} ) )
	);
};

describe( 'CreateShippingLabelModal', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		mockPlugins();
	} );

	it( 'lists the extensions recommended for the store country', () => {
		render(
			<CreateShippingLabelModal
				storeCountry="US"
				onRequestClose={ jest.fn() }
			/>
		);

		expect(
			screen.getByText( 'WooCommerce Shipping' )
		).toBeInTheDocument();
		expect( screen.getByText( 'ShipStation' ) ).toBeInTheDocument();
		expect( screen.queryByText( 'Packlink PRO' ) ).not.toBeInTheDocument();
	} );

	it( 'shows a fallback message when the country has no recommendations', () => {
		render(
			<CreateShippingLabelModal
				storeCountry="JP"
				onRequestClose={ jest.fn() }
			/>
		);

		expect(
			screen.getByText(
				'We don’t have a recommended shipping extension for your store’s location yet.'
			)
		).toBeInTheDocument();
		expect( screen.queryByRole( 'list' ) ).not.toBeInTheDocument();
	} );

	it( 'records one impression with the order context', () => {
		const { rerender } = render(
			<CreateShippingLabelModal
				storeCountry="FR"
				onRequestClose={ jest.fn() }
			/>
		);
		rerender(
			<CreateShippingLabelModal
				storeCountry="FR"
				onRequestClose={ jest.fn() }
			/>
		);

		expect( recordEvent ).toHaveBeenCalledTimes( 1 );
		expect( recordEvent ).toHaveBeenCalledWith(
			'shipping_partner_impression',
			{
				context: 'order',
				country: 'FR',
				plugins: 'packlink-pro-shipping',
			}
		);
	} );

	it( 'asks to reload the page once an extension becomes active', () => {
		const { rerender } = render(
			<CreateShippingLabelModal
				storeCountry="US"
				onRequestClose={ jest.fn() }
			/>
		);
		expect(
			screen.queryByRole( 'button', { name: 'Reload page' } )
		).not.toBeInTheDocument();

		mockPlugins( [ 'woocommerce-shipping' ] );
		rerender(
			<CreateShippingLabelModal
				storeCountry="US"
				onRequestClose={ jest.fn() }
			/>
		);

		expect(
			screen.getByRole( 'button', { name: 'Reload page' } )
		).toBeInTheDocument();
	} );
} );
