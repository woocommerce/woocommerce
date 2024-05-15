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

const renderInCheckoutProvider = ( ui, options = { legacyRoot: true } ) => {
	const Wrapper = ( { children } ) => {
		return <CheckoutProvider>{ children }</CheckoutProvider>;
	};
	const result = render( ui, { wrapper: Wrapper, ...options } );
	// We need to switch to React 17 rendering to allow these tests to keep passing, but as a result the React
	// rendering error will be shown.
	expect( console ).toHaveErroredWith(
		`Warning: ReactDOM.render is no longer supported in React 18. Use createRoot instead. Until you switch to the new API, your app will behave as if it's running React 17. Learn more: https://reactjs.org/link/switch-to-createroot`
	);

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
	countryKey: 'AU',
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

const countryRegExp = /country/i;
const cityRegExp = /city/i;
const stateRegExp = /county|province|state/i;
const postalCodeRegExp = /postal code|postcode|zip/i;

const inputAddress = async ( {
	country = null,
	city = null,
	state = null,
	postcode = null,
} ) => {
	if ( country ) {
		const countryInput = screen.queryByRole( 'combobox', {
			name: countryRegExp,
		} );
		await userEvent.type( countryInput, country + '{arrowdown}{enter}' );
	}
	if ( city ) {
		const cityInput = screen.getByLabelText( cityRegExp );
		await userEvent.type( cityInput, city );
	}

	if ( state ) {
		const stateButton = screen.queryByRole( 'combobox', {
			name: stateRegExp,
		} );
		// State input might be a select or a text input.
		if ( stateButton ) {
			await userEvent.click( stateButton );
			await userEvent.click(
				screen.getByRole( 'option', { name: state } )
			);
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

	it( 'updates context value when interacting with form elements', async () => {
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

	it( 'input fields update when changing the country', async () => {
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

	it( 'input values are reset after changing the country', async () => {
		renderInCheckoutProvider( <WrappedAddressForm type="shipping" /> );

		await act( async () => {
			await inputAddress( secondaryAddress );
		} );

		// Only update `country` to verify other values are reset.
		await act( async () => {
			await inputAddress( { country: primaryAddress.country } );
		} );

		expect( screen.getByLabelText( stateRegExp ).value ).toBe( '' );

		// Repeat the test with an address which has a select for the state.
		await act( async () => {
			await inputAddress( tertiaryAddress );
		} );
		await act( async () => {
			await inputAddress( { country: primaryAddress.country } );
		} );
		expect( screen.getByLabelText( stateRegExp ).value ).toBe( '' );
	} );
} );
