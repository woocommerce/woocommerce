/**
 * External dependencies
 */
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { MultiCurrencySettingsApp } from '../app';
import type { StoreCurrenciesResponse } from '../types';

const mockCreateSuccessNotice = jest.fn();
const mockCreateErrorNotice = jest.fn();

jest.mock( '@wordpress/api-fetch', () => jest.fn() );
jest.mock( '@wordpress/data', () => {
	const actual = jest.requireActual( '@wordpress/data' );

	return {
		...actual,
		useDispatch: jest.fn( () => ( {
			createSuccessNotice: mockCreateSuccessNotice,
			createErrorNotice: mockCreateErrorNotice,
		} ) ),
	};
} );

jest.mock( '../store-settings', () => ( {
	StoreLevelSettings: () => <div>Store settings component</div>,
} ) );

jest.mock( '../currency-settings-modal', () => ( {
	CurrencySettingsModal: ( {
		currency,
		onClose,
		onSaved,
	}: {
		currency: StoreCurrenciesResponse[ 'default' ];
		onClose: () => void;
		onSaved: ( currencyCode: string, manualRate: number | null ) => void;
	} ) => (
		<div>
			<div>Currency settings modal for { currency.code }</div>
			<button
				type="button"
				onClick={ () => {
					onSaved( currency.code, 0.95 );
					onClose();
				} }
			>
				Save { currency.code } manual rate
			</button>
		</div>
	),
} ) );

const mockApiFetch = apiFetch as jest.MockedFunction< typeof apiFetch >;

const currenciesResponse: StoreCurrenciesResponse = {
	available: {
		USD: {
			id: 'usd',
			code: 'USD',
			name: 'United States (US) dollar',
			rate: 1,
			symbol: '$',
			symbol_position: 'left',
			is_zero_decimal: false,
			is_default: true,
			charm: 0,
			rounding: '0',
			last_updated: null,
		},
		EUR: {
			id: 'eur',
			code: 'EUR',
			name: 'Euro',
			rate: 0.92,
			symbol: '€',
			symbol_position: 'left',
			is_zero_decimal: false,
			is_default: false,
			charm: 0,
			rounding: '0',
			last_updated: 1710000000,
		},
		CAD: {
			id: 'cad',
			code: 'CAD',
			name: 'Canadian dollar',
			rate: 1.34,
			symbol: '$',
			symbol_position: 'left',
			is_zero_decimal: false,
			is_default: false,
			charm: 0,
			rounding: '0',
			last_updated: 1710000000,
		},
	},
	enabled: {},
	default: {} as StoreCurrenciesResponse[ 'default' ],
};
currenciesResponse.enabled = {
	USD: currenciesResponse.available.USD,
	EUR: currenciesResponse.available.EUR,
};
currenciesResponse.default = currenciesResponse.available.USD;

const updatedResponse: StoreCurrenciesResponse = {
	...currenciesResponse,
	enabled: {
		USD: currenciesResponse.available.USD,
		CAD: currenciesResponse.available.CAD,
	},
};

function mockInitialFetch() {
	mockApiFetch.mockResolvedValueOnce( currenciesResponse );
}

