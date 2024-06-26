/**
 * External dependencies
 */
import { SETTINGS_STORE_NAME } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { useEffect } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { getCountryCode } from '~/dashboard/utils';
import { hasCompleteAddress } from '../utils';
import {
	default as StoreLocationForm,
	FormValues,
	defaultValidate,
} from '~/task-lists/fills/steps/location';

const validateLocationForm = ( values: FormValues ) => {
	const errors = defaultValidate( values );

	if (
		document.getElementById( 'woocommerce-store-address-form-address_1' ) &&
		! values.addressLine1.trim().length
	) {
		errors.addressLine1 = __( 'Please enter an address', 'woocommerce' );
	}

	if (
		document.getElementById( 'woocommerce-store-address-form-postcode' ) &&
		! values.postCode.trim().length
	) {
		errors.postCode = __( 'Please enter a post code', 'woocommerce' );
	}

	if (
		document.getElementById( 'woocommerce-store-address-form-city' ) &&
		! values.city.trim().length
	) {
		errors.city = __( 'Please enter a city', 'woocommerce' );
	}

	return errors;
};

export const StoreLocation: React.FC< {
	nextStep: () => void;
} > = ( { nextStep } ) => {
	const { createNotice } = useDispatch( 'core/notices' );
	const { updateAndPersistSettingsForGroup } =
		useDispatch( SETTINGS_STORE_NAME );
	const { generalSettings, isResolving, isUpdating } = useSelect(
		( select ) => {
			const {
				getSettings,
				hasFinishedResolution,
				isUpdateSettingsRequesting,
			} = select( SETTINGS_STORE_NAME );

			return {
				generalSettings: getSettings( 'general' )?.general,
				isResolving: ! hasFinishedResolution( 'getSettings', [
					'general',
				] ),
				isUpdating: isUpdateSettingsRequesting( 'general' ),
			};
		}
	);

	useEffect( () => {
		if (
			isResolving ||
			isUpdating ||
			! hasCompleteAddress(
				generalSettings || {},
				Boolean(
					document.getElementById(
						'woocommerce-store-address-form-postcode'
					)
				)
			)
		) {
			return;
		}
		nextStep();
	}, [ isResolving, generalSettings, isUpdating ] );

	if ( isResolving ) {
		return null;
	}

	return (
		<StoreLocationForm
			validate={ validateLocationForm }
			onComplete={ ( values: { [ key: string ]: string } ) => {
				const country = getCountryCode( values.countryState );
				recordEvent( 'tasklist_tax_set_location', {
					country,
				} );
			} }
			isSettingsRequesting={ false }
			settings={ generalSettings }
			updateAndPersistSettingsForGroup={
				updateAndPersistSettingsForGroup
			}
			createNotice={ createNotice }
		/>
	);
};
