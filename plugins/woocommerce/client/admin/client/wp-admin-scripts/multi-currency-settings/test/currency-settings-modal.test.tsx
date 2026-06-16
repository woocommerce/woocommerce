/**
 * External dependencies
 */
import {
	act,
	fireEvent,
	render,
	screen,
	waitFor,
} from '@testing-library/react';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { CurrencySettingsModal } from '../currency-settings-modal';

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

const mockApiFetch = apiFetch as jest.MockedFunction< typeof apiFetch >;

const usdCurrency = {
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
};

const euroCurrency = {
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
};

const automaticSettingsResponse = {
	exchange_rate_type: 'automatic',
	manual_rate: null,
	price_rounding: null,
	price_charm: null,
};

const manualSettingsResponse = {
	exchange_rate_type: 'manual',
	manual_rate: 0.95,
	price_rounding: 1,
	price_charm: -0.01,
};

const renderModal = ( props = {} ) => {
	const onClose = jest.fn();
	const onSaved = jest.fn();

	render(
		<CurrencySettingsModal
			currency={ euroCurrency }
			defaultCurrency={ usdCurrency }
			onClose={ onClose }
			onSaved={ onSaved }
			{ ...props }
		/>
	);

	return { onClose, onSaved };
};

describe( 'CurrencySettingsModal', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'loads and renders currency settings', async () => {
		mockApiFetch.mockResolvedValueOnce( automaticSettingsResponse );

		renderModal();

		expect( mockApiFetch ).toHaveBeenCalledWith( {
			path: '/wc/v3/payments/multi-currency/currencies/EUR',
		} );
		expect(
			await screen.findByRole( 'heading', {
				name: 'Manage Euro settings',
			} )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'radio', { name: 'Fetch rates automatically' } )
		).toBeChecked();
		expect(
			screen.queryByLabelText( 'Manual rate' )
		).not.toBeInTheDocument();
	} );

	it( 'saves manual currency settings with preserved REST keys', async () => {
		mockApiFetch
			.mockResolvedValueOnce( automaticSettingsResponse )
			.mockResolvedValueOnce( manualSettingsResponse );
		const { onClose, onSaved } = renderModal();

		fireEvent.click(
			await screen.findByRole( 'radio', { name: 'Manual' } )
		);
		fireEvent.change( screen.getByLabelText( 'Manual rate' ), {
			target: { value: '0.95' },
		} );
		fireEvent.change( screen.getByLabelText( 'Price rounding' ), {
			target: { value: '1.00' },
		} );
		fireEvent.change( screen.getByLabelText( 'Charm pricing' ), {
			target: { value: '-0.01' },
		} );
		fireEvent.click(
			screen.getByRole( 'button', { name: 'Save changes' } )
		);

		await waitFor( () => {
			expect( mockApiFetch ).toHaveBeenLastCalledWith( {
				path: '/wc/v3/payments/multi-currency/currencies/EUR',
				method: 'POST',
				data: {
					exchange_rate_type: 'manual',
					manual_rate: 0.95,
					price_rounding: 1,
					price_charm: -0.01,
				},
			} );
		} );
		expect( mockCreateSuccessNotice ).toHaveBeenCalledWith(
			'Currency settings saved.'
		);
		expect( onSaved ).toHaveBeenCalledWith( 'EUR', 0.95 );
		expect( onClose ).toHaveBeenCalled();
	} );

	it( 'shows an error notice when saving currency settings fails', async () => {
		mockApiFetch
			.mockResolvedValueOnce( automaticSettingsResponse )
			.mockRejectedValueOnce( new Error( 'Nope' ) );

		renderModal();

		fireEvent.click(
			await screen.findByRole( 'radio', { name: 'Manual' } )
		);
		fireEvent.click(
			screen.getByRole( 'button', { name: 'Save changes' } )
		);

		await waitFor( () => {
			expect( mockCreateErrorNotice ).toHaveBeenCalledWith(
				'Error saving currency settings.'
			);
		} );
		expect(
			screen.getByRole( 'heading', {
				name: 'Manage Euro settings',
			} )
		).toBeInTheDocument();
	} );

	it( 'keeps save focus visible while saving currency settings', async () => {
		let resolveSave = ( value: typeof manualSettingsResponse ) => value;
		const savePromise = new Promise< typeof manualSettingsResponse >(
			( resolve ) => {
				resolveSave = resolve;
			}
		);
		mockApiFetch
			.mockResolvedValueOnce( automaticSettingsResponse )
			.mockReturnValueOnce( savePromise );

		renderModal();

		fireEvent.click(
			await screen.findByRole( 'radio', { name: 'Manual' } )
		);

		const saveButton = screen.getByRole( 'button', {
			name: 'Save changes',
		} );
		saveButton.focus();
		fireEvent.click( saveButton );

		await waitFor( () => {
			expect( saveButton ).toHaveAttribute( 'aria-disabled', 'true' );
		} );
		expect( saveButton ).toHaveFocus();

		await act( async () => {
			resolveSave( manualSettingsResponse );
			await savePromise;
		} );
	} );
} );
