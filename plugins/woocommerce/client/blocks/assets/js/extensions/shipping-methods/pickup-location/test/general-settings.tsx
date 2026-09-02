/**
 * External dependencies
 */
import type { ReactNode } from 'react';
import { act, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import GeneralSettings from '../general-settings';
import { useSettingsContext } from '../settings-context';
import type { SettingsContextType } from '../types';

jest.mock( '@wordpress/components', () => ( {
	Button: ( { children }: { children: ReactNode } ) => (
		<button type="button">{ children }</button>
	),
	Card: ( { children }: { children: ReactNode } ) => <div>{ children }</div>,
	CardBody: ( { children }: { children: ReactNode } ) => (
		<div>{ children }</div>
	),
	CheckboxControl: ( {
		checked,
		label,
		onChange,
	}: {
		checked: boolean;
		label: string;
		onChange: ( checked: boolean ) => void;
	} ) => {
		const id = `checkbox-${ label.toLowerCase().replace( /\W+/g, '-' ) }`;
		return (
			<>
				<label htmlFor={ id }>{ label }</label>
				<input
					id={ id }
					type="checkbox"
					checked={ checked }
					onChange={ ( event ) => onChange( event.target.checked ) }
				/>
			</>
		);
	},
	ExternalLink: ( {
		children,
		href,
	}: {
		children: ReactNode;
		href: string;
	} ) => <a href={ href }>{ children }</a>,
	Notice: ( { children }: { children: ReactNode } ) => (
		<div>{ children }</div>
	),
	Modal: ( { children }: { children: ReactNode } ) => <div>{ children }</div>,
	SelectControl: ( {
		label,
		onChange,
		options,
		value,
	}: {
		label: string;
		onChange: ( value: string ) => void;
		options: { label: string; value: string }[];
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
					{ options.map( ( option ) => (
						<option key={ option.value } value={ option.value }>
							{ option.label }
						</option>
					) ) }
				</select>
			</>
		);
	},
	TextControl: ( {
		label,
		onChange,
		placeholder,
		type = 'text',
		value,
	}: {
		label: string;
		onChange: ( value: string ) => void;
		placeholder?: string;
		type?: string;
		value: string;
	} ) => {
		const id = `text-${ label.toLowerCase().replace( /\W+/g, '-' ) }`;
		return (
			<>
				<label htmlFor={ id }>{ label }</label>
				<input
					id={ id }
					type={ type }
					placeholder={ placeholder }
					value={ value }
					onChange={ ( event ) => onChange( event.target.value ) }
				/>
			</>
		);
	},
	ToggleControl: () => <input type="checkbox" readOnly />,
} ) );

jest.mock( '../settings-context', () => ( {
	useSettingsContext: jest.fn(),
} ) );

const mockUseSettingsContext = useSettingsContext as jest.MockedFunction<
	typeof useSettingsContext
>;

const getContext = (
	overrides: Partial< SettingsContextType > = {}
): SettingsContextType => ( {
	settings: {
		enabled: true,
		title: '',
		tax_status: 'taxable',
		cost: '',
	},
	readOnlySettings: {
		hasLegacyPickup: false,
		storeCountry: 'US',
		storeState: 'CA',
	},
	setSettingField: jest.fn( () => jest.fn() ),
	pickupLocations: [],
	setPickupLocations: jest.fn(),
	toggleLocation: jest.fn(),
	updateLocation: jest.fn(),
	isSaving: false,
	save: jest.fn(),
	isDirty: false,
	...overrides,
} );

describe( 'GeneralSettings', () => {
	it( 'delegates enablement and title changes to their setting fields', async () => {
		const user = userEvent.setup();
		const enabledChanged = jest.fn();
		const titleChanged = jest.fn();
		const setSettingField = jest.fn( ( field ) => {
			return field === 'enabled' ? enabledChanged : titleChanged;
		} );
		mockUseSettingsContext.mockReturnValue(
			getContext( { setSettingField } )
		);

		render( <GeneralSettings /> );

		await user.click(
			screen.getByRole( 'checkbox', { name: 'Enable local pickup' } )
		);
		const title = screen.getByRole( 'textbox', { name: 'Title' } );
		await user.type( title, 'C' );

		expect( setSettingField ).toHaveBeenCalledWith( 'enabled' );
		expect( setSettingField ).toHaveBeenCalledWith( 'title' );
		expect( enabledChanged ).toHaveBeenCalledWith( false );
		expect( titleChanged ).toHaveBeenLastCalledWith( 'C' );
	} );

	it( 'shows price controls and clears cost whenever price visibility changes', async () => {
		const user = userEvent.setup();
		const costChanged = jest.fn();
		const taxStatusChanged = jest.fn();
		const setSettingField = jest.fn( ( field ) => {
			if ( field === 'cost' ) {
				return costChanged;
			}
			if ( field === 'tax_status' ) {
				return taxStatusChanged;
			}
			return jest.fn();
		} );
		mockUseSettingsContext.mockReturnValue(
			getContext( { setSettingField } )
		);

		render( <GeneralSettings /> );

		const showPrice = screen.getByRole( 'checkbox', {
			name: 'Add a price for customers who choose local pickup',
		} );
		expect(
			screen.queryByRole( 'spinbutton', { name: 'Cost' } )
		).not.toBeInTheDocument();
		expect(
			screen.queryByRole( 'combobox', { name: 'Taxes' } )
		).not.toBeInTheDocument();

		await act( async () => {
			await user.click( showPrice );
		} );

		const cost = screen.getByRole( 'spinbutton', { name: 'Cost' } );
		const taxes = screen.getByRole( 'combobox', { name: 'Taxes' } );
		await user.type( cost, '7' );
		await user.selectOptions( taxes, 'none' );

		expect( setSettingField ).toHaveBeenCalledWith( 'cost' );
		expect( setSettingField ).toHaveBeenCalledWith( 'tax_status' );
		expect( costChanged ).toHaveBeenCalledWith( '' );
		expect( costChanged ).toHaveBeenLastCalledWith( '7' );
		expect( taxStatusChanged ).toHaveBeenCalledWith( 'none' );

		await act( async () => {
			await user.click( showPrice );
		} );

		expect( costChanged ).toHaveBeenLastCalledWith( '' );
		expect(
			screen.queryByRole( 'spinbutton', { name: 'Cost' } )
		).not.toBeInTheDocument();
		expect(
			screen.queryByRole( 'combobox', { name: 'Taxes' } )
		).not.toBeInTheDocument();
	} );
} );
