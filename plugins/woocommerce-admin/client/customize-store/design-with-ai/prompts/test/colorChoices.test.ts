/**
 * Internal dependencies
 */
import { colorPaletteValidator, defaultColorPalette } from '..';

describe( 'colorPaletteValidator', () => {
	it( 'should validate a correct color palette', () => {
		const validPalette = {
			name: 'Ancient Bronze',
			primary: '#11163d',
			secondary: '#8C8369',
			foreground: '#11163d',
			background: '#ffffff',
			lookAndFeel: [ 'Contemporary', 'Classic' ],
		};

		const parsedResult = colorPaletteValidator.parse( validPalette );
		expect( parsedResult ).toEqual( validPalette );
	} );

	it( 'should fail for an incorrect name', () => {
		const invalidPalette = {
			name: 'Invalid Name',
			primary: '#11163d',
			secondary: '#8C8369',
			foreground: '#11163d',
			background: '#ffffff',
			lookAndFeel: [ 'Contemporary', 'Classic' ],
		};
		expect( () => colorPaletteValidator.parse( invalidPalette ) )
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
			lookAndFeel: [ 'Contemporary', 'Classic' ],
		};
		expect( () => colorPaletteValidator.parse( invalidPalette ) )
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
			lookAndFeel: [ 'Contemporary', 'Classic' ],
		};
		expect( () => colorPaletteValidator.parse( invalidPalette ) )
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
			lookAndFeel: [ 'Contemporary', 'Classic' ],
		};
		expect( () => colorPaletteValidator.parse( invalidPalette ) )
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
			lookAndFeel: [ 'Contemporary', 'Classic' ],
		};
		expect( () => colorPaletteValidator.parse( invalidPalette ) )
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

	it( 'should fail for an invalid Look and Feel', () => {
		const invalidPalette = {
			name: 'Ancient Bronze',
			primary: '#11163d',
			secondary: '#11163d',
			foreground: '#11163d',
			background: '#ffffff',
			lookAndFeel: [ 'Contemporary', 'Classic', 'Invalid Look' ],
		};
		expect( () => colorPaletteValidator.parse( invalidPalette ) )
			.toThrowErrorMatchingInlineSnapshot( `
		"[
		  {
		    \\"received\\": \\"Invalid Look\\",
		    \\"code\\": \\"invalid_enum_value\\",
		    \\"options\\": [
		      \\"Contemporary\\",
		      \\"Classic\\",
		      \\"Bold\\"
		    ],
		    \\"path\\": [
		      \\"lookAndFeel\\",
		      2
		    ],
		    \\"message\\": \\"Invalid enum value. Expected 'Contemporary' | 'Classic' | 'Bold', received 'Invalid Look'\\"
		  }
		]"
	` );
	} );
} );

describe( 'colorPaletteResponseValidator', () => {
	const validPalette = {
		default: 'Ancient Bronze',
		bestColors: [
			'Canary',
			'Cinder',
			'Rustic Rosewood',
			'Lightning',
			'Midnight Citrus',
			'Purple Twilight',
			'Fuchsia',
			'Charcoal',
		],
	};
	it( 'should validate a correct color palette response', () => {
		const parsedResult =
			defaultColorPalette.responseValidation( validPalette );
		expect( parsedResult ).toEqual( validPalette );
	} );

	it( 'should fail if array contains invalid color', () => {
		const invalidPalette = {
			default: validPalette.default,
			bestColors: validPalette.bestColors
				.slice( 0, 7 )
				.concat( 'Invalid Color' ),
		};
		expect( () => defaultColorPalette.responseValidation( invalidPalette ) )
			.toThrowErrorMatchingInlineSnapshot( `
		"[
		  {
		    \\"code\\": \\"custom\\",
		    \\"message\\": \\"Color palette not part of allowed list\\",
		    \\"path\\": [
		      \\"bestColors\\",
		      7
		    ]
		  }
		]"
	` );
	} );

	it( 'should fail if bestColors property is missing', () => {
		const invalidPalette = {
			default: 'Ancient Bronze',
		};
		expect( () => defaultColorPalette.responseValidation( invalidPalette ) )
			.toThrowErrorMatchingInlineSnapshot( `
		"[
		  {
		    \\"code\\": \\"invalid_type\\",
		    \\"expected\\": \\"array\\",
		    \\"received\\": \\"undefined\\",
		    \\"path\\": [
		      \\"bestColors\\"
		    ],
		    \\"message\\": \\"Required\\"
		  }
		]"
	` );
	} );
	it( 'should fail if default property is missing', () => {
		const invalidPalette = {
			bestColors: validPalette.bestColors,
		};
		expect( () => defaultColorPalette.responseValidation( invalidPalette ) )
			.toThrowErrorMatchingInlineSnapshot( `
		"[
		  {
		    \\"code\\": \\"invalid_type\\",
		    \\"expected\\": \\"string\\",
		    \\"received\\": \\"undefined\\",
		    \\"path\\": [
		      \\"default\\"
		    ],
		    \\"message\\": \\"Required\\"
		  }
		]"
	` );
	} );

	it( 'should fail if bestColors array is not of length 8', () => {
		const invalidPalette = {
			default: 'Ancient Bronze',
			bestColors: validPalette.bestColors.slice( 0, 7 ),
		};
		expect( () => defaultColorPalette.responseValidation( invalidPalette ) )
			.toThrowErrorMatchingInlineSnapshot( `
		"[
		  {
		    \\"code\\": \\"too_small\\",
		    \\"minimum\\": 8,
		    \\"type\\": \\"array\\",
		    \\"inclusive\\": true,
		    \\"exact\\": true,
		    \\"message\\": \\"Array must contain exactly 8 element(s)\\",
		    \\"path\\": [
		      \\"bestColors\\"
		    ]
		  }
		]"
	` );
	} );

	it( 'should fail if there are duplicate colors', () => {
		const invalidPalette = {
			default: 'Ancient Bronze',
			bestColors: Array( 8 ).fill( 'Ancient Bronze' ),
		};
		expect( () => defaultColorPalette.responseValidation( invalidPalette ) )
			.toThrowErrorMatchingInlineSnapshot( `
		"[
		  {
		    \\"code\\": \\"custom\\",
		    \\"message\\": \\"Color palette names must be unique\\",
		    \\"path\\": []
		  }
		]"
	` );
	} );
} );
