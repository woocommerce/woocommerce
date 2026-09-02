/**
 * External dependencies
 */
import { act, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ReactNode } from 'react';

/**
 * Internal dependencies
 */
import LocationSettings from '../location-settings';
import { useSettingsContext } from '../settings-context';
import type { SettingsContextType, SortablePickupLocation } from '../types';

jest.mock( '@wordpress/components', () => ( {
	Button: ( {
		children,
		onClick,
	}: {
		children: ReactNode;
		onClick: () => void;
	} ) => (
		<button type="button" onClick={ onClick }>
			{ children }
		</button>
	),
	Card: ( { children }: { children: ReactNode } ) => <div>{ children }</div>,
	CardBody: ( { children }: { children: ReactNode } ) => (
		<div>{ children }</div>
	),
	ExternalLink: ( {
		children,
		href,
	}: {
		children: ReactNode;
		href: string;
	} ) => <a href={ href }>{ children }</a>,
	Modal: ( { children, title }: { children: ReactNode; title: string } ) => (
		<div role="dialog" aria-label={ title }>
			{ children }
		</div>
	),
	SelectControl: ( {
		children,
		label,
		onChange,
		value,
	}: {
		children: ReactNode;
		label: string;
		onChange: ( value: string ) => void;
		value: string;
	} ) => {
		const id = `select-${ label.toLowerCase().replace( /\W+/g, '-' ) }`;
		return (
			<>
				<label htmlFor={ id }>{ label }</label>
				<select
					id={ id }
					value={ value }
					onChange={ ( event ) => onChange( event.target.value ) }
				>
					{ children }
				</select>
			</>
		);
	},
	TextControl: ( {
		label,
		onChange,
		placeholder,
		required,
		value,
	}: {
		label?: string;
		onChange: ( value: string ) => void;
		placeholder?: string;
		required?: boolean;
		value: string;
	} ) => {
		const accessibleLabel = label || placeholder || 'Text field';
		const id = `text-${ accessibleLabel
			.toLowerCase()
			.replace( /\W+/g, '-' ) }`;
		return (
			<>
				<label htmlFor={ id }>{ accessibleLabel }</label>
				<input
					id={ id }
					required={ required }
					value={ value }
					onChange={ ( event ) => onChange( event.target.value ) }
				/>
			</>
		);
	},
	ToggleControl: ( {
		checked,
		onChange,
	}: {
		checked: boolean;
		onChange: () => void;
	} ) => (
		<>
			<label htmlFor="toggle-location">Toggle location</label>
			<input
				id="toggle-location"
				type="checkbox"
				checked={ checked }
				onChange={ onChange }
			/>
		</>
	),
} ) );

jest.mock( '../settings-context', () => ( {
	useSettingsContext: jest.fn(),
} ) );

jest.mock( '../utils', () => ( {
	getUserFriendlyAddress: jest.fn( ( address: Record< string, string > ) =>
		Object.values( address ).filter( Boolean ).join( ', ' )
	),
	states: {
		US: { CA: 'California' },
		GB: {},
	},
	countryStateOptions: {
		options: [
			{
				label: 'United States',
				options: [
					{ value: 'US:CA', label: 'United States — California' },
				],
			},
			{
				options: [ { value: 'GB', label: 'United Kingdom' } ],
			},
		],
	},
} ) );

const mockUseSettingsContext = useSettingsContext as jest.MockedFunction<
	typeof useSettingsContext
>;

