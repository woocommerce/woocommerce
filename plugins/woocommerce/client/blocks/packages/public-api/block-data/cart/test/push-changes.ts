/**
 * External dependencies
 */
import * as wpDataFunctions from '@wordpress/data';
import { cartStore, validationStore } from '@woocommerce/block-data';

/**
 * Internal dependencies
 */
import { pushChanges } from '../push-changes';
import { STORE_KEY as VALIDATION_STORE_KEY } from '../../validation/constants';

let updateCustomerDataMock = jest.fn();
const getValidationErrorMock = jest.fn().mockReturnValue( undefined );
let getCustomerDataMock = jest.fn().mockReturnValue( {
	billingAddress: {
		first_name: 'John',
		last_name: 'Doe',
		address_1: '123 Main St',
		address_2: '',
		city: 'New York',
		state: 'NY',
		postcode: '10001',
		country: 'US',
		email: 'john.doe@mail.com',
		phone: '555-555-5555',
	},
	shippingAddress: {
		first_name: 'John',
		last_name: 'Doe',
		address_1: '123 Main St',
		address_2: '',
		city: 'New York',
		state: 'NY',
		postcode: '10001',
		country: 'US',
		phone: '555-555-5555',
	},
} );

// Mocking select and dispatch here so we can control the actions/selectors used in pushChanges.
jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	__esModule: true,
	select: jest.fn(),
	dispatch: jest.fn(),
} ) );

jest.mock( '@woocommerce/utils', () => ( {
	isSiteEditorPage: jest.fn().mockReturnValue( true ),
} ) );

// Mocking processErrorResponse because we don't actually care about processing the error response, we just don't want
// pushChanges to throw an error.
jest.mock( '../../utils', () => ( {
	...jest.requireActual( '../../utils' ),
	__esModule: true,
	processErrorResponse: jest.fn(),
} ) );

// Mocking updatePaymentMethods because this uses the mocked debounce earlier, and causes an error. Moreover, we don't
// need to update payment methods, they are not relevant to the tests in this file.
jest.mock( '../update-payment-methods', () => ( {
	debouncedUpdatePaymentMethods: jest.fn(),
	updatePaymentMethods: jest.fn(),
} ) );

async function resetToInitialAddressMock() {
	pushChanges( false );
	await Promise.resolve();
	await Promise.resolve();
	updateCustomerDataMock.mockReset();
	updateCustomerDataMock.mockResolvedValue( jest.fn() );

	getCustomerDataMock = jest.fn().mockReturnValue( {
		billingAddress: {
			first_name: 'John',
			last_name: 'Doe',
			address_1: '123 Main St',
			address_2: '',
			city: 'New York',
			state: 'NY',
			postcode: '10001',
			country: 'US',
			email: 'john.doe@mail.com',
			phone: '555-555-5555',
		},
		shippingAddress: {
			first_name: 'John',
			last_name: 'Doe',
			address_1: '123 Main St',
			address_2: '',
			city: 'New York',
			state: 'NY',
			postcode: '10001',
			country: 'US',
			phone: '555-555-5555',
		},
	} );
	pushChanges( false );
	await Promise.resolve();
	await Promise.resolve();
	updateCustomerDataMock.mockClear();
}

