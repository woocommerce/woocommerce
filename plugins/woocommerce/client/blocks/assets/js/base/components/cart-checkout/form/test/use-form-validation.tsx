/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';
import type { FormFieldProps } from '@woocommerce/settings';
import { useSchemaParser } from '@woocommerce/base-hooks';

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

	it( 'validates additional fields for contact formType using customer.additional_fields', () => {
		const formFields: FormFieldProps[] = [
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
		];

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
			} as never,
		} );

		const { result } = renderHook( () =>
			useFormValidation( formFields, 'contact' )
		);

		expect( result.current.errors ).toEqual( {
			'repro/digits-contact': 'Use digits only.',
		} );
	} );

	it( 'validates additional fields for order formType using checkout.additional_fields', () => {
		const formFields: FormFieldProps[] = [
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
		];

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
			} as never,
		} );

		const { result } = renderHook( () =>
			useFormValidation( formFields, 'order' )
		);

		expect( result.current.errors ).toEqual( {
			'repro/digits-order': 'Use digits only.',
		} );
	} );

	it( 'returns no errors when contact additional field is valid', () => {
		const formFields: FormFieldProps[] = [
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
		];

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
			} as never,
		} );

		const { result } = renderHook( () =>
			useFormValidation( formFields, 'contact' )
		);

		expect( result.current.errors ).toEqual( {} );
	} );
} );
