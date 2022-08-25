/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { difference } from 'lodash';
import { useSelect } from '@wordpress/data';
import { Spinner } from '@woocommerce/components';
import { PLUGINS_STORE_NAME, SETTINGS_STORE_NAME } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import {
	AUTOMATION_PLUGINS,
	hasCompleteAddress,
	TaxChildProps,
} from '../utils';
import { AutomatedTaxes } from './automated-taxes';
import { Setup } from './setup';

export const WooCommerceTax: React.FC< TaxChildProps > = ( {
	isPending,
	onAutomate,
	onManual,
	onDisable,
} ) => {
	const {
		generalSettings,
		isJetpackConnected,
		isResolving,
		pluginsToActivate,
	} = useSelect( ( select ) => {
		const { getSettings } = select( SETTINGS_STORE_NAME );
		const { getActivePlugins, hasFinishedResolution } =
			select( PLUGINS_STORE_NAME );
		const activePlugins = getActivePlugins();

		return {
			generalSettings: getSettings( 'general' ).general,
			isJetpackConnected:
				select( PLUGINS_STORE_NAME ).isJetpackConnected(),
			isResolving:
				! hasFinishedResolution( 'isJetpackConnected' ) ||
				! select( SETTINGS_STORE_NAME ).hasFinishedResolution(
					'getSettings',
					[ 'general' ]
				) ||
				! hasFinishedResolution( 'getActivePlugins' ),
			pluginsToActivate: difference( AUTOMATION_PLUGINS, activePlugins ),
		};
	} );

	const canAutomateTaxes = () => {
		return (
			hasCompleteAddress( generalSettings || {} ) &&
			! pluginsToActivate.length &&
			isJetpackConnected
		);
	};

	if ( isResolving ) {
		return <Spinner />;
	}

	const childProps = {
		isPending,
		onAutomate,
		onManual,
		onDisable,
	};

	if ( canAutomateTaxes() ) {
		return <AutomatedTaxes { ...childProps } />;
	}

	return <Setup { ...childProps } />;
};
