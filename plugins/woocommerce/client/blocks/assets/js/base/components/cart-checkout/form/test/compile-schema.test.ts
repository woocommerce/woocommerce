/**
 * External dependencies
 */
import Ajv from 'ajv';

/**
 * Internal dependencies
 */
import { compileSchema, hasUncompilablePattern } from '../compile-schema';

const createParser = () =>
	new Ajv( {
		allErrors: true,
		$data: true,
		strictSchema: false,
		strict: false,
		messages: true,
	} );

const schemaWithPattern = ( pattern: string ) => ( {
	type: 'object',
	properties: {
		checkout: {
			type: 'object',
			properties: {
				additional_fields: {
					type: 'object',
					properties: {
						'member/number': { type: 'string', pattern },
					},
				},
			},
		},
	},
} );

describe( 'compileSchema', () => {
	let consoleError: jest.SpyInstance;

	beforeEach( () => {
		consoleError = jest
			.spyOn( console, 'error' )
			.mockImplementation( () => {} );
	} );

	afterEach( () => {
		consoleError.mockRestore();
	} );

	it( 'compiles a schema whose patterns the browser accepts', () => {
		const validate = compileSchema(
			createParser(),
			schemaWithPattern( '^[0-9]{3}-[0-9]{4}$' )
		);

		expect( validate ).not.toBeNull();
		expect(
			validate?.( {
				checkout: {
					additional_fields: { 'member/number': '123-4567' },
				},
			} )
		).toBe( true );
		expect( consoleError ).not.toHaveBeenCalled();
	} );

	it( 'returns null instead of throwing for a PCRE-only pattern', () => {
		// What the browser rejects: an escaped hyphen outside a character class.
		const validate = compileSchema(
			createParser(),
			schemaWithPattern( '^[0-9]{3}\\-[0-9]{4}$' )
		);

		expect( validate ).toBeNull();
	} );

	it( 'logs the offending pattern once, however often the schema is compiled', () => {
		const parser = createParser();
		const pattern = '^[0-9]{2}\\-[0-9]{2}$';
		const schema = schemaWithPattern( pattern );

		compileSchema( parser, schema );
		compileSchema( parser, schema );
		compileSchema( parser, schema );

		expect( consoleError ).toHaveBeenCalledTimes( 2 );

		const [ message, patterns ] = consoleError.mock.calls[ 0 ];
		expect( message ).toContain( 'ECMA-262' );
		expect( patterns ).toEqual( [ pattern ] );
	} );

	it( 'keeps validating the fields whose patterns are fine', () => {
		const parser = createParser();

		compileSchema( parser, schemaWithPattern( '(?i)^abc$' ) );

		const validate = compileSchema(
			parser,
			schemaWithPattern( '^[0-9]{3}-[0-9]{4}$' )
		);

		expect(
			validate?.( {
				checkout: { additional_fields: { 'member/number': 'nope' } },
			} )
		).toBe( false );
	} );
} );

describe( 'hasUncompilablePattern', () => {
	it( 'is false for a schema whose patterns the browser accepts', () => {
		expect(
			hasUncompilablePattern( schemaWithPattern( '^[0-9]{3}-[0-9]{4}$' ) )
		).toBe( false );
	} );

	it( 'is true for a schema with a PCRE-only pattern', () => {
		expect(
			hasUncompilablePattern(
				schemaWithPattern( '^[0-9]{3}\\-[0-9]{4}$' )
			)
		).toBe( true );
	} );

	it( 'does not read a $data pointer as a pattern', () => {
		// `pattern: { $data: '1/member/number' }` holds a pointer, not a pattern.
		// The value it resolves to is checked by AJV at validation time.
		const schema = {
			type: 'object',
			properties: {
				'member/number': {
					type: 'string',
					pattern: { $data: '1/other' },
				},
			},
		};

		expect( hasUncompilablePattern( schema ) ).toBe( false );
	} );

	it( 'reports one bad field without hiding a good one', () => {
		const schema = {
			type: 'object',
			properties: {
				'ns/good': { type: 'string', pattern: '^[0-9]{5}$' },
				'ns/bad': { type: 'string', pattern: '^[0-9]{3}\\-[0-9]{4}$' },
			},
		};

		expect( hasUncompilablePattern( schema ) ).toBe( true );
		// The good field on its own is still compilable, which is what lets the
		// caller drop the bad field and keep the good one in browser validation.
		expect( hasUncompilablePattern( schema.properties[ 'ns/good' ] ) ).toBe(
			false
		);
	} );
} );
