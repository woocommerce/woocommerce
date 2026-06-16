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
import { StoreLevelSettings } from '../store-settings';

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

const storeSettingsResponse = {
	wcpay_multi_currency_enable_auto_currency: true,
	wcpay_multi_currency_enable_storefront_switcher: false,
	wcpay_multi_currency_rendering_mode: 'speed',
	is_cache_optimized_feature_enabled: true,
	site_theme: 'Storefront',
	date_format: 'F j, Y',
	time_format: 'g:i a',
	store_url: 'shop',
};

describe( 'StoreLevelSettings', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'loads and renders store-level settings', async () => {
		mockApiFetch.mockResolvedValueOnce( storeSettingsResponse );

		render( <StoreLevelSettings /> );

		expect( mockApiFetch ).toHaveBeenCalledWith( {
			path: '/wc/v3/payments/multi-currency/get-settings',
		} );
		expect(
			await screen.findByRole( 'heading', { name: 'Store settings' } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'checkbox', {
				name: /Automatically switch customers to their local currency/i,
			} )
		).toBeChecked();
		expect(
			screen.getByRole( 'checkbox', {
				name: /Add a currency switcher to the Storefront theme/i,
			} )
		).not.toBeChecked();
		expect(
			screen.getByRole( 'radio', {
				name: 'Optimized for speed (default)',
			} )
		).toBeChecked();
	} );

	it( 'saves store settings with preserved REST option keys', async () => {
		mockApiFetch
			.mockResolvedValueOnce( storeSettingsResponse )
			.mockResolvedValueOnce( {
				...storeSettingsResponse,
				wcpay_multi_currency_enable_auto_currency: false,
				wcpay_multi_currency_rendering_mode: 'cache',
			} );

		render( <StoreLevelSettings /> );

		fireEvent.click(
			await screen.findByRole( 'checkbox', {
				name: /Automatically switch customers to their local currency/i,
			} )
		);
		fireEvent.click(
			screen.getByRole( 'radio', { name: 'Optimized for caching' } )
		);
		fireEvent.click(
			screen.getByRole( 'button', { name: 'Save changes' } )
		);

		await waitFor( () => {
			expect( mockApiFetch ).toHaveBeenLastCalledWith( {
				path: '/wc/v3/payments/multi-currency/update-settings',
				method: 'POST',
				data: {
					wcpay_multi_currency_enable_auto_currency: 'no',
					wcpay_multi_currency_enable_storefront_switcher: 'no',
					wcpay_multi_currency_rendering_mode: 'cache',
				},
			} );
		} );
		expect( mockCreateSuccessNotice ).toHaveBeenCalledWith(
			'Store settings saved.'
		);
	} );

	it( 'hides conditional settings when the store does not support them', async () => {
		mockApiFetch.mockResolvedValueOnce( {
			...storeSettingsResponse,
			is_cache_optimized_feature_enabled: false,
			site_theme: 'Twenty Twenty-Four',
		} );

		render( <StoreLevelSettings /> );

		expect(
			await screen.findByRole( 'heading', { name: 'Store settings' } )
		).toBeInTheDocument();
		expect(
			screen.queryByRole( 'checkbox', {
				name: /Add a currency switcher to the Storefront theme/i,
			} )
		).not.toBeInTheDocument();
		expect(
			screen.queryByRole( 'radio', { name: 'Optimized for caching' } )
		).not.toBeInTheDocument();
	} );

	it( 'shows an error notice when saving store settings fails', async () => {
		mockApiFetch
			.mockResolvedValueOnce( storeSettingsResponse )
			.mockRejectedValueOnce( new Error( 'Nope' ) );

		render( <StoreLevelSettings /> );

		fireEvent.click(
			await screen.findByRole( 'checkbox', {
				name: /Automatically switch customers to their local currency/i,
			} )
		);
		fireEvent.click(
			screen.getByRole( 'button', { name: 'Save changes' } )
		);

		await waitFor( () => {
			expect( mockCreateErrorNotice ).toHaveBeenCalledWith(
				'Error saving store settings.'
			);
		} );
	} );

	it( 'keeps save focus visible while saving store settings', async () => {
		let resolveSave = ( value: typeof storeSettingsResponse ) => value;
		const savePromise = new Promise< typeof storeSettingsResponse >(
			( resolve ) => {
				resolveSave = resolve;
			}
		);
		mockApiFetch
			.mockResolvedValueOnce( storeSettingsResponse )
			.mockReturnValueOnce( savePromise );

		render( <StoreLevelSettings /> );

		fireEvent.click(
			await screen.findByRole( 'checkbox', {
				name: /Automatically switch customers to their local currency/i,
			} )
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
			resolveSave( {
				...storeSettingsResponse,
				wcpay_multi_currency_enable_auto_currency: false,
			} );
			await savePromise;
		} );
	} );
} );
