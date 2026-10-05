import { describe, expect, it, vi } from 'vitest';

/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
vi.mock( '@woocommerce/settings', async () => {
	const _actual = await vi.importActual( '@woocommerce/settings' );
	const mock = {
		...( await vi.importActual( '@woocommerce/settings' ) ),
		getSettingWithCoercion: vi
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
							branding_html:
								'<div>Test Provider - Fallback</div>',
						},
					];
				}
				return _actual.getSettingWithCoercion(
					value,
					fallback,
					typeguard
				);
			} ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@woocommerce/blocks-components', () => {
	const mock = {
		ValidatedTextInput: () => <div>ValidatedTextInput component</div>,
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '../../address-autocomplete/address-autocomplete', () => {
	const mock = {
		AddressAutocomplete: () => <div>AddressAutocomplete component</div>,
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '../address-line-2-field', () => {
	const mock = () => <div>AddressLine2 component</div>;
	return {
		default: mock,
		...mock,
	};
} );
describe( 'AddressLineFields', () => {
	it( 'should show the AddressAutocomplete component when providers are available', async () => {
		vi.resetModules(),
			await ( async () => {
				const AddressLineFields =
					// eslint-disable-next-line @typescript-eslint/no-var-requires
					( await import( '../address-line-fields' ) ).default;
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
						onChange={ vi.fn() }
					/>
				);
				expect(
					screen.getByText( 'AddressAutocomplete component' )
				).toBeInTheDocument();
			} )();
	} );
	it( 'should show the ValidatedTextInput component when no providers are available', async () => {
		vi.resetModules(),
			await ( async () => {
				vi.doMock( '@woocommerce/settings', async () => {
					const actual = await vi.importActual(
						'@woocommerce/settings'
					);
					const mock = {
						...( await vi.importActual( '@woocommerce/settings' ) ),
						getSettingWithCoercion: vi
							.fn()
							.mockImplementation(
								( value, fallback, typeguard ) => {
									if (
										value === 'addressAutocompleteProviders'
									) {
										return [];
									}
									return actual.getSettingWithCoercion(
										value,
										fallback,
										typeguard
									);
								}
							),
					};
					return Object.defineProperties(
						{
							default: mock,
						},
						Object.getOwnPropertyDescriptors( mock )
					);
				} );
				vi.resetModules();
				const AddressLineFields =
					// eslint-disable-next-line @typescript-eslint/no-var-requires
					( await import( '../address-line-fields' ) ).default;
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
						onChange={ vi.fn() }
					/>
				);
				expect(
					screen.getByText( 'ValidatedTextInput component' )
				).toBeInTheDocument();
			} )();
	} );
} );
