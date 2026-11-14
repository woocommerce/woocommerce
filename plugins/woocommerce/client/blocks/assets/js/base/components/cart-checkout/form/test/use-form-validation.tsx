/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';
import type { KeyedFormFields } from '@woocommerce/settings';
import { useSchemaParser, type DocumentObject } from '@woocommerce/base-hooks';

/**
 * Internal dependencies
 */
import { useFormValidation } from '../use-form-validation';
import { schemaParser } from '../../../../../utils/schema-parser';

jest.mock( '@woocommerce/base-hooks', () => {
	const original = jest.requireActual( '@woocommerce/base-hooks' );
	return {
		...original,
		useSchemaParser: jest.fn(),
	};
} );

const mockUseSchemaParser = useSchemaParser as jest.MockedFunction<
	typeof useSchemaParser
>;

describe( 'useFormValidation', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'validates required additional fields for contact formType using customer.additional_fields', () => {
		const formFields = [
			{
				key: 'repro/digits-contact',
				label: 'Member number',
				required: true,
				validation: {
					type: 'string',
					pattern: '^[0-9]+$',
					errorMessage: 'Use digits only.',
				},
			},
		] as unknown as KeyedFormFields;

		mockUseSchemaParser.mockReturnValue( {
			parser: schemaParser,
			data: {
				cart: {},
				checkout: {},
				customer: {
					additional_fields: {
						'repro/digits-contact': 'invalid-abc',
					},
				},
			} as unknown as DocumentObject< 'contact' >,
		} );

		const { result } = renderHook( () =>
			useFormValidation( formFields, 'contact' )
		);

		expect( result.current.errors ).toEqual( {
			'repro/digits-contact': 'Use digits only.',
		} );
	} );

	it( 'validates optional additional fields for contact formType when a value is provided', () => {
		const formFields = [
			{
				key: 'repro/digits-contact',
				label: 'Member number',
				required: false,
				validation: {
					type: 'string',
					pattern: '^[0-9]+$',
					errorMessage: 'Use digits only.',
				},
			},
		] as unknown as KeyedFormFields;

		mockUseSchemaParser.mockReturnValue( {
			parser: schemaParser,
			data: {
				cart: {},
				checkout: {},
				customer: {
					additional_fields: {
						'repro/digits-contact': 'invalid-abc',
					},
				},
			} as unknown as DocumentObject< 'contact' >,
		} );

		const { result } = renderHook( () =>
			useFormValidation( formFields, 'contact' )
		);

		expect( result.current.errors ).toEqual( {
			'repro/digits-contact': 'Use digits only.',
		} );
	} );

	it( 'does not validate optional additional fields for contact formType when empty', () => {
		const formFields = [
			{
				key: 'repro/digits-contact',
				label: 'Member number',
				required: false,
				validation: {
					type: 'string',
					pattern: '^[0-9]+$',
					errorMessage: 'Use digits only.',
				},
			},
		] as unknown as KeyedFormFields;

		mockUseSchemaParser.mockReturnValue( {
			parser: schemaParser,
			data: {
				cart: {},
				checkout: {},
				customer: {
					additional_fields: {
						'repro/digits-contact': '',
					},
				},
			} as unknown as DocumentObject< 'contact' >,
		} );

		const { result } = renderHook( () =>
			useFormValidation( formFields, 'contact' )
		);

		expect( result.current.errors ).toEqual( {} );
	} );

	it( 'validates additional fields for order formType using checkout.additional_fields', () => {
		const formFields = [
			{
				key: 'repro/digits-order',
				label: 'PO number',
				required: true,
				validation: {
					type: 'string',
					pattern: '^[0-9]+$',
					errorMessage: 'Use digits only.',
				},
			},
		] as unknown as KeyedFormFields;

		mockUseSchemaParser.mockReturnValue( {
			parser: schemaParser,
			data: {
				cart: {},
				checkout: {
					additional_fields: {
						'repro/digits-order': 'invalid-abc',
					},
				},
				customer: {},
			} as unknown as DocumentObject< 'order' >,
		} );

		const { result } = renderHook( () =>
			useFormValidation( formFields, 'order' )
		);

		expect( result.current.errors ).toEqual( {
			'repro/digits-order': 'Use digits only.',
		} );
	} );

	it( 'returns no errors when contact additional field is valid', () => {
		const formFields = [
			{
				key: 'repro/digits-contact',
				label: 'Member number',
				required: true,
				validation: {
					type: 'string',
					pattern: '^[0-9]+$',
					errorMessage: 'Use digits only.',
				},
			},
		] as unknown as KeyedFormFields;

		mockUseSchemaParser.mockReturnValue( {
			parser: schemaParser,
			data: {
				cart: {},
				checkout: {},
				customer: {
					additional_fields: {
						'repro/digits-contact': '12345',
					},
				},
			} as unknown as DocumentObject< 'contact' >,
		} );

		const { result } = renderHook( () =>
			useFormValidation( formFields, 'contact' )
		);

		expect( result.current.errors ).toEqual( {} );
	} );
} );
