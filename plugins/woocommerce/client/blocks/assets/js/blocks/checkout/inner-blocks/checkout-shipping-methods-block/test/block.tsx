/**
 * External dependencies
 */
import { act, render, within } from '@testing-library/react';
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

const completeAddress = {
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
};

const incompleteAddress = {
	...completeAddress,
	city: '',
	postcode: '',
};

const warningText =
	'No shipping options are available for this address. Please verify the address is correct or try a different address.';
const neutralText = 'Enter a shipping address to view shipping options.';
const skeletonLabel = 'Loading shipping options…';

// Sets the address the shopper has typed, and the rates from the last server response.
const setUpCart = ( {
	shippingAddress,
	hasCalculatedShipping = true,
	isUpdateQueued = false,
	isLoadingRates = false,
}: {
	shippingAddress: typeof completeAddress;
	hasCalculatedShipping?: boolean;
	isUpdateQueued?: boolean;
	isLoadingRates?: boolean;
} ) => {
	const cart = dispatch( cartStore );
	cart.setCartData( {
		needsShipping: true,
		hasCalculatedShipping,
		shippingRates: hasCalculatedShipping
			? [ generateShippingPackage( { packageId: 0, shippingRates: [] } ) ]
			: [],
	} );
	cart.setShippingAddress( shippingAddress );
	cart.__internalSetShippingRatesUpdateQueued( isUpdateQueued );
	cart.updatingAddressFieldsForShippingRates( isLoadingRates );
};

// Queries are scoped to the block because a11y speak repeats notices in a live region outside it.
const renderBlock = () =>
	within(
		render(
			<SlotFillProvider>
				<Block />
			</SlotFillProvider>
		).container
	);

describe( 'Checkout shipping methods block', () => {
	it( 'shows the skeleton, not the no-rates warning, while a completed address is waiting to be sent', () => {
		setUpCart( {
			shippingAddress: completeAddress,
			isUpdateQueued: true,
		} );

		const block = renderBlock();

		expect( block.getByLabelText( skeletonLabel ) ).toBeInTheDocument();
		expect( block.queryByText( warningText ) ).not.toBeInTheDocument();
	} );

	it( 'shows the no-rates warning once the server has returned no rates for the completed address', () => {
		setUpCart( {
			shippingAddress: completeAddress,
			isUpdateQueued: true,
		} );

		const block = renderBlock();

		act( () => {
			dispatch( cartStore ).__internalSetShippingRatesUpdateQueued(
				false
			);
		} );

		expect( block.getByText( warningText ) ).toBeInTheDocument();
		expect(
			block.queryByLabelText( skeletonLabel )
		).not.toBeInTheDocument();
	} );

	it.each( [
		[ 'waiting to be sent', { isUpdateQueued: true } ],
		[ 'being sent', { isLoadingRates: true } ],
	] )(
		'shows the skeleton when shipping has not been calculated yet and the completed address is %s',
		( _, state ) => {
			setUpCart( {
				shippingAddress: completeAddress,
				hasCalculatedShipping: false,
				...state,
			} );

			const block = renderBlock();

			expect( block.getByLabelText( skeletonLabel ) ).toBeInTheDocument();
			expect( block.queryByText( neutralText ) ).not.toBeInTheDocument();
		}
	);

	it( 'keeps asking for an address while the address is incomplete, even with changes waiting to be sent', () => {
		setUpCart( {
			shippingAddress: incompleteAddress,
			isUpdateQueued: true,
		} );

		const block = renderBlock();

		expect( block.getByText( neutralText ) ).toBeInTheDocument();
		expect(
			block.queryByLabelText( skeletonLabel )
		).not.toBeInTheDocument();
	} );
} );
