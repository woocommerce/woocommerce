/**
 * Internal dependencies
 */
import { fontChoiceValidator } from '..';

describe( 'fontChoiceValidator', () => {
	it( 'should validate when font choice is part of the allowed list', () => {
		const validFontChoice = { pair_name: 'Montserrat + Arvo' };
		expect( () =>
			fontChoiceValidator.parse( validFontChoice )
		).not.toThrow();
	} );

	it( 'should not validate when font choice is not part of the allowed list', () => {
		const invalidFontChoice = { pair_name: 'Comic Sans' };
		expect( () => fontChoiceValidator.parse( invalidFontChoice ) )
			.toThrowErrorMatchingInlineSnapshot( `
		"[
		  {
		    \\"code\\": \\"custom\\",
		    \\"message\\": \\"Font choice not part of allowed list\\",
		    \\"path\\": [
		      \\"pair_name\\"
		    ]
		  }
		]"
	` );
	} );

	it( 'should not validate when pair_name is not a string', () => {
		const invalidType = { pair_name: 123 };
		expect( () => fontChoiceValidator.parse( invalidType ) )
			.toThrowErrorMatchingInlineSnapshot( `
		"[
		  {
		    \\"code\\": \\"invalid_type\\",
		    \\"expected\\": \\"string\\",
		    \\"received\\": \\"number\\",
		    \\"path\\": [
		      \\"pair_name\\"
		    ],
		    \\"message\\": \\"Expected string, received number\\"
		  }
		]"
	` );
	} );
} );
