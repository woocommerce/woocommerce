/**
 * Internal dependencies
 */
import { defaultColorPalette } from '..';

describe( 'colorPairing.responseValidation', () => {
	it( 'should validate a correct color palette', () => {
		const validPalette = {
			name: 'Ancient Bronze',
			primary: '#11163d',
			secondary: '#8C8369',
			foreground: '#11163d',
			background: '#ffffff',
		};

		const parsedResult =
			defaultColorPalette.responseValidation( validPalette );
		expect( parsedResult ).toEqual( validPalette );
	} );

	it( 'should fail for an incorrect name', () => {
		const invalidPalette = {
			name: 'Invalid Name',
			primary: '#11163d',
			secondary: '#8C8369',
			foreground: '#11163d',
			background: '#ffffff',
		};
		expect( () => defaultColorPalette.responseValidation( invalidPalette ) )
			.toThrowErrorMatchingInlineSnapshot( `
		"[
		  {
		    \\"code\\": \\"custom\\",
		    \\"message\\": \\"Color palette not part of allowed list\\",
		    \\"path\\": [
		      \\"name\\"
		    ]
		  }
		]"
	` );
	} );

	it( 'should fail for an invalid primary color', () => {
		const invalidPalette = {
			name: 'Ancient Bronze',
			primary: 'invalidColor',
			secondary: '#8C8369',
			foreground: '#11163d',
			background: '#ffffff',
		};
		expect( () => defaultColorPalette.responseValidation( invalidPalette ) )
			.toThrowErrorMatchingInlineSnapshot( `
		"[
		  {
		    \\"validation\\": \\"regex\\",
		    \\"code\\": \\"invalid_string\\",
		    \\"message\\": \\"Invalid primary color\\",
		    \\"path\\": [
		      \\"primary\\"
		    ]
		  }
		]"
	` );
	} );

	it( 'should fail for an invalid secondary color', () => {
		const invalidPalette = {
			name: 'Ancient Bronze',
			primary: '#11163d',
			secondary: 'invalidColor',
			foreground: '#11163d',
			background: '#ffffff',
		};
		expect( () => defaultColorPalette.responseValidation( invalidPalette ) )
			.toThrowErrorMatchingInlineSnapshot( `
		"[
		  {
		    \\"validation\\": \\"regex\\",
		    \\"code\\": \\"invalid_string\\",
		    \\"message\\": \\"Invalid secondary color\\",
		    \\"path\\": [
		      \\"secondary\\"
		    ]
		  }
		]"
	` );
	} );

	it( 'should fail for an invalid foreground color', () => {
		const invalidPalette = {
			name: 'Ancient Bronze',
			primary: '#11163d',
			secondary: '11163d',
			foreground: '#invalid_color',
			background: '#ffffff',
		};
		expect( () => defaultColorPalette.responseValidation( invalidPalette ) )
			.toThrowErrorMatchingInlineSnapshot( `
		"[
		  {
		    \\"validation\\": \\"regex\\",
		    \\"code\\": \\"invalid_string\\",
		    \\"message\\": \\"Invalid secondary color\\",
		    \\"path\\": [
		      \\"secondary\\"
		    ]
		  },
		  {
		    \\"validation\\": \\"regex\\",
		    \\"code\\": \\"invalid_string\\",
		    \\"message\\": \\"Invalid foreground color\\",
		    \\"path\\": [
		      \\"foreground\\"
		    ]
		  }
		]"
	` );
	} );

	it( 'should fail for an invalid background color', () => {
		const invalidPalette = {
			name: 'Ancient Bronze',
			primary: '#11163d',
			secondary: '#11163d',
			foreground: '#11163d',
			background: '#fffff',
		};
		expect( () => defaultColorPalette.responseValidation( invalidPalette ) )
			.toThrowErrorMatchingInlineSnapshot( `
		"[
		  {
		    \\"validation\\": \\"regex\\",
		    \\"code\\": \\"invalid_string\\",
		    \\"message\\": \\"Invalid background color\\",
		    \\"path\\": [
		      \\"background\\"
		    ]
		  }
		]"
	` );
	} );
} );