const location: SortablePickupLocation = {
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

const getContext = (
	overrides: Partial< SettingsContextType > = {}
): SettingsContextType => ( {
	settings: {
		enabled: true,
		title: 'Pickup',
		tax_status: 'taxable',
		cost: '',
	},
	readOnlySettings: {
		hasLegacyPickup: false,
		storeCountry: 'US',
		storeState: 'CA',
	},
	setSettingField: jest.fn( () => jest.fn() ),
	pickupLocations: [ location ],
	setPickupLocations: jest.fn(),
	toggleLocation: jest.fn(),
	updateLocation: jest.fn(),
	isSaving: false,
	save: jest.fn(),
	isDirty: false,
	...overrides,
} );

describe( 'LocationSettings', () => {
	it( 'adds a location with the dialog field values', async () => {
		const user = userEvent.setup();
		const updateLocation = jest.fn();
		mockUseSettingsContext.mockReturnValue(
			getContext( { pickupLocations: [], updateLocation } )
		);
		render( <LocationSettings /> );

		await act( async () => {
			await user.click(
				screen.getByRole( 'button', { name: 'Add pickup location' } )
			);
		} );
		await act( async () => {
			await user.type(
				screen.getByRole( 'textbox', { name: /Location name/ } ),
				'Downtown'
			);
		} );
		await act( async () => {
			await user.type(
				screen.getByRole( 'textbox', { name: 'Address' } ),
				'10 Market Street'
			);
		} );
		await act( async () => {
			await user.type(
				screen.getByRole( 'textbox', { name: 'City' } ),
				'San Francisco'
			);
		} );
		await act( async () => {
			await user.type(
				screen.getByRole( 'textbox', { name: 'Postcode / ZIP' } ),
				'94105'
			);
		} );
		await act( async () => {
			await user.selectOptions(
				screen.getByRole( 'combobox', { name: 'Country / State' } ),
				'US:CA'
			);
		} );
		await act( async () => {
			await user.type(
				screen.getByRole( 'textbox', { name: 'Pickup details' } ),
				'Ask at reception'
			);
		} );
		await act( async () => {
			await user.click( screen.getByRole( 'button', { name: 'Done' } ) );
		} );

		expect( updateLocation ).toHaveBeenCalledWith( 'new', {
			name: 'Downtown',
			details: 'Ask at reception',
			enabled: true,
			address: {
				address_1: '10 Market Street',
				city: 'San Francisco',
				state: 'CA',
				postcode: '94105',
				country: 'US',
			},
		} );
	} );

	it( 'edits a location using its existing ID and dialog payload', async () => {
		const user = userEvent.setup();
		const updateLocation = jest.fn();
		mockUseSettingsContext.mockReturnValue(
			getContext( { updateLocation } )
		);
		render( <LocationSettings /> );

		await act( async () => {
			await user.click( screen.getByRole( 'button', { name: 'Edit' } ) );
		} );
		const name = screen.getByRole( 'textbox', {
			name: /Location name/,
		} );
		await act( async () => {
			await user.clear( name );
		} );
		await act( async () => {
			await user.type( name, 'London office' );
		} );
		await act( async () => {
			await user.selectOptions(
				screen.getByRole( 'combobox', { name: 'Country / State' } ),
				'GB'
			);
		} );
		await act( async () => {
			await user.click( screen.getByRole( 'button', { name: 'Done' } ) );
		} );

		expect( updateLocation ).toHaveBeenCalledWith( 'warehouse-0', {
			...location,
			name: 'London office',
			address: {
				...location.address,
				state: '',
				country: 'GB',
			},
		} );
	} );

	it( 'deletes a location using its existing ID', async () => {
		const user = userEvent.setup();
		const updateLocation = jest.fn();
		mockUseSettingsContext.mockReturnValue(
			getContext( { updateLocation } )
		);
		render( <LocationSettings /> );

		await act( async () => {
			await user.click( screen.getByRole( 'button', { name: 'Edit' } ) );
		} );
		await act( async () => {
			await user.click(
				screen.getByRole( 'button', { name: 'Delete location' } )
			);
		} );

		expect( updateLocation ).toHaveBeenCalledWith( 'warehouse-0', null );
	} );

	it( 'toggles a location using its existing ID', async () => {
		const user = userEvent.setup();
		const toggleLocation = jest.fn();
		mockUseSettingsContext.mockReturnValue(
			getContext( { toggleLocation } )
		);
		render( <LocationSettings /> );

		await user.click(
			screen.getByRole( 'checkbox', { name: 'Toggle location' } )
		);

		expect( toggleLocation ).toHaveBeenCalledWith( 'warehouse-0' );
	} );
} );
