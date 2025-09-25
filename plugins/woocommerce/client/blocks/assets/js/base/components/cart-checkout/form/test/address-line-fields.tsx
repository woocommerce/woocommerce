/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';

jest.mock( '@woocommerce/settings', () => ( {
	...jest.requireActual( '@woocommerce/settings' ),
	getSettingWithCoercion: jest
		.fn()
		.mockImplementation( ( value, fallback, typeguard ) => {
			if ( value === 'addressAutocompleteProviders' ) {
				return [
					{
						id: 'germany-only',
						name: 'Test Provider Only Works In Germany',
						branding_html: '<div>Test Provider - DE</div>',
					},
					{
						id: 'fallback',
						name: 'Fallback Test Provider',
						branding_html: '<div>Test Provider - Fallback</div>',
					},
				];
			}
			return jest
				.requireActual( '@woocommerce/settings' )
				.getSettingWithCoercion( value, fallback, typeguard );
		} ),
} ) );

jest.mock( '@woocommerce/blocks-components', () => ( {
	ValidatedTextInput: () => <div>ValidatedTextInput component</div>,
} ) );

jest.mock( '../../address-autocomplete/address-autocomplete', () => ( {
	AddressAutocomplete: () => <div>AddressAutocomplete component</div>,
} ) );
jest.mock( '../address-line-2-field', () => () => (
	<div>AddressLine2 component</div>
) );
describe( 'AddressLineFields', () => {
	it( 'should show the AddressAutocomplete component when providers are available', () => {
		jest.isolateModules( () => {
			const AddressLineFields =
				// eslint-disable-next-line @typescript-eslint/no-var-requires
				require( '../address-line-fields' ).default;
			render(
				<AddressLineFields
					formId="a"
					address1={ {
						field: {
							index: 0,
							key: 'address_1',
							required: true,
							label: 'Address 1',
							type: 'text',
							hidden: false,
							autocomplete: 'address-line1',
							optionalLabel: 'Optional Address 1',
						},
						value: '',
					} }
					address2={ {
						field: {
							index: 1,
							key: 'address_2',
							required: false,
							label: 'Address 2',
							type: 'text',
							hidden: false,
							autocomplete: 'address-line2',
							optionalLabel: 'Optional Address 2',
						},
						value: '',
					} }
					addressType="billing"
					onChange={ jest.fn() }
				/>
			);

			expect(
				screen.getByText( 'AddressAutocomplete component' )
			).toBeInTheDocument();
		} );
	} );
	it( 'should show the ValidatedTextInput component when no providers are available', () => {
		jest.isolateModules( () => {
			const AddressLineFields =
				// eslint-disable-next-line @typescript-eslint/no-var-requires
				require( '../address-line-fields' ).default;
			jest.mock( '@woocommerce/settings', () => ( {
				...jest.requireActual( '@woocommerce/settings' ),
				getSettingWithCoercion: jest
					.fn()
					.mockImplementation( ( value, fallback, typeguard ) => {
						if ( value === 'addressAutocompleteProviders' ) {
							return [];
						}
						return jest
							.requireActual( '@woocommerce/settings' )
							.getSettingWithCoercion(
								value,
								fallback,
								typeguard
							);
					} ),
			} ) );
			jest.resetModules();

			render(
				<AddressLineFields
					formId="a"
					address1={ {
						// @ts-expect-error -- No need to pass validation for this test
						field: {
							index: 0,
							key: 'address_1',
							required: true,
							label: 'Address 1',
							type: 'text',
							hidden: false,
							autocomplete: 'address-line1',
							optionalLabel: 'Optional Address 1',
						},
						value: '',
					} }
					address2={ {
						// @ts-expect-error -- No need to pass validation for this test
						field: {
							index: 1,
							key: 'address_2',
							required: false,
							label: 'Address 2',
							type: 'text',
							hidden: false,
							autocomplete: 'address-line2',
							optionalLabel: 'Optional Address 2',
						},
						value: '',
					} }
					addressType="billing"
					onChange={ jest.fn() }
				/>
			);

			expect(
				screen.getByText( 'ValidatedTextInput component' )
			).toBeInTheDocument();
		} );
	} );
} );
