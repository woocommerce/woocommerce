/**
 * External dependencies
 */
import { render, screen, waitFor, fireEvent } from '@testing-library/react';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { SettingsGeneralMain } from '../settings-general-main';
import type { GeneralSettingsResponse } from '../hooks/use-general-settings';

// Mock dependencies.
jest.mock( '@wordpress/api-fetch' );
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

describe( 'SettingsGeneralMain', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	describe( 'Basic Rendering', () => {
		it( 'shows loading state initially', () => {
			( apiFetch as jest.Mock ).mockImplementation(
				() => new Promise( () => {} )
			);

			render( <SettingsGeneralMain /> );

			expect(
				screen.getByText( 'Loading settings...' )
			).toBeInTheDocument();
		} );

		it( 'renders settings after successful API fetch', async () => {
			( apiFetch as jest.Mock ).mockResolvedValue( mockApiResponse );

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
		it( 'displays error message when API fetch fails', async () => {
			const errorMessage = 'Network error';
			( apiFetch as jest.Mock ).mockRejectedValue(
				new Error( errorMessage )
			);

			render( <SettingsGeneralMain /> );

			await waitFor( () => {
				expect(
					screen.getByText(
						'Error loading settings. Please try refreshing the page.'
					)
				).toBeInTheDocument();
			} );

			expect( screen.getByText( errorMessage ) ).toBeInTheDocument();
		} );

		it( 'displays error message when save fails', async () => {
			( apiFetch as jest.Mock )
				.mockResolvedValueOnce( mockApiResponse )
				.mockRejectedValueOnce( new Error( 'Save failed' ) );

			render( <SettingsGeneralMain /> );

			await waitFor( () => {
				expect( screen.getByText( 'Store Address' ) ).toBeInTheDocument();
			} );

			const saveButton = screen.getByText( 'Save changes' );
			fireEvent.click( saveButton );

			await waitFor( () => {
				expect(
					screen.getByText(
						'Error saving settings. Please try again.'
					)
				).toBeInTheDocument();
			} );
		} );
	} );

	describe( 'Save Functionality', () => {
		it( 'enables save button when form is dirty', async () => {
			( apiFetch as jest.Mock ).mockResolvedValue( mockApiResponse );

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

		it( 'calls API with correct data on save', async () => {
			( apiFetch as jest.Mock ).mockResolvedValue( mockApiResponse );

			render( <SettingsGeneralMain /> );

			await waitFor( () => {
				expect( screen.getByText( 'Store Address' ) ).toBeInTheDocument();
			} );

			// Simulate form change.
			const input = screen.getAllByTestId( 'mock-input' )[ 0 ];
			fireEvent.change( input, { target: { value: '456 New St' } } );

			const saveButton = screen.getByText( 'Save changes' );
			fireEvent.click( saveButton );

			await waitFor( () => {
				expect( apiFetch ).toHaveBeenCalledWith(
					expect.objectContaining( {
						path: '/wc/v4/settings/general',
						method: 'POST',
						data: expect.objectContaining( {
							values: expect.any( Object ),
						} ),
					} )
				);
			} );
		} );

		it( 'shows success message after successful save', async () => {
			( apiFetch as jest.Mock ).mockResolvedValue( mockApiResponse );

			render( <SettingsGeneralMain /> );

			await waitFor( () => {
				expect( screen.getByText( 'Store Address' ) ).toBeInTheDocument();
			} );

			// Simulate form change.
			const input = screen.getAllByTestId( 'mock-input' )[ 0 ];
			fireEvent.change( input, { target: { value: '456 New St' } } );

			const saveButton = screen.getByText( 'Save changes' );
			fireEvent.click( saveButton );

			await waitFor( () => {
				expect(
					screen.getByText( 'Settings saved successfully.' )
				).toBeInTheDocument();
			} );
		} );

		it( 'disables save button while saving', async () => {
			( apiFetch as jest.Mock ).mockImplementation( ( options ) => {
				if ( options.method === 'POST' ) {
					return new Promise( ( resolve ) =>
						setTimeout( () => resolve( mockApiResponse ), 100 )
					);
				}
				return Promise.resolve( mockApiResponse );
			} );

			render( <SettingsGeneralMain /> );

			await waitFor( () => {
				expect( screen.getByText( 'Store Address' ) ).toBeInTheDocument();
			} );

			// Simulate form change.
			const input = screen.getAllByTestId( 'mock-input' )[ 0 ];
			fireEvent.change( input, { target: { value: '456 New St' } } );

			const saveButton = screen.getByText(
				'Save changes'
			) as HTMLButtonElement;
			fireEvent.click( saveButton );

			await waitFor( () => {
				expect( screen.getByText( 'Saving...' ) ).toBeInTheDocument();
			} );

			const savingButton = screen.getByText(
				'Saving...'
			) as HTMLButtonElement;
			expect( savingButton.disabled ).toBe( true );
		} );
	} );

	describe( 'Data Structure', () => {
		it( 'renders all groups from API response', async () => {
			( apiFetch as jest.Mock ).mockResolvedValue( mockApiResponse );

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

			( apiFetch as jest.Mock ).mockResolvedValue( emptyResponse );

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

			( apiFetch as jest.Mock ).mockResolvedValue(
				responseWithoutDesc
			);

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

			( apiFetch as jest.Mock ).mockResolvedValue(
				responseWithUndefined
			);

			render( <SettingsGeneralMain /> );

			await waitFor( () => {
				expect( screen.getByText( 'Store Address' ) ).toBeInTheDocument();
			} );
		} );
	} );
} );