describe( 'MultiCurrencySettingsApp', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		mockInitialFetch();
	} );

	it( 'loads and renders enabled currencies', async () => {
		render( <MultiCurrencySettingsApp /> );

		expect( mockApiFetch ).toHaveBeenCalledWith( {
			path: '/wc/v3/payments/multi-currency/currencies',
		} );

		expect( await screen.findByText( 'Euro' ) ).toBeInTheDocument();
		expect(
			screen.getByText( 'United States (US) dollar' )
		).toBeInTheDocument();
		expect(
			screen.getAllByText( 'Default currency' ).length
		).toBeGreaterThan( 0 );
		expect(
			screen.getByRole( 'button', {
				name: 'Remove Euro as an enabled currency',
			} )
		).toBeInTheDocument();
	} );

	it( 'removes a non-default enabled currency', async () => {
		mockApiFetch.mockResolvedValueOnce( updatedResponse );

		render( <MultiCurrencySettingsApp /> );

		const removeButton = await screen.findByRole( 'button', {
			name: 'Remove Euro as an enabled currency',
		} );
		removeButton.focus();
		fireEvent.click( removeButton );

		await waitFor( () => {
			expect( mockApiFetch ).toHaveBeenLastCalledWith( {
				path: '/wc/v3/payments/multi-currency/update-enabled-currencies',
				method: 'POST',
				data: { enabled: [ 'USD' ] },
			} );
		} );
		expect( mockCreateSuccessNotice ).toHaveBeenCalledWith(
			'Enabled currencies updated.'
		);
		await waitFor( () =>
			expect(
				screen.getByRole( 'button', {
					name: 'Add/remove currencies',
				} )
			).toHaveFocus()
		);
	} );

	it( 'opens the currency settings modal for a non-default currency', async () => {
		render( <MultiCurrencySettingsApp /> );

		fireEvent.click(
			await screen.findByRole( 'button', {
				name: 'Manage Euro settings',
			} )
		);

		expect(
			screen.getByText( 'Currency settings modal for EUR' )
		).toBeInTheDocument();
	} );

	it( 'updates the displayed exchange rate after saving manual currency settings', async () => {
		render( <MultiCurrencySettingsApp /> );

		fireEvent.click(
			await screen.findByRole( 'button', {
				name: 'Manage Euro settings',
			} )
		);
		fireEvent.click(
			screen.getByRole( 'button', { name: 'Save EUR manual rate' } )
		);

		expect( screen.getByText( '0.95' ) ).toBeInTheDocument();
		expect(
			screen.queryByText( 'Currency settings modal for EUR' )
		).not.toBeInTheDocument();
		await waitFor( () => {
			expect(
				screen.getByRole( 'button', {
					name: 'Manage Euro settings',
				} )
			).toHaveFocus();
		} );
	} );

	it( 'keeps modal save focus visible while updating currencies', async () => {
		let resolveSave = ( value: StoreCurrenciesResponse ) => value;
		const savePromise = new Promise< StoreCurrenciesResponse >(
			( resolve ) => {
				resolveSave = resolve;
			}
		);
		mockApiFetch.mockReturnValueOnce( savePromise );

		render( <MultiCurrencySettingsApp /> );

		fireEvent.click(
			await screen.findByRole( 'button', {
				name: 'Add/remove currencies',
			} )
		);

		const updateButton = screen.getByRole( 'button', {
			name: 'Update selected',
		} );
		updateButton.focus();
		fireEvent.click( updateButton );

		await waitFor( () => {
			expect( updateButton ).toHaveAttribute( 'aria-disabled', 'true' );
		} );
		expect( updateButton ).toHaveFocus();

		resolveSave( updatedResponse );

		await waitFor( () => {
			expect(
				screen.queryByRole( 'heading', {
					name: 'Add enabled currencies',
				} )
			).not.toBeInTheDocument();
		} );
	} );

	it( 'updates selected currencies from the modal', async () => {
		mockApiFetch.mockResolvedValueOnce( updatedResponse );

		render( <MultiCurrencySettingsApp /> );

		fireEvent.click(
			await screen.findByRole( 'button', {
				name: 'Add/remove currencies',
			} )
		);

		expect(
			screen.getByRole( 'heading', { name: 'Add enabled currencies' } )
		).toBeInTheDocument();

		fireEvent.click(
			screen.getByRole( 'checkbox', { name: 'Canadian dollar CAD' } )
		);
		fireEvent.click( screen.getByRole( 'checkbox', { name: 'Euro EUR' } ) );
		fireEvent.click(
			screen.getByRole( 'button', { name: 'Update selected' } )
		);

		await waitFor( () => {
			expect( mockApiFetch ).toHaveBeenLastCalledWith( {
				path: '/wc/v3/payments/multi-currency/update-enabled-currencies',
				method: 'POST',
				data: { enabled: [ 'USD', 'CAD' ] },
			} );
		} );
		expect(
			screen.queryByRole( 'heading', { name: 'Add enabled currencies' } )
		).not.toBeInTheDocument();
	} );

	it( 'shows an error notice when updating currencies fails', async () => {
		mockApiFetch.mockRejectedValueOnce( new Error( 'Nope' ) );

		render( <MultiCurrencySettingsApp /> );

		fireEvent.click(
			await screen.findByRole( 'button', {
				name: 'Remove Euro as an enabled currency',
			} )
		);

		await waitFor( () => {
			expect( mockCreateErrorNotice ).toHaveBeenCalledWith(
				'Error updating enabled currencies.'
			);
		} );
	} );
} );
