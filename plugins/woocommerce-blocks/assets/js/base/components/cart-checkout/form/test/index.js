/**
 * External dependencies
 */
import { act, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { CheckoutProvider } from '@woocommerce/base-context';
import { useCheckoutAddress } from '@woocommerce/base-context/hooks';
import { ADDRESS_FORM_KEYS } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import Form from '../form';

jest.mock( '@wordpress/element', () => {
	return {
		...jest.requireActual( '@wordpress/element' ),
		useId: () => {
			return 'mock-id';
		},
	};
} );

const renderInCheckoutProvider = ( ui, options = {} ) => {
	const Wrapper = ( { children } ) => {
		return <CheckoutProvider>{ children }</CheckoutProvider>;
	};
	const result = render( ui, { wrapper: Wrapper, ...options } );

	return result;
};

// Countries used in testing addresses must be in the wcSettings global.
// See: tests/js/setup-globals.js
const primaryAddress = {
	country: 'United Kingdom',
	countryKey: 'GB',
	city: 'London',
	state: 'Greater London',
	postcode: 'ABCD',
};
const secondaryAddress = {
	country: 'Austria', // We use Austria because it doesn't have states.
	countryKey: 'AT',
	city: 'Vienna',
	postcode: 'DCBA',
};
const tertiaryAddress = {
	country: 'Canada', // We use Canada because it has a select for the state.
	countryKey: 'CA',
	city: 'Toronto',
	state: 'Ontario',
	postcode: 'EFGH',
};
const quaternaryAddress = {
	country: 'Japan',
	countryKey: 'JP',
	city: 'Tokyo',
	postcode: 'IJKL',
};

const cityRegExp = /city/i;
const stateRegExp = /county|province|state/i;
const postalCodeRegExp = /postal code|postcode|zip/i;

const inputAddress = async ( {
	countryKey = null,
	city = null,
	state = null,
	postcode = null,
} ) => {
	if ( countryKey ) {
		const countryInput = screen.getByLabelText( 'Country/Region' );

		if ( countryInput ) {
			await userEvent.selectOptions( countryInput, countryKey );
		}
	}
	if ( city ) {
		const cityInput = screen.getByLabelText( cityRegExp );
		await userEvent.type( cityInput, city );
	}

	if ( state ) {
		const stateButton = screen.queryByLabelText( stateRegExp, {
			selector: 'select',
		} );
		// State input might be a select or a text input.
		if ( stateButton ) {
			await userEvent.selectOptions( stateButton, state );
		} else {
			const stateInput = screen.getByLabelText( stateRegExp );
			await userEvent.type( stateInput, state );
		}
	}
	if ( postcode ) {
		const postcodeInput = screen.getByLabelText( postalCodeRegExp );
		await userEvent.type( postcodeInput, postcode );
	}
};

describe( 'Form Component', () => {
	const WrappedAddressForm = ( { type } ) => {
		const { setShippingAddress, shippingAddress } = useCheckoutAddress();

		return (
			<Form
				type={ type }
				onChange={ setShippingAddress }
				values={ shippingAddress }
				fields={ ADDRESS_FORM_KEYS }
			/>
		);
	};
	const ShippingFields = () => {
		const { shippingAddress } = useCheckoutAddress();

		return (
			<ul>
				{ Object.keys( shippingAddress ).map( ( key ) => (
					<li key={ key }>{ key + ': ' + shippingAddress[ key ] }</li>
				) ) }
			</ul>
		);
	};

	test( 'updates context value when interacting with form elements', async () => {
		renderInCheckoutProvider(
			<>
				<WrappedAddressForm type="shipping" />
				<ShippingFields />
			</>
		);

		await act( async () => {
			await inputAddress( primaryAddress );
		} );

		expect( screen.getByText( /country:/ ) ).toHaveTextContent(
			`country: ${ primaryAddress.countryKey }`
		);
		expect( screen.getByText( /city/ ) ).toHaveTextContent(
			`city: ${ primaryAddress.city }`
		);
		expect( screen.getByText( /state/ ) ).toHaveTextContent(
			`state: ${ primaryAddress.state }`
		);
		expect( screen.getByText( /postcode/ ) ).toHaveTextContent(
			`postcode: ${ primaryAddress.postcode }`
		);
	} );

	test( 'input fields update when changing the country', async () => {
		renderInCheckoutProvider( <WrappedAddressForm type="shipping" /> );

		await act( async () => {
			await inputAddress( primaryAddress );
		} );

		// Verify correct labels are used.
		expect( screen.getByLabelText( /City/ ) ).toBeInTheDocument();
		expect( screen.getByLabelText( /County/ ) ).toBeInTheDocument();
		expect( screen.getByLabelText( /Postcode/ ) ).toBeInTheDocument();

		await act( async () => {
			await inputAddress( secondaryAddress );
		} );

		// Verify state input has been removed.
		expect( screen.queryByText( stateRegExp ) ).not.toBeInTheDocument();

		await act( async () => {
			await inputAddress( tertiaryAddress );
		} );

		// Verify postal code input label changed.
		expect( screen.getByLabelText( /Postal code/ ) ).toBeInTheDocument();
	} );

	test( 'input values are reset after changing the country', async () => {
		renderInCheckoutProvider( <WrappedAddressForm type="shipping" /> );

		// First enter an address with no state, but fill the city.
		await act( async () => {
			await inputAddress( secondaryAddress );
		} );

		// Update country to another country without state.
		await act( async () => {
			await inputAddress( { countryKey: quaternaryAddress.countryKey } );
		} );

		expect( screen.getByLabelText( postalCodeRegExp ).value ).toBe( '' );
	} );
} );
