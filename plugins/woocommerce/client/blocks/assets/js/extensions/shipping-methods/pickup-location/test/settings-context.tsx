/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { dispatch } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';
import { act, render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import { SettingsProvider, useSettingsContext } from '../settings-context';
import type { SortablePickupLocation } from '../types';

jest.mock( '@wordpress/api-fetch', () => jest.fn() );

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	dispatch: jest.fn(),
} ) );

jest.mock( '../utils', () => ( {
	defaultSettings: {
		enabled: false,
		title: 'Pickup',
		tax_status: 'taxable',
		cost: '',
	},
	defaultReadyOnlySettings: {
		hasLegacyPickup: false,
		storeCountry: 'US',
		storeState: 'CA',
	},
	readOnlySettings: {
		hasLegacyPickup: false,
		storeCountry: 'US',
		storeState: 'CA',
	},
	getInitialSettings: jest.fn( () => ( {
		enabled: false,
		title: 'Pickup',
		tax_status: 'none',
		cost: '',
	} ) ),
	getInitialPickupLocations: jest.fn( () => [
		{
			id: 'warehouse-0',
			name: 'Warehouse',
			details: 'Rear entrance',
			enabled: true,
			address: {
				address_1: '60 29th Street',
				city: 'San Francisco',
				state: 'CA',
				postcode: '94110',
				country: 'US',
			},
		},
	] ),
} ) );

const mockApiFetch = apiFetch as jest.Mock;
const mockDispatch = dispatch as jest.Mock;
const createSuccessNotice = jest.fn();
const createErrorNotice = jest.fn();

const annex: SortablePickupLocation = {
	id: '',
	name: 'Annex',
	details: 'Front desk',
	enabled: true,
	address: {
		address_1: '10 Market Street',
		city: 'San Francisco',
		state: 'CA',
		postcode: '94105',
		country: 'US',
	},
};

const replacement: SortablePickupLocation = {
	id: 'warehouse-0',
	name: 'Main warehouse',
	details: 'Loading bay',
	enabled: false,
	address: {
		address_1: '100 New Bridge Street',
		city: 'London',
		state: '',
		postcode: 'EC4V 6JA',
		country: 'GB',
	},
};

const initialWarehouse: SortablePickupLocation = {
	id: 'warehouse-0',
	name: 'Warehouse',
	details: 'Rear entrance',
	enabled: true,
	address: {
		address_1: '60 29th Street',
		city: 'San Francisco',
		state: 'CA',
		postcode: '94110',
		country: 'US',
	},
};

const addedAnnex: SortablePickupLocation = {
	...annex,
	id: 'annex-1',
};

const ContextConsumer = () => {
	const context = useSettingsContext();
	return (
		<>
			<output aria-label="Settings state">
				{ JSON.stringify( context.settings ) }
			</output>
			<output aria-label="Pickup locations state">
				{ JSON.stringify( context.pickupLocations ) }
			</output>
			<output aria-label="Dirty state">
				{ String( context.isDirty ) }
			</output>
			<output aria-label="Saving state">
				{ String( context.isSaving ) }
			</output>
			<button
				type="button"
				onClick={ () => context.updateLocation( 'new', annex ) }
			>
				Add annex
			</button>
			<button
				type="button"
				onClick={ () =>
					context.updateLocation( 'warehouse-0', replacement )
				}
			>
				Replace warehouse
			</button>
			<button
				type="button"
				onClick={ () => context.toggleLocation( 'warehouse-0' ) }
			>
				Toggle warehouse
			</button>
			<button
				type="button"
				onClick={ () => context.updateLocation( 'annex-1', null ) }
			>
				Delete annex
			</button>
			<button
				type="button"
				onClick={ () => {
					context.setSettingField( 'enabled' )( true );
					context.setSettingField( 'title' )( 'Curbside pickup' );
					context.setSettingField( 'tax_status' )( 'unsupported' );
					context.setSettingField( 'cost' )( '7.50' );
				} }
			>
				Change settings
			</button>
			<button type="button" onClick={ context.save }>
				Save
			</button>
		</>
	);
};

const getOutput = ( name: string ) => screen.getByRole( 'status', { name } );

const getPickupLocations = (): SortablePickupLocation[] =>
	JSON.parse(
		getOutput( 'Pickup locations state' ).textContent ?? '[]'
	) as SortablePickupLocation[];

