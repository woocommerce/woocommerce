/**
 * External dependencies
 */
import { difference } from 'lodash';
import { useSelect } from '@wordpress/data';
import { Spinner } from '@woocommerce/components';
import { pluginsStore, settingsStore } from '@woocommerce/data';

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
		const { getSettings } = select( settingsStore );
		const { getActivePlugins, hasFinishedResolution } =
			select( pluginsStore );
		const activePlugins = getActivePlugins();

		return {
			generalSettings: getSettings( 'general' ).general,
			isJetpackConnected: select( pluginsStore ).isJetpackConnected(),
			isResolving:
				! hasFinishedResolution( 'isJetpackConnected', undefined ) ||
				! select( settingsStore ).hasFinishedResolution(
					'getSettings',
					[ 'general' ]
				) ||
				! hasFinishedResolution( 'getActivePlugins', undefined ),
			pluginsToActivate: difference( AUTOMATION_PLUGINS, activePlugins ),
		};
	}, [] );

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
