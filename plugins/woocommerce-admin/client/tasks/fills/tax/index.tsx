/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Card, CardBody } from '@wordpress/components';
import { difference } from 'lodash';
import { useDispatch, useSelect } from '@wordpress/data';
import { Spinner } from '@woocommerce/components';
import { getAdminLink } from '@woocommerce/wc-admin-settings';
import {
	ONBOARDING_STORE_NAME,
	OPTIONS_STORE_NAME,
	PLUGINS_STORE_NAME,
	SETTINGS_STORE_NAME,
} from '@woocommerce/data';
import { queueRecordEvent } from '@woocommerce/tracks';
import { registerPlugin } from '@wordpress/plugins';
import { useEffect, useState } from '@wordpress/element';
import { WooOnboardingTask } from '@woocommerce/onboarding';

/**
 * Internal dependencies
 */
import {
	AUTOMATION_PLUGINS,
	hasCompleteAddress,
	redirectToTaxSettings,
	SettingsSelector,
} from './utils';
import { AutomatedTaxes } from './automated-taxes';
import { ConfigurationStepper } from './configuration-stepper';
import { createNoticesFromResponse } from '../../../lib/notices';
import { getCountryCode } from '../../../dashboard/utils';
import './tax.scss';

const Tax = ( { onComplete, query } ) => {
	const [ isPending, setIsPending ] = useState( false );
	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );
	const { createNotice } = useDispatch( 'core/notices' );
	const { updateAndPersistSettingsForGroup } = useDispatch(
		SETTINGS_STORE_NAME
	);
	const {
		generalSettings,
		isJetpackConnected,
		isResolving,
		pluginsToActivate,
		tasksStatus,
		taxSettings,
	} = useSelect( ( select ) => {
		const { getSettings } = select(
			SETTINGS_STORE_NAME
		) as SettingsSelector;
		const { getActivePlugins } = select( PLUGINS_STORE_NAME );
		const activePlugins = getActivePlugins();

		return {
			generalSettings: getSettings( 'general' ).general,
			isJetpackConnected: select(
				PLUGINS_STORE_NAME
			).isJetpackConnected(),
			isResolving:
				! select( PLUGINS_STORE_NAME ).hasFinishedResolution(
					'isJetpackConnected'
				) ||
				! select(
					SETTINGS_STORE_NAME
				).hasFinishedResolution( 'getSettings', [ 'general' ] ) ||
				! select( ONBOARDING_STORE_NAME ).hasFinishedResolution(
					'getTasksStatus'
				),
			pluginsToActivate: difference( AUTOMATION_PLUGINS, activePlugins ),
			// @Todo this should be removed as soon as https://github.com/woocommerce/woocommerce-admin/pull/7841 is merged.
			tasksStatus: select( ONBOARDING_STORE_NAME ).getTasksStatus(),
			taxSettings: getSettings( 'tax' ).tax || {},
		};
	} );

	const supportsAutomatedTaxes = () => {
		const {
			automatedTaxSupportedCountries = [],
			taxJarActivated,
		} = tasksStatus;

		return (
			! taxJarActivated && // WCS integration doesn't work with the official TaxJar plugin.
			automatedTaxSupportedCountries.includes(
				getCountryCode( generalSettings?.woocommerce_default_country )
			)
		);
	};

	const canAutomateTaxes = () => {
		return (
			hasCompleteAddress( generalSettings ) &&
			! pluginsToActivate.length &&
			isJetpackConnected &&
			supportsAutomatedTaxes()
		);
	};

	const onManual = async () => {
		setIsPending( true );
		if ( generalSettings.woocommerce_calc_taxes !== 'yes' ) {
			updateAndPersistSettingsForGroup( 'tax', {
				tax: {
					...taxSettings,
					wc_connect_taxes_enabled: 'no',
				},
			} );
			updateAndPersistSettingsForGroup( 'general', {
				general: {
					...generalSettings,
					woocommerce_calc_taxes: 'yes',
				},
			} )
				.then( () => redirectToTaxSettings() )
				.catch( ( error ) => {
					setIsPending( false );
					createNoticesFromResponse( error );
				} );
		} else {
			redirectToTaxSettings();
		}
	};

	const onAutomate = () => {
		setIsPending( true );
		updateAndPersistSettingsForGroup( 'tax', {
			tax: {
				...taxSettings,
				wc_connect_taxes_enabled: 'yes',
			},
		} );
		updateAndPersistSettingsForGroup( 'general', {
			general: {
				...generalSettings,
				woocommerce_calc_taxes: 'yes',
			},
		} );

		createNotice(
			'success',
			__(
				"You're awesome! One less item on your to-do list ✅",
				'woocommerce-admin'
			)
		);
		onComplete();
	};

	const onDisable = () => {
		setIsPending( true );
		queueRecordEvent( 'tasklist_tax_connect_store', {
			connect: false,
			no_tax: true,
		} );

		updateOptions( {
			woocommerce_no_sales_tax: true,
			woocommerce_calc_taxes: 'no',
		} ).then( () => {
			window.location.href = getAdminLink( 'admin.php?page=wc-admin' );
		} );
	};

	useEffect( () => {
		const { auto } = query;

		if ( auto === 'true' ) {
			onAutomate();
		}
	}, [] );

	if ( isResolving ) {
		return <Spinner />;
	}

	const childProps = {
		isPending,
		onAutomate,
		onManual,
		onDisable,
		supportsAutomatedTaxes: supportsAutomatedTaxes(),
	};

	return (
		<div className="woocommerce-task-tax">
			<Card className="woocommerce-task-card">
				<CardBody>
					{ canAutomateTaxes() ? (
						<AutomatedTaxes { ...childProps } />
					) : (
						<ConfigurationStepper { ...childProps } />
					) }
				</CardBody>
			</Card>
		</div>
	);
};

registerPlugin( 'wc-admin-onboarding-task-tax', {
	scope: 'woocommerce-tasks',
	render: () => (
		<WooOnboardingTask id="tax">
			{ ( { onComplete, query } ) => (
				<Tax onComplete={ onComplete } query={ query } />
			) }
		</WooOnboardingTask>
	),
} );
