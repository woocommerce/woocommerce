/**
 * External dependencies
 */
import { render, screen, waitFor, fireEvent } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { SettingsGeneralMain } from '../settings-general-main';
import type { GeneralSettingsResponse } from '../hooks/use-general-settings';

// Mock dependencies.
jest.mock( '@wordpress/dataviews', () => ( {
	DataForm: ( {
		data,
		onChange,
	}: {
		data: Record< string, unknown >;
		onChange: ( newData: Record< string, unknown > ) => void;
	} ) => (
		<div data-testid="data-form">
			<input
				data-testid="mock-input"
				value={ String( data.test_field || '' ) }
				onChange={ ( e ) =>
					onChange( { ...data, test_field: e.target.value } )
				}
			/>
		</div>
	),
} ) );

const mockApiResponse: GeneralSettingsResponse = {
	id: 'general',
	title: 'General',
	description: 'General settings',
	values: {
		test_field: 'Initial value',
		woocommerce_store_address: '123 Main St',
		woocommerce_currency: 'USD',
		woocommerce_calc_taxes: false,
	},
	groups: {
		store_address: {
			title: 'Store Address',
			description: 'This is where your business is located.',
			order: 10,
			fields: [
				{
					id: 'test_field',
					label: 'Test field',
					type: 'text',
					desc: 'Test description',
				},
				{
					id: 'woocommerce_store_address',
					label: 'Address line 1',
					type: 'text',
					desc: 'The street address',
				},
			],
		},
		pricing_options: {
			title: 'Currency options',
			description: 'Currency settings',
			order: 40,
			fields: [
				{
					id: 'woocommerce_currency',
					label: 'Currency',
					type: 'select',
					options: {
						USD: 'US Dollar',
						EUR: 'Euro',
					},
					desc: 'Currency for prices',
				},
			],
		},
		taxes_and_coupons_options: {
			title: 'Taxes and coupons',
			description: 'Enable taxes and coupons',
			order: 30,
			fields: [
				{
					id: 'woocommerce_calc_taxes',
					label: 'Enable taxes',
					type: 'checkbox',
					desc: 'Enable tax rates and calculations',
				},
			],
		},
	},
};

const setPreloadedSettings = ( data?: GeneralSettingsResponse ) => {
	( window as Window & {
		wcSettings?: {
			admin?: {
				settings?: {
					general?: GeneralSettingsResponse;
				};
			};
		};
	} ).wcSettings = data
		? { admin: { settings: { general: data } } }
		: { admin: { settings: {} } };
};

describe( 'SettingsGeneralMain', () => {
	beforeEach( () => {
		setPreloadedSettings( mockApiResponse );
	} );

	describe( 'Basic Rendering', () => {
		it( 'shows loading state initially', () => {
			setPreloadedSettings();

			render( <SettingsGeneralMain /> );

			expect( screen.getByText( 'Loading settings' ) ).toBeInTheDocument();
		} );

		it( 'renders settings from preloaded data', async () => {
			render( <SettingsGeneralMain /> );

			await waitFor( () => {
				expect( screen.getByText( 'Store Address' ) ).toBeInTheDocument();
			} );

			expect( screen.getByText( 'Currency options' ) ).toBeInTheDocument();
			expect(
				screen.getByText( 'Taxes and coupons' )
			).toBeInTheDocument();
		} );
	} );

	describe( 'Error Handling', () => {
		it( 'displays error message when preloaded data is missing', async () => {
			setPreloadedSettings();
			render( <SettingsGeneralMain /> );

			await waitFor( () => {
				expect(
					screen.getByText(
						'Error loading settings. Please try refreshing the page.'
					)
				).toBeInTheDocument();
			} );

			expect(
				screen.getByText( 'General settings data is missing.' )
			).toBeInTheDocument();
		} );
	} );

	describe( 'Save Functionality', () => {
		it( 'enables save button when form is dirty', async () => {
			render( <SettingsGeneralMain /> );

			await waitFor( () => {
				expect( screen.getByText( 'Store Address' ) ).toBeInTheDocument();
			} );

			const saveButton = screen.getByText(
				'Save changes'
			) as HTMLButtonElement;
			expect( saveButton.disabled ).toBe( true );

			// Simulate form change.
			const input = screen.getAllByTestId( 'mock-input' )[ 0 ];
			fireEvent.change( input, { target: { value: '456 New St' } } );

			await waitFor( () => {
				expect( saveButton.disabled ).toBe( false );
			} );
		} );

		it( 'renders hidden inputs for form submission', async () => {
			render( <SettingsGeneralMain /> );

			await waitFor( () => {
				expect( screen.getByText( 'Store Address' ) ).toBeInTheDocument();
			} );

			const input = screen.getAllByTestId( 'mock-input' )[ 0 ];
			fireEvent.change( input, { target: { value: '456 New St' } } );

			await waitFor( () => {
				const hiddenInput = document.querySelector(
					'input[name="test_field"]'
				) as HTMLInputElement;
				expect( hiddenInput.value ).toBe( '456 New St' );
			} );
		} );
	} );

	describe( 'Data Structure', () => {
		it( 'renders all groups from API response', async () => {
			render( <SettingsGeneralMain /> );

			await waitFor( () => {
				expect( screen.getByText( 'Store Address' ) ).toBeInTheDocument();
			} );

			// Check all three groups are rendered.
			expect( screen.getByText( 'Store Address' ) ).toBeInTheDocument();
			expect( screen.getByText( 'Currency options' ) ).toBeInTheDocument();
			expect(
				screen.getByText( 'Taxes and coupons' )
			).toBeInTheDocument();
		} );

		it( 'handles empty groups gracefully', async () => {
			const emptyResponse = {
				...mockApiResponse,
				groups: {},
			};

			setPreloadedSettings( emptyResponse );

			render( <SettingsGeneralMain /> );

			await waitFor( () => {
				expect( screen.queryByText( 'Store Address' ) ).not.toBeInTheDocument();
			} );

			expect( screen.getByText( 'Save changes' ) ).toBeInTheDocument();
		} );
	} );

	describe( 'Edge Cases', () => {
		it( 'handles missing field descriptions', async () => {
			const responseWithoutDesc = {
				...mockApiResponse,
				groups: {
					store_address: {
						title: 'Store Address',
						description: '',
						order: 10,
						fields: [
							{
								id: 'woocommerce_store_address',
								label: 'Address line 1',
								type: 'text',
							},
						],
					},
				},
			};

			setPreloadedSettings( responseWithoutDesc );

			render( <SettingsGeneralMain /> );

			await waitFor( () => {
				expect( screen.getByText( 'Store Address' ) ).toBeInTheDocument();
			} );
		} );

		it( 'handles undefined values in form data', async () => {
			const responseWithUndefined = {
				...mockApiResponse,
				values: {
					woocommerce_store_address: undefined,
				},
			};

			setPreloadedSettings( responseWithUndefined );

			render( <SettingsGeneralMain /> );

			await waitFor( () => {
				expect( screen.getByText( 'Store Address' ) ).toBeInTheDocument();
			} );
		} );
	} );
} );
