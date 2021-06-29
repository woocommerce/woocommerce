/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Card, CardBody } from '@wordpress/components';
import { enqueueScript } from '@woocommerce/wc-admin-settings';
import {
	OPTIONS_STORE_NAME,
	PAYMENT_GATEWAYS_STORE_NAME,
	PLUGINS_STORE_NAME,
	pluginNames,
} from '@woocommerce/data';
import { Plugins, Stepper } from '@woocommerce/components';
import { WooPaymentGatewaySetup } from '@woocommerce/onboarding';
import { recordEvent } from '@woocommerce/tracks';
import { useEffect, useState, useMemo, useCallback } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { useSlot } from '@woocommerce/experimental';

/**
 * Internal dependencies
 */
import { createNoticesFromResponse } from '~/lib/notices';
import { Configure } from './Configure';
import './Setup.scss';

export const Setup = ( {
	markConfigured,
	paymentGateway,
	recordConnectStartEvent,
} ) => {
	const {
		id,
		plugins = [],
		title,
		postInstallScripts,
		installed: gatewayInstalled,
	} = paymentGateway;
	const slot = useSlot( `woocommerce_payment_gateway_setup_${ id }` );
	const hasFills = Boolean( slot?.fills?.length );
	const [ isPluginLoaded, setIsPluginLoaded ] = useState( false );

	useEffect( () => {
		recordEvent( 'payments_task_stepper_view', {
			payment_method: id,
		} );
	}, [] );

	const { invalidateResolutionForStoreSelector } = useDispatch(
		PAYMENT_GATEWAYS_STORE_NAME
	);

	const {
		isOptionUpdating,
		isPaymentGatewayResolving,
		needsPluginInstall,
	} = useSelect( ( select ) => {
		const { isOptionsUpdating } = select( OPTIONS_STORE_NAME );
		const { isResolving } = select( PAYMENT_GATEWAYS_STORE_NAME );
		const activePlugins = select( PLUGINS_STORE_NAME ).getActivePlugins();
		const pluginsToInstall = plugins.filter(
			( m ) => ! activePlugins.includes( m )
		);

		return {
			isOptionUpdating: isOptionsUpdating(),
			isPaymentGatewayResolving: isResolving( 'getPaymentGateways' ),
			needsPluginInstall: !! pluginsToInstall.length,
		};
	} );

	useEffect( () => {
		if ( needsPluginInstall ) {
			return;
		}

		if ( postInstallScripts && postInstallScripts.length ) {
			const scriptPromises = postInstallScripts.map( ( script ) =>
				enqueueScript( script )
			);
			Promise.all( scriptPromises ).then( () => {
				setIsPluginLoaded( true );
			} );
			return;
		}

		setIsPluginLoaded( true );
	}, [ postInstallScripts, needsPluginInstall ] );

	const pluginNamesString = plugins
		.map( ( pluginSlug ) => pluginNames[ pluginSlug ] )
		.join( ' ' + __( 'and', 'woocommerce-admin' ) + ' ' );

	const installStep = useMemo( () => {
		return plugins && plugins.length
			? {
					key: 'install',
					label: sprintf(
						/* translators: %s = one or more plugin names joined by "and" */
						__( 'Install %s', 'woocommerce-admin' ),
						pluginNamesString
					),
					content: (
						<Plugins
							onComplete={ ( installedPlugins, response ) => {
								createNoticesFromResponse( response );
								invalidateResolutionForStoreSelector(
									'getPaymentGateways'
								);
								recordEvent(
									'tasklist_payment_install_method',
									{
										plugins,
									}
								);
							} }
							onError={ ( errors, response ) =>
								createNoticesFromResponse( response )
							}
							autoInstall
							pluginSlugs={ plugins }
						/>
					),
			  }
			: null;
	}, [] );

	const configureStep = useMemo(
		() => ( {
			key: 'configure',
			label: sprintf(
				__( 'Configure your %(title)s account', 'woocommerce-admin' ),
				{
					title,
				}
			),
			content: gatewayInstalled ? (
				<Configure
					markConfigured={ markConfigured }
					paymentGateway={ paymentGateway }
					recordConnectStartEvent={ recordConnectStartEvent }
				/>
			) : null,
		} ),
		[ gatewayInstalled ]
	);

	const stepperPending =
		needsPluginInstall ||
		isOptionUpdating ||
		isPaymentGatewayResolving ||
		! isPluginLoaded;

	const DefaultStepper = useCallback(
		( props ) => (
			<Stepper
				isVertical
				isPending={ stepperPending }
				currentStep={ needsPluginInstall ? 'install' : 'configure' }
				steps={ [ installStep, configureStep ].filter( Boolean ) }
				{ ...props }
			/>
		),
		[ stepperPending, installStep, configureStep ]
	);

	return (
		<Card className="woocommerce-task-payment-method woocommerce-task-card">
			<CardBody>
				{ hasFills ? (
					<WooPaymentGatewaySetup.Slot
						fillProps={ {
							defaultStepper: DefaultStepper,
							defaultInstallStep: installStep,
							defaultConfigureStep: configureStep,
							markConfigured: () => markConfigured( id ),
							paymentGateway,
						} }
						id={ id }
					/>
				) : (
					<DefaultStepper />
				) }
			</CardBody>
		</Card>
	);
};
