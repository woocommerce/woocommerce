/**
 * Internal dependencies
 */
import { validatePhoneNumber } from '../validation';

describe( 'PhoneNumberInput Validation', () => {
	it( 'should return true for a valid US phone number', () => {
		expect( validatePhoneNumber( '+12345678901', 'US' ) ).toBe( true );
	} );

	it( 'should return true for a valid phone number with country guessed from the number', () => {
		expect( validatePhoneNumber( '+447123456789' ) ).toBe( true );
	} );

	it( 'should return false for a phone number with invalid format', () => {
		expect( validatePhoneNumber( '1234567890', 'US' ) ).toBe( false );
	} );

	it( 'should return false for a phone number with incorrect country', () => {
		expect( validatePhoneNumber( '+12345678901', 'GB' ) ).toBe( false );
	} );

	it( 'should return false for a phone number with incorrect length', () => {
		expect( validatePhoneNumber( '+123456', 'US' ) ).toBe( false );
	} );

	it( 'should return false for a phone number with incorrect start', () => {
		expect( validatePhoneNumber( '+11234567890', 'US' ) ).toBe( false );
	} );
} );
