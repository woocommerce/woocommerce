/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Card, CardBody } from '@wordpress/components';
import {
	OPTIONS_STORE_NAME,
	PAYMENT_GATEWAYS_STORE_NAME,
	PLUGINS_STORE_NAME,
} from '@woocommerce/data';
import { Plugins, Stepper } from '@woocommerce/components';
import { WooPaymentGatewaySetup } from '@woocommerce/onboarding';
import { recordEvent } from '@woocommerce/tracks';
import { useEffect, useState, useMemo } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { useSlot } from '@woocommerce/experimental';

/**
 * Internal dependencies
 */
import { createNoticesFromResponse } from '~/lib/notices';
import { enqueueScript } from '~/utils/enqueue-script';
import { Configure } from './Configure';
import './Setup.scss';

export const Setup = ( { markConfigured, paymentGateway } ) => {
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

	const { isOptionUpdating, isPaymentGatewayResolving, needsPluginInstall } =
		useSelect( ( select ) => {
			const { isOptionsUpdating } = select( OPTIONS_STORE_NAME );
			const { isResolving } = select( PAYMENT_GATEWAYS_STORE_NAME );
			const activePlugins =
				select( PLUGINS_STORE_NAME ).getActivePlugins();
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

	const installStep = useMemo( () => {
		return plugins && plugins.length
			? {
					key: 'install',
					label: sprintf(
						/* translators: %s = title of the payment gateway to install */
						__( 'Install %s', 'woocommerce' ),
						title
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
				/* translators: %s = title of the payment gateway to install */
				__( 'Configure your %(title)s account', 'woocommerce' ),
				{
					title,
				}
			),
			content: gatewayInstalled ? (
				<Configure
					markConfigured={ markConfigured }
					paymentGateway={ paymentGateway }
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

	const defaultStepper = (
		<Stepper
			isVertical
			isPending={ stepperPending }
			currentStep={ needsPluginInstall ? 'install' : 'configure' }
			steps={ [ installStep, configureStep ].filter( Boolean ) }
		/>
	);

	return (
		<Card className="woocommerce-task-payment-method woocommerce-task-card">
			<CardBody>
				{ hasFills ? (
					<WooPaymentGatewaySetup.Slot
						fillProps={ {
							defaultStepper,
							defaultInstallStep: installStep,
							defaultConfigureStep: configureStep,
							markConfigured: () => markConfigured( id ),
							paymentGateway,
						} }
						id={ id }
					/>
				) : (
					defaultStepper
				) }
			</CardBody>
		</Card>
	);
};