describe( 'pushChanges', () => {
	beforeAll( () => {
		wpDataFunctions.select.mockImplementation(
			( storeNameOrDescriptor: unknown ) => {
				if ( storeNameOrDescriptor === cartStore ) {
					return {
						...jest
							.requireActual( '@wordpress/data' )
							.select( storeNameOrDescriptor ),
						hasFinishedResolution: () => true,
						getCustomerData: getCustomerDataMock,
					};
				}
				if (
					storeNameOrDescriptor === validationStore ||
					storeNameOrDescriptor === VALIDATION_STORE_KEY
				) {
					return {
						...jest
							.requireActual( '@wordpress/data' )
							.select( storeNameOrDescriptor ),
						getValidationError: getValidationErrorMock,
					};
				}
				return jest
					.requireActual( '@wordpress/data' )
					.select( storeNameOrDescriptor );
			}
		);
		wpDataFunctions.dispatch.mockImplementation(
			( storeNameOrDescriptor: unknown ) => {
				if ( storeNameOrDescriptor === cartStore ) {
					return {
						...jest
							.requireActual( '@wordpress/data' )
							.dispatch( storeNameOrDescriptor ),
						updateCustomerData: updateCustomerDataMock,
					};
				}
				return jest
					.requireActual( '@wordpress/data' )
					.dispatch( storeNameOrDescriptor );
			}
		);
	} );
	beforeEach( async () => {
		getValidationErrorMock.mockReset().mockReturnValue( undefined );
		await resetToInitialAddressMock();
	} );

	it( 'Pushes a billing country change when state and postcode are automatically cleared', async () => {
		const customerData = getCustomerDataMock();
		getCustomerDataMock.mockReturnValue( {
			...customerData,
			billingAddress: {
				...customerData.billingAddress,
				country: 'FR',
			},
		} );
		getValidationErrorMock.mockImplementation( ( errorId ) =>
			[ 'billing_state', 'billing_postcode' ].includes( errorId )
				? { message: 'Required field', hidden: true }
				: undefined
		);

		pushChanges( false );

		await expect( updateCustomerDataMock ).toHaveBeenCalledWith(
			{
				billing_address: {
					...customerData.billingAddress,
					country: 'FR',
					state: '',
					postcode: '',
				},
			},
			true,
			false
		);
	} );

	it( 'Pushes a shipping country change when state and postcode are automatically cleared', async () => {
		const customerData = getCustomerDataMock();
		getCustomerDataMock.mockReturnValue( {
			...customerData,
			shippingAddress: {
				...customerData.shippingAddress,
				country: 'FR',
			},
		} );
		getValidationErrorMock.mockImplementation( ( errorId ) =>
			[ 'shipping_state', 'shipping_postcode' ].includes( errorId )
				? { message: 'Required field', hidden: true }
				: undefined
		);

		pushChanges( false );

		await expect( updateCustomerDataMock ).toHaveBeenCalledWith(
			{
				shipping_address: {
					...customerData.shippingAddress,
					country: 'FR',
					state: '',
					postcode: '',
				},
			},
			true,
			true
		);
	} );

	it( 'Does not push a country change with another invalid dirty field', () => {
		const customerData = getCustomerDataMock();
		getCustomerDataMock.mockReturnValue( {
			...customerData,
			billingAddress: {
				...customerData.billingAddress,
				country: 'FR',
				email: 'invalid-email',
			},
		} );
		getValidationErrorMock.mockImplementation( ( errorId ) =>
			[ 'billing_state', 'billing_postcode', 'billing_email' ].includes(
				errorId
			)
				? { message: 'Invalid field', hidden: true }
				: undefined
		);

		pushChanges( false );

		expect( updateCustomerDataMock ).not.toHaveBeenCalled();
	} );

	it( 'Does not push a country change with a non-empty invalid postcode', () => {
		const customerData = getCustomerDataMock();
		getCustomerDataMock.mockReturnValue( {
			...customerData,
			billingAddress: {
				...customerData.billingAddress,
				country: 'FR',
				postcode: 'INVALID',
			},
		} );
		getValidationErrorMock.mockImplementation( ( errorId ) =>
			[ 'billing_state', 'billing_postcode' ].includes( errorId )
				? { message: 'Invalid field', hidden: true }
				: undefined
		);

		pushChanges( false );

		expect( updateCustomerDataMock ).not.toHaveBeenCalled();
	} );

	it( 'Keeps props dirty if data did not persist due to an error', async () => {
		// When first updating the customer data, we want to simulate a rejected update.
		updateCustomerDataMock = jest.fn().mockRejectedValue( 'error' );

		// Run this without changing anything because the first run does not push data (the first run is populating what was received on page load).
		pushChanges( false );

		// Mock the returned value of `getCustomerData` to simulate a change in the shipping address.
		getCustomerDataMock.mockReturnValue( {
			billingAddress: {
				first_name: 'John',
				last_name: 'Doe',
				address_1: '123 Main St',
				address_2: '',
				city: 'New York',
				state: 'NY',
				postcode: '10001',
				country: 'US',
				email: 'john.doe@mail.com',
				phone: '555-555-5555',
			},
			shippingAddress: {
				first_name: 'John',
				last_name: 'Doe',
				address_1: '123 Main St',
				address_2: '',
				city: 'Houston',
				state: 'TX',
				postcode: 'ABCDEF',
				country: 'US',
				phone: '555-555-5555',
			},
		} );

		// Push these changes to the server, the `updateCustomerData` mock is set to reject (in the original mock at the top of the file), to simulate a server error.
		pushChanges( false );

		// Check that the mock was called with full address data.
		await expect( updateCustomerDataMock ).toHaveBeenCalledWith(
			{
				shipping_address: {
					first_name: 'John',
					last_name: 'Doe',
					address_1: '123 Main St',
					address_2: '',
					city: 'Houston',
					state: 'TX',
					postcode: 'ABCDEF',
					country: 'US',
					phone: '555-555-5555',
				},
			},
			true,
			true // because the shipping rate impacting field was changed
		);

		// This assertion is required to ensure the async `catch` block in `pushChanges` is done executing and all side effects finish.
		await expect( updateCustomerDataMock ).toHaveReturned();

		// Reset the mock so that it no longer rejects.
		updateCustomerDataMock.mockReset();
		updateCustomerDataMock.mockResolvedValue( jest.fn() );

		// Simulate the user updating the postcode only.
		getCustomerDataMock.mockReturnValue( {
			billingAddress: {
				first_name: 'John',
				last_name: 'Doe',
				address_1: '123 Main St',
				address_2: '',
				city: 'New York',
				state: 'NY',
				postcode: '10001',
				country: 'US',
				email: 'john.doe@mail.com',
				phone: '555-555-5555',
			},
			shippingAddress: {
				first_name: 'John',
				last_name: 'Doe',
				address_1: '123 Main St',
				address_2: '',
				city: 'Houston',
				state: 'TX',
				postcode: '77058',
				country: 'US',
				phone: '555-555-5555',
			},
		} );

		// Although only one property was updated between calls, we should expect City, State, and Postcode to be pushed
		// to the server because the previous push failed when they were originally changed.
		pushChanges( false );

		await expect( updateCustomerDataMock ).toHaveBeenLastCalledWith(
			{
				shipping_address: {
					first_name: 'John',
					last_name: 'Doe',
					address_1: '123 Main St',
					address_2: '',
					city: 'Houston',
					state: 'TX',
					postcode: '77058',
					country: 'US',
					phone: '555-555-5555',
				},
			},
			true,
			true // because the shipping rate impacting field was changed
		);
	} );

	it( 'Does not push the shipping address if the billing address is changed', async () => {
		// Simulate the user updating the billing postcode only.
		getCustomerDataMock.mockReturnValue( {
			billingAddress: {
				first_name: 'John',
				last_name: 'Doe',
				address_1: '123 Main St',
				address_2: '',
				city: 'New York',
				state: 'NY',
				postcode: '10002',
				country: 'US',
				email: 'john.doe@mail.com',
				phone: '555-555-5555',
			},
			shippingAddress: {
				first_name: 'John',
				last_name: 'Doe',
				address_1: '123 Main St',
				address_2: '',
				city: 'New York',
				state: 'NY',
				postcode: '10001',
				country: 'US',
				phone: '555-555-5555',
			},
		} );

		pushChanges( false );

		await expect( updateCustomerDataMock ).toHaveBeenLastCalledWith(
			{
				billing_address: {
					first_name: 'John',
					last_name: 'Doe',
					address_1: '123 Main St',
					address_2: '',
					city: 'New York',
					state: 'NY',
					postcode: '10002',
					country: 'US',
					email: 'john.doe@mail.com',
					phone: '555-555-5555',
				},
			},
			true,
			false // because no shipping rates impacting fields are changed
		);
	} );

	it( 'Does not push the billing address if the shipping address is changed', async () => {
		getCustomerDataMock.mockReturnValue( {
			billingAddress: {
				first_name: 'John',
				last_name: 'Doe',
				address_1: '123 Main St',
				address_2: '',
				city: 'New York',
				state: 'NY',
				postcode: '10001',
				country: 'US',
				email: 'john.doe@mail.com',
				phone: '555-555-5555',
			},
			// simulate the user updating the shipping postcode only
			shippingAddress: {
				first_name: 'John',
				last_name: 'Doe',
				address_1: '123 Main St',
				address_2: '',
				city: 'New York',
				state: 'NY',
				postcode: '10002',
				country: 'US',
				phone: '555-555-5555',
			},
		} );

		pushChanges( false );

		await expect( updateCustomerDataMock ).toHaveBeenLastCalledWith(
			{
				shipping_address: {
					first_name: 'John',
					last_name: 'Doe',
					address_1: '123 Main St',
					address_2: '',
					city: 'New York',
					state: 'NY',
					postcode: '10002',
					country: 'US',
					phone: '555-555-5555',
				},
			},
			true,
			true // because the shipping rate impacting field was changed
		);
	} );

	it( 'Pushes both the billing & shipping address if both are changed', async () => {
		getCustomerDataMock.mockReturnValue( {
			billingAddress: {
				first_name: 'John',
				last_name: 'Doe',
				address_1: '123 Main St',
				address_2: '',
				city: 'New York',
				state: 'NY',
				postcode: '10002',
				country: 'US',
				email: 'john.doe@mail.com',
				phone: '555-555-5555',
			},
			// simulate the user updating the shipping postcode only
			shippingAddress: {
				first_name: 'John',
				last_name: 'Doe',
				address_1: '123 Main St',
				address_2: '',
				city: 'New York',
				state: 'NY',
				postcode: '10002',
				country: 'US',
				phone: '555-555-5555',
			},
		} );

		pushChanges( false );

		await expect( updateCustomerDataMock ).toHaveBeenLastCalledWith(
			{
				billing_address: {
					first_name: 'John',
					last_name: 'Doe',
					address_1: '123 Main St',
					address_2: '',
					city: 'New York',
					state: 'NY',
					postcode: '10002',
					country: 'US',
					email: 'john.doe@mail.com',
					phone: '555-555-5555',
				},
				shipping_address: {
					first_name: 'John',
					last_name: 'Doe',
					address_1: '123 Main St',
					address_2: '',
					city: 'New York',
					state: 'NY',
					postcode: '10002',
					country: 'US',
					phone: '555-555-5555',
				},
			},
			true,
			true // because a shipping rate impacting field was changed
		);
	} );

	it( 'Pushes the data when non-shipping impacting rates are changed without flagging haveAddressFieldsForShippingRatesChanged', async () => {
		// Simulate the user updating all non-shipping impacting fields
		getCustomerDataMock.mockReturnValue( {
			billingAddress: {
				first_name: 'John - changed',
				last_name: 'Doe - changed',
				address_1: '123 Main St - changed',
				address_2: ' - changed',
				city: 'New York - changed',
				state: 'NY - changed',
				postcode: '10001 - changed',
				country: 'US - changed',
				email: 'john.doe@mail.com - changed',
				phone: '555-555-5555 - changed',
			},
			// simulate the user updating the shipping postcode only
			shippingAddress: {
				first_name: 'John - changed',
				last_name: 'Doe - changed',
				address_1: '123 Main St - changed',
				address_2: ' - changed',
				city: 'New York',
				state: 'NY',
				postcode: '10001',
				country: 'US',
				phone: '555-555-5555 - changed',
			},
		} );

		pushChanges( false );

		await expect( updateCustomerDataMock ).toHaveBeenLastCalledWith(
			{
				billing_address: {
					first_name: 'John - changed',
					last_name: 'Doe - changed',
					address_1: '123 Main St - changed',
					address_2: ' - changed',
					city: 'New York - changed',
					state: 'NY - changed',
					postcode: '10001 - changed',
					country: 'US - changed',
					email: 'john.doe@mail.com - changed',
					phone: '555-555-5555 - changed',
				},
				shipping_address: {
					first_name: 'John - changed',
					last_name: 'Doe - changed',
					address_1: '123 Main St - changed',
					address_2: ' - changed',
					city: 'New York',
					state: 'NY',
					postcode: '10001',
					country: 'US',
					phone: '555-555-5555 - changed',
				},
			},
			true,
			false // because no shipping rate impacting fields are changed
		);
	} );
} );
