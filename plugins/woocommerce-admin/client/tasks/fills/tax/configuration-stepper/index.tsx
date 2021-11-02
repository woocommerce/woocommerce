/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { difference, filter } from 'lodash';
import { useEffect, useState } from '@wordpress/element';
import { Stepper } from '@woocommerce/components';
import {
	OPTIONS_STORE_NAME,
	PLUGINS_STORE_NAME,
	SETTINGS_STORE_NAME,
} from '@woocommerce/data';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { AUTOMATION_PLUGINS, SettingsSelector } from '../utils';
import { Connect } from './connect';
import { ManualConfiguration } from './manual-configuration';
import { Plugins } from './plugins';
import { StoreLocation } from './store-location';

export type ConfigurationStepperProps = {
	isPending: boolean;
	onDisable: () => void;
	onAutomate: () => void;
	onManual: () => void;
	supportsAutomatedTaxes: boolean;
};

export type ConfigurationStepProps = {
	isPending: boolean;
	isResolving: boolean;
	nextStep: () => void;
	onDisable: () => void;
	onAutomate: () => void;
	onManual: () => void;
	pluginsToActivate: string[];
};

export const ConfigurationStepper: React.FC< ConfigurationStepperProps > = ( {
	isPending,
	onDisable,
	onAutomate,
	onManual,
	supportsAutomatedTaxes,
} ) => {
	const [ pluginsToActivate, setPluginsToActivate ] = useState( [] );
	const {
		activePlugins,
		isJetpackConnected,
		isResolving,
		tosAccepted,
	} = useSelect( ( select ) => {
		const { getSettings } = select(
			SETTINGS_STORE_NAME
		) as SettingsSelector;
		const { getOption, hasFinishedResolution } = select(
			OPTIONS_STORE_NAME
		) as SettingsSelector;
		const { getActivePlugins } = select( PLUGINS_STORE_NAME );

		return {
			activePlugins: getActivePlugins(),
			generalSettings: getSettings( 'general' )?.general,
			isJetpackConnected: select(
				PLUGINS_STORE_NAME
			).isJetpackConnected(),
			isResolving:
				! hasFinishedResolution( 'getOption', [
					'woocommerce_setup_jetpack_opted_in',
				] ) ||
				! hasFinishedResolution( 'getOption', [
					'wc_connect_options',
				] ),
			tosAccepted:
				getOption( 'wc_connect_options' )?.tos_accepted ||
				getOption( 'woocommerce_setup_jetpack_opted_in' ) === '1',
		};
	} );
	const [ stepIndex, setStepIndex ] = useState( 0 );

	useEffect( () => {
		const remainingPlugins = difference(
			AUTOMATION_PLUGINS,
			activePlugins
		);
		if ( remainingPlugins.length <= pluginsToActivate.length ) {
			return;
		}
		setPluginsToActivate( remainingPlugins );
	}, [ activePlugins ] );

	const nextStep = () => {
		setStepIndex( stepIndex + 1 );
	};

	const stepProps = {
		isPending,
		isResolving,
		onAutomate,
		onDisable,
		nextStep,
		onManual,
		pluginsToActivate,
	};

	const getVisibleSteps = () => {
		const allSteps = [
			{
				key: 'store_location',
				label: __( 'Set store location', 'woocommerce-admin' ),
				description: __(
					'The address from which your business operates',
					'woocommerce-admin'
				),
				content: <StoreLocation { ...stepProps } />,
				visible: true,
			},
			{
				key: 'plugins',
				label: pluginsToActivate.includes( 'woocommerce-services' )
					? __(
							'Install Jetpack and WooCommerce Tax',
							'woocommerce-admin'
					  )
					: __( 'Install Jetpack', 'woocommerce-admin' ),
				description: __(
					'Jetpack and WooCommerce Tax allow you to automate sales tax calculations',
					'woocommerce-admin'
				),
				content: <Plugins { ...stepProps } />,
				visible:
					! isResolving &&
					( pluginsToActivate.length || ! tosAccepted ) &&
					supportsAutomatedTaxes,
			},
			{
				key: 'connect',
				label: __( 'Connect your store', 'woocommerce-admin' ),
				description: __(
					'Connect your store to WordPress.com to enable automated sales tax calculations',
					'woocommerce-admin'
				),
				content: <Connect { ...stepProps } />,
				visible:
					! isResolving &&
					! isJetpackConnected &&
					supportsAutomatedTaxes,
			},
			{
				key: 'manual_configuration',
				label: __( 'Configure tax rates', 'woocommerce-admin' ),
				description: __(
					'Head over to the tax rate settings screen to configure your tax rates',
					'woocommerce-admin'
				),
				content: <ManualConfiguration { ...stepProps } />,
				visible: ! supportsAutomatedTaxes,
			},
		];

		return filter( allSteps, ( step ) => step.visible );
	};

	const steps = getVisibleSteps();

	const step = steps[ stepIndex ];

	return (
		<Stepper
			isPending={ isResolving }
			isVertical={ true }
			currentStep={ step.key }
			steps={ steps }
		/>
	);
};
