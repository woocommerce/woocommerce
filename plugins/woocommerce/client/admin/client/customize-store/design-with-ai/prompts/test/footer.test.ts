/**
 * Internal dependencies
 */
import { footerValidator } from '..';

describe( 'footerValidator', () => {
	it( 'should validate when footer is part of the allowed list', () => {
		const validFooter = { slug: 'woocommerce-blocks/footer-large' };
		expect( () => footerValidator.parse( validFooter ) ).not.toThrow();
	} );

	it( 'should not validate when footer is not part of the allowed list', () => {
		const invalidFooter = {
			slug: 'woocommerce-blocks/footer-large-invalid',
		};
		expect( () => footerValidator.parse( invalidFooter ) )
			.toThrowErrorMatchingInlineSnapshot( `
		"[
		  {
		    \\"code\\": \\"custom\\",
		    \\"message\\": \\"Footer not part of allowed list\\",
		    \\"path\\": [
		      \\"slug\\"
		    ]
		  }
		]"
	` );
	} );

	it( 'should not validate when slug is not a string', () => {
		const invalidType = { slug: 123 };
		expect( () => footerValidator.parse( invalidType ) )
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
