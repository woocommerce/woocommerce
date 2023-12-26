/**
 * Internal dependencies
 */
import { headerValidator } from '..';

describe( 'headerValidator', () => {
	it( 'should validate when header is part of the allowed list', () => {
		const validHeader = { slug: 'woocommerce-blocks/header-large' };
		expect( () => headerValidator.parse( validHeader ) ).not.toThrow();
	} );

	it( 'should not validate when header is not part of the allowed list', () => {
		const invalidHeader = {
			slug: 'woocommerce-blocks/header-large-invalid',
		};
		expect( () => headerValidator.parse( invalidHeader ) )
			.toThrowErrorMatchingInlineSnapshot( `
		"[
		  {
		    \\"code\\": \\"custom\\",
		    \\"message\\": \\"Header not part of allowed list\\",
		    \\"path\\": [
		      \\"slug\\"
		    ]
		  }
		]"
	` );
	} );

	it( 'should not validate when slug is not a string', () => {
		const invalidType = { slug: 123 };
		expect( () => headerValidator.parse( invalidType ) )
			.toThrowErrorMatchingInlineSnapshot( `
		"[
		  {
		    \\"code\\": \\"invalid_type\\",
		    \\"expected\\": \\"string\\",
		    \\"received\\": \\"number\\",
		    \\"path\\": [
		      \\"slug\\"
		    ],
		    \\"message\\": \\"Expected string, received number\\"
		  }
		]"
	` );
	} );
} );
