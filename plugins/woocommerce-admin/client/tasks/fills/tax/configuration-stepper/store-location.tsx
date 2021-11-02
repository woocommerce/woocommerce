/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { SETTINGS_STORE_NAME } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { useEffect } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { ConfigurationStepProps } from '.';
import { getCountryCode } from '../../../../dashboard/utils';
import { hasCompleteAddress, SettingsSelector } from '../utils';
import { default as StoreLocationForm } from '../../steps/location';

export const StoreLocation: React.FC< ConfigurationStepProps > = ( {
	isResolving,
	nextStep,
} ) => {
	const { updateAndPersistSettingsForGroup } = useDispatch(
		SETTINGS_STORE_NAME
	);
	const { generalSettings } = useSelect( ( select ) => {
		const { getSettings } = select(
			SETTINGS_STORE_NAME
		) as SettingsSelector;

		return {
			generalSettings: getSettings( 'general' )?.general,
		};
	} );

	useEffect( () => {
		if ( isResolving || ! hasCompleteAddress( generalSettings ) ) {
			return;
		}
		nextStep();
	}, [ isResolving ] );

	if ( isResolving ) {
		return null;
	}

	return (
		<StoreLocationForm
			onComplete={ ( values ) => {
				const country = getCountryCode( values.countryState );
				recordEvent( 'tasklist_tax_set_location', {
					country,
				} );
				nextStep();
			} }
			isSettingsRequesting={ false }
			settings={ generalSettings }
			updateAndPersistSettingsForGroup={
				updateAndPersistSettingsForGroup
			}
		/>
	);
};
