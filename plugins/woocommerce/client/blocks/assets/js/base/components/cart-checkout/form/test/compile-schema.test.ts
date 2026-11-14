/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';
import { useSchemaParser } from '@woocommerce/base-hooks';
import type { KeyedFormFields } from '@woocommerce/settings';
import Ajv from 'ajv';

/**
 * Internal dependencies
 */
import { compileSchema, hasUncompilablePattern } from '../compile-schema';
import { useFormValidation } from '../use-form-validation';

jest.mock( '@woocommerce/base-hooks', () => ( {
	useSchemaParser: jest.fn(),
	usePrevious: jest.fn(),
} ) );

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

	it.each( [
		{ pattern: '^[a-z]+\\-$' },
		{ patternProperties: { '^[a-z]+\\-$': { type: 'string' } } },
	] )( 'keeps sibling fields in form validation with %j', ( validation ) => {
		// Field.validation is typed as a document schema even for scalar field rules.
		const fields = [
			{
				key: 'ns/bad',
				label: 'Bad field',
				required: true,
				validation,
			},
			{
				key: 'ns/good',
				label: 'Good field',
				required: true,
				validation: { pattern: '^[0-9]{5}$' },
			},
			{
				key: 'ns/matching',
				label: 'Matching field',
				required: true,
				validation: { const: { $data: '1/ns~1good' } },
			},
		].map( ( field, index ) => ( {
			...field,
			optionalLabel: field.label,
			autocomplete: 'off',
			hidden: false,
			index,
		} ) ) as unknown as KeyedFormFields;
		jest.mocked( useSchemaParser ).mockReturnValue( {
			parser: createParser(),
			data: {
				cart: {},
				customer: {},
				checkout: {
					create_account: false,
					customer_note: '',
					payment_method: 'cod',
					additional_fields: {
						'ns/bad': 'anything',
						'ns/good': 'invalid',
						'ns/matching': 'different',
					},
				},
			},
		} );

		const { result } = renderHook( () =>
			useFormValidation( fields, 'order' )
		);

		expect( result.current.errors ).toEqual( {
			'ns/good': 'good field must match the pattern ^[0-9]{5}$',
			'ns/matching': 'matching field is invalid',
		} );
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
		const validate = compileSchema( createParser(), schema );
		expect(
			validate?.( { 'member/number': '123-4567', other: '\\-' } )
		).toBe( false );
	} );

	it( 'detects patterns in patternProperties keys and subschemas', () => {
		const schema = {
			type: 'object',
			patternProperties: {
				'^[a-z]+\\-$': { type: 'string' },
			},
		};

		expect( hasUncompilablePattern( schema ) ).toBe( true );
		expect(
			hasUncompilablePattern( {
				patternProperties: {
					'^[a-z]+-$': { pattern: '\\@' },
				},
			} )
		).toBe( true );
	} );

	it( 'does not read instance data as schemas', () => {
		const value = { pattern: '\\@', patternProperties: { '\\-': {} } };
		const schema = {
			type: 'object',
			properties: { pattern: { const: '\\@' } },
			const: value,
			enum: [ value ],
			default: value,
			examples: [ value ],
		};

		expect( hasUncompilablePattern( schema ) ).toBe( false );
		const validate = compileSchema( createParser(), schema );
		expect( validate?.( value ) ).toBe( true );
		expect( validate?.( { pattern: 'different' } ) ).toBe( false );
	} );

	it( 'finds patterns in nested schemas and tuple items', () => {
		expect(
			hasUncompilablePattern( {
				anyOf: [
					{ not: { properties: { pattern: { pattern: '\\@' } } } },
				],
			} )
		).toBe( true );
		expect(
			hasUncompilablePattern( { items: [ { pattern: '\\@' } ] } )
		).toBe( true );
		expect( hasUncompilablePattern( { items: { pattern: '\\@' } } ) ).toBe(
			true
		);
		expect(
			hasUncompilablePattern( { properties: { pattern: false } } )
		).toBe( false );
	} );
} );