describe( 'SettingsProvider', () => {
	beforeEach( () => {
		mockApiFetch.mockReset();
		mockDispatch.mockReset();
		createSuccessNotice.mockReset();
		createErrorNotice.mockReset();
		mockDispatch.mockReturnValue( {
			createSuccessNotice,
			createErrorNotice,
		} );
	} );

	it( 'adds, replaces, toggles, and deletes locations while marking changes dirty', async () => {
		const user = userEvent.setup();
		render(
			<SettingsProvider>
				<ContextConsumer />
			</SettingsProvider>
		);

		expect( getOutput( 'Dirty state' ) ).toHaveTextContent( 'false' );

		await act( async () => {
			await user.click(
				screen.getByRole( 'button', { name: 'Add annex' } )
			);
		} );
		expect( getPickupLocations() ).toEqual( [
			initialWarehouse,
			addedAnnex,
		] );
		expect( getOutput( 'Dirty state' ) ).toHaveTextContent( 'true' );

		await act( async () => {
			await user.click(
				screen.getByRole( 'button', { name: 'Replace warehouse' } )
			);
		} );
		expect( getPickupLocations() ).toEqual( [ replacement, addedAnnex ] );

		await act( async () => {
			await user.click(
				screen.getByRole( 'button', { name: 'Toggle warehouse' } )
			);
		} );
		const enabledReplacement = { ...replacement, enabled: true };
		expect( getPickupLocations() ).toEqual( [
			enabledReplacement,
			addedAnnex,
		] );

		await act( async () => {
			await user.click(
				screen.getByRole( 'button', { name: 'Delete annex' } )
			);
		} );
		expect( getPickupLocations() ).toEqual( [ enabledReplacement ] );
	} );

	it( 'normalizes settings and locations in a successful save request', async () => {
		const user = userEvent.setup();
		let resolveRequest: () => void;
		mockApiFetch.mockImplementation(
			() =>
				new Promise( ( resolve ) => {
					resolveRequest = () => resolve( {} );
				} )
		);
		render(
			<SettingsProvider>
				<ContextConsumer />
			</SettingsProvider>
		);

		await act( async () => {
			await user.click(
				screen.getByRole( 'button', { name: 'Change settings' } )
			);
		} );
		await act( async () => {
			await user.click(
				screen.getByRole( 'button', { name: 'Add annex' } )
			);
		} );
		expect( getOutput( 'Dirty state' ) ).toHaveTextContent( 'true' );

		await act( async () => {
			await user.click( screen.getByRole( 'button', { name: 'Save' } ) );
		} );

		expect( mockApiFetch ).toHaveBeenCalledWith( {
			path: '/wc/v3/pickup-locations',
			method: 'POST',
			data: {
				pickup_location_settings: {
					enabled: 'yes',
					title: 'Curbside pickup',
					tax_status: 'taxable',
					cost: '7.50',
				},
				pickup_locations: [
					{
						name: 'Warehouse',
						address: {
							address_1: '60 29th Street',
							city: 'San Francisco',
							state: 'CA',
							postcode: '94110',
							country: 'US',
						},
						details: 'Rear entrance',
						enabled: true,
					},
					{
						name: 'Annex',
						address: annex.address,
						details: 'Front desk',
						enabled: true,
					},
				],
			},
		} );
		expect( getOutput( 'Saving state' ) ).toHaveTextContent( 'true' );
		expect( getOutput( 'Dirty state' ) ).toHaveTextContent( 'false' );

		await act( async () => {
			resolveRequest();
		} );

		await waitFor( () => {
			expect( getOutput( 'Saving state' ) ).toHaveTextContent( 'false' );
		} );
		expect( createSuccessNotice ).toHaveBeenCalledWith(
			'Local Pickup settings have been saved.'
		);
		expect( createErrorNotice ).not.toHaveBeenCalled();
		expect( mockDispatch ).toHaveBeenCalledWith( noticesStore );
	} );

	it( 'restores dirty state and reports a rejected save', async () => {
		const user = userEvent.setup();
		let rejectRequest: ( reason: Error ) => void;
		mockApiFetch.mockImplementation(
			() =>
				new Promise( ( resolve, reject ) => {
					void resolve;
					rejectRequest = reject;
				} )
		);
		render(
			<SettingsProvider>
				<ContextConsumer />
			</SettingsProvider>
		);

		await act( async () => {
			await user.click(
				screen.getByRole( 'button', { name: 'Change settings' } )
			);
		} );
		await act( async () => {
			await user.click( screen.getByRole( 'button', { name: 'Save' } ) );
		} );
		expect( getOutput( 'Saving state' ) ).toHaveTextContent( 'true' );
		expect( getOutput( 'Dirty state' ) ).toHaveTextContent( 'false' );

		await act( async () => {
			rejectRequest( new Error( 'Request failed' ) );
		} );

		await waitFor( () => {
			expect( getOutput( 'Saving state' ) ).toHaveTextContent( 'false' );
		} );
		expect( getOutput( 'Dirty state' ) ).toHaveTextContent( 'true' );
		expect( createErrorNotice ).toHaveBeenCalledWith(
			'There was an error saving your Local Pickup settings. Please try again.'
		);
		expect( createSuccessNotice ).not.toHaveBeenCalled();
		expect( mockDispatch ).toHaveBeenCalledWith( noticesStore );
	} );
} );
