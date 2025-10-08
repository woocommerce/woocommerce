/**
 * External dependencies
 */
import { FieldValidationStatus } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import {
	getValidationErrorId,
	getValidationError,
	getValidationErrors,
	hasValidationErrors,
} from '../selectors';

describe( 'Validation selectors', () => {
	it( 'Gets the validation error', () => {
		const state: Record< string, FieldValidationStatus > = {
			validationError: {
				message: 'This is a test message',
				hidden: false,
			},
		};
		const validationError = getValidationError( state, 'validationError' );
		expect( validationError ).toEqual( {
			message: 'This is a test message',
			hidden: false,
		} );
	} );

	it( 'Gets the generated validation error ID', () => {
		const state: Record< string, FieldValidationStatus > = {
			validationError: {
				message: 'This is a test message',
				hidden: false,
			},
		};
		const validationErrorID = getValidationErrorId(
			state,
			'validationError'
		);
		expect( validationErrorID ).toEqual( `validate-error-validationError` );
	} );

	it( 'Checks if state has any validation errors', () => {
		const state: Record< string, FieldValidationStatus > = {
			validationError: {
				message: 'This is a test message',
				hidden: false,
			},
		};
		const validationErrors = hasValidationErrors( state );
		expect( validationErrors ).toEqual( true );
		const stateWithNoErrors: Record< string, FieldValidationStatus > = {};
		const stateWithNoErrorsCheckResult =
			hasValidationErrors( stateWithNoErrors );
		expect( stateWithNoErrorsCheckResult ).toEqual( false );
	} );

	it( 'Gets all validation errors', () => {
		const state: Record< string, FieldValidationStatus > = {
			billing_first_name: {
				message: 'First name is required',
				hidden: false,
			},
			billing_last_name: {
				message: 'Last name is required',
				hidden: true,
			},
			shipping_city: {
				message: 'City is required',
				hidden: false,
			},
		};
		const allValidationErrors = getValidationErrors( state );
		expect( allValidationErrors ).toEqual( state );
		expect( allValidationErrors ).toHaveProperty( 'billing_first_name' );
		expect( allValidationErrors ).toHaveProperty( 'billing_last_name' );
		expect( allValidationErrors ).toHaveProperty( 'shipping_city' );
	} );

	it( 'Gets empty object when no validation errors exist', () => {
		const state: Record< string, FieldValidationStatus > = {};
		const allValidationErrors = getValidationErrors( state );
		expect( allValidationErrors ).toEqual( {} );
		expect( Object.keys( allValidationErrors ) ).toHaveLength( 0 );
	} );
} );
