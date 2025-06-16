/**
 * External dependencies
 */
import {
	createContext,
	useContext,
	useCallback,
	useState,
} from '@wordpress/element';
import { cleanForSlug } from '@wordpress/url';
import type { UniqueIdentifier } from '@dnd-kit/core';
import apiFetch from '@wordpress/api-fetch';
import { dispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import fastDeepEqual from 'fast-deep-equal/es6';
import { store as noticesStore } from '@wordpress/notices';

/**
 * Internal dependencies
 */
import type {
	SortablePickupLocation,
	SettingsContextType,
	ShippingMethodSettings,
} from './types';
import {
	defaultSettings,
	getInitialSettings,
	defaultReadyOnlySettings,
	readOnlySettings,
	getInitialPickupLocations,
} from './utils';

const SettingsContext = createContext< SettingsContextType >( {
	settings: defaultSettings,
	readOnlySettings: defaultReadyOnlySettings,
	setSettingField: () => () => void null,
	pickupLocations: [],
	setPickupLocations: () => void null,
	toggleLocation: () => void null,
	updateLocation: () => void null,
	isSaving: false,
	save: () => void null,
	isDirty: false,
} );

export const useSettingsContext = (): SettingsContextType => {
	return useContext( SettingsContext );
};

export const SettingsProvider = ( {
	children,
}: {
	children: JSX.Element[] | JSX.Element;
} ): JSX.Element => {
	const [ isSaving, setIsSaving ] = useState( false );
	const [ isDirty, setIsDirty ] = useState( false );
	const [ pickupLocations, setPickupLocations ] = useState<
		SortablePickupLocation[]
	>( getInitialPickupLocations );
	const [ settings, setSettings ] =
		useState< ShippingMethodSettings >( getInitialSettings );

	const setSettingField = useCallback(
		( field: keyof ShippingMethodSettings ) => ( newValue: unknown ) => {
			setIsDirty( true );
			setSettings( ( prevValue ) => ( {
				...prevValue,
				[ field ]: newValue,
			} ) );
		},
		[]
	);

	const setPickupLocationsState = useCallback(
		( newLocations: SortablePickupLocation[] ) => {
			setIsDirty( true );
			setPickupLocations( newLocations );
		},
		[]
	);

	const toggleLocation = useCallback( ( rowId: UniqueIdentifier ) => {
		setIsDirty( true );
		setPickupLocations( ( previousLocations: SortablePickupLocation[] ) => {
			const locationIndex = previousLocations.findIndex(
				( { id } ) => id === rowId
			);
			const updated = [ ...previousLocations ];
			updated[ locationIndex ].enabled =
				! previousLocations[ locationIndex ].enabled;
			return updated;
		} );
	}, [] );

	const updateLocation = (
		rowId: UniqueIdentifier | 'new',
		locationData: SortablePickupLocation
	) => {
		setPickupLocations( ( prevData ) => {
			setIsDirty( true );
			if ( rowId === 'new' ) {
				return [
					...prevData,
					{
						...locationData,
						id:
							cleanForSlug( locationData.name ) +
							'-' +
							prevData.length,
					},
				];
			}
			return prevData
				.map( ( location ): SortablePickupLocation => {
					if ( location.id === rowId ) {
						return locationData;
					}
					return location;
				} )
				.filter( Boolean );
		} );
	};

	const save = useCallback( () => {
		const data = {
			pickup_location_settings: {
				enabled: settings.enabled ? 'yes' : 'no',
				title: settings.title,
				tax_status: [ 'taxable', 'none' ].includes(
					settings.tax_status
				)
					? settings.tax_status
					: 'taxable',
				cost: settings.cost,
			},
			pickup_locations: pickupLocations.map( ( location ) => ( {
				name: location.name,
				address: location.address,
				details: location.details,
				enabled: location.enabled,
			} ) ),
		};

		setIsSaving( true );
		setIsDirty( false );

		// @todo This should be improved to include error handling in case of API failure, or invalid data being sent that
		// does not match the schema. This would fail silently on the API side.
		apiFetch( {
			path: '/wp/v2/settings',
			method: 'POST',
			data,
		} ).then( ( response ) => {
			setIsSaving( false );
			if (
				fastDeepEqual(
					response.pickup_location_settings,
					data.pickup_location_settings
				) &&
				fastDeepEqual(
					response.pickup_locations,
					data.pickup_locations
				)
			) {
				dispatch( noticesStore ).createSuccessNotice(
					__(
						'Local Pickup settings have been saved.',
						'woocommerce'
					)
				);
			}
		} );
	}, [ settings, pickupLocations ] );

	const settingsData = {
		settings,
		setSettingField,
		readOnlySettings,
		pickupLocations,
		setPickupLocations: setPickupLocationsState,
		toggleLocation,
		updateLocation,
		isSaving,
		save,
		isDirty,
	};

	return (
		<SettingsContext.Provider value={ settingsData }>
			{ children }
		</SettingsContext.Provider>
	);
};
