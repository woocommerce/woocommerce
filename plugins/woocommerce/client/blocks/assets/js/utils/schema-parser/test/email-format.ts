/**
 * Internal dependencies
 */
import { schemaParser } from '../index';

describe( 'Schema Parser', () => {
	describe( 'email format validation', () => {
		const validateEmail = ( email: string ) => {
			const schema = {
				type: 'object',
				properties: {
					email: { type: 'string', format: 'email' },
				},
			};
			const validate = schemaParser.compile( schema );
			return validate( { email } );
		};

		it.each( [
			[ 'test@example.com', true ],
			[ 'test.name@example.com', true ],
			[ 'test+label@example.com', true ],
			[ 'test@sub.example.com', true ],
			[ 'TEST@EXAMPLE.COM', true ],
			[ 'user123@example.co.uk', true ],
		] )( 'should validate valid email %s', ( email, expected ) => {
			expect( validateEmail( email ) ).toBe( expected );
		} );

		it.each( [
			[ 'test@localhost', false ],
			[ 'test@example', false ],
			[ 'test@.com', false ],
			[ 'test@com', false ],
			[ 'test.@example.com', false ],
			[ '.test@example.com', false ],
			[ 'test@example..com', false ],
			[ 'test..name@example.com', false ],
			[ '@example.com', false ],
			[ 'test@', false ],
			[ 'test', false ],
		] )( 'should invalidate invalid email %s', ( email, expected ) => {
			expect( validateEmail( email ) ).toBe( expected );
		} );
	} );
} );
