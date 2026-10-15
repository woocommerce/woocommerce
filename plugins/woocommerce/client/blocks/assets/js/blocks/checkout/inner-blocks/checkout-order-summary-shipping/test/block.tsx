/**
 * External dependencies
 */
import { render, within } from '@testing-library/react';
import { dispatch } from '@wordpress/data';
import { cartStore } from '@woocommerce/block-data';
import { SlotFillProvider } from '@woocommerce/blocks-checkout';

/**
 * Internal dependencies
 */
import Block from '../block';
import { generateShippingPackage } from '../../../../../mocks/shipping-package';

jest.mock( '@wordpress/api-fetch', () => ( {
	__esModule: true,
	default: Object.assign( jest.fn(), {
		use: jest.fn(),
		setNonce: jest.fn(),
		setCartHash: jest.fn(),
	} ),
} ) );

const noRatesText = 'No available delivery option';
const skeletonLabel = 'Loading price…';

// Sets a completed address and the empty rates from the last server response.
const setUpCart = ( { isUpdateQueued }: { isUpdateQueued: boolean } ) => {
	const cart = dispatch( cartStore );
	cart.finishResolution( 'getCartData', [] );
	cart.setCartData( {
		needsShipping: true,
		hasCalculatedShipping: true,
		shippingRates: [
			generateShippingPackage( { packageId: 0, shippingRates: [] } ),
		],
	} );
	cart.setShippingAddress( {
		first_name: 'John',
		last_name: 'Doe',
		company: '',
		address_1: '1 Valencia St',
		address_2: '',
		city: 'San Francisco',
		state: 'CA',
		postcode: '94110',
		country: 'US',
		phone: '',
	} );
	cart.__internalSetShippingRatesUpdateQueued( isUpdateQueued );
	// Setting the address starts a real push, so clear its in-flight flag.
	cart.updatingAddressFieldsForShippingRates( false );
};

const renderBlock = () =>
	within(
		render(
			<SlotFillProvider>
				<Block />
			</SlotFillProvider>
		).container
	);

describe( 'Checkout order summary shipping block', () => {
	it( 'shows a loading placeholder, not "No available delivery option", while rates are stale', () => {
		setUpCart( { isUpdateQueued: true } );

		const block = renderBlock();

		expect( block.getByLabelText( skeletonLabel ) ).toBeInTheDocument();
		expect( block.queryByText( noRatesText ) ).not.toBeInTheDocument();
	} );

	it( 'shows "No available delivery option" once the server has returned no rates', () => {
		setUpCart( { isUpdateQueued: false } );

		const block = renderBlock();

		expect( block.getByText( noRatesText ) ).toBeInTheDocument();
		expect(
			block.queryByLabelText( skeletonLabel )
		).not.toBeInTheDocument();
	} );
} );
