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
import { WooRemotePayment } from '@woocommerce/onboarding';
import { recordEvent } from '@woocommerce/tracks';
import { useEffect, useState, useMemo, useCallback } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { useSlot } from '@woocommerce/experimental';

/**
 * Internal dependencies
 */
import { createNoticesFromResponse } from '~/lib/notices';
import { PaymentConnect } from './PaymentConnect';

export const PaymentMethod = ( {
	markConfigured,
	method,
	recordConnectStartEvent,
} ) => {
	const { key, plugins, title } = method;
	const slot = useSlot( `woocommerce_remote_payment_${ key }` );
	const hasFills = Boolean( slot?.fills?.length );
	const [ isPluginLoaded, setIsPluginLoaded ] = useState( false );

	useEffect( () => {
		recordEvent( 'payments_task_stepper_view', {
			payment_method: key,
		} );
	}, [] );

	const {
		isOptionUpdating,
		isPaymentGatewayResolving,
		needsPluginInstall,
		paymentGateway,
	} = useSelect( ( select ) => {
		const { isOptionsUpdating } = select( OPTIONS_STORE_NAME );
		const { getPaymentGateway, isResolving } = select(
			PAYMENT_GATEWAYS_STORE_NAME
		);
		const activePlugins = select( PLUGINS_STORE_NAME ).getActivePlugins();
		const pluginsToInstall = plugins.filter(
			( m ) => ! activePlugins.includes( m )
		);

		return {
			isOptionUpdating: isOptionsUpdating(),
			isPaymentGatewayResolving: isResolving( 'getPaymentGateway', [
				key,
			] ),
			paymentGateway: ! pluginsToInstall.length
				? getPaymentGateway( key )
				: null,
			needsPluginInstall: !! pluginsToInstall.length,
		};
	} );

	useEffect( () => {
		if ( ! paymentGateway ) {
			return;
		}

		const { post_install_scripts: postInstallScripts } = paymentGateway;
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
	}, [ paymentGateway ] );

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
					isComplete: ! needsPluginInstall,
			  }
			: null;
	}, [ needsPluginInstall ] );

	const connectStep = {
		key: 'connect',
		label: sprintf(
			__( 'Connect your %(title)s account', 'woocommerce-admin' ),
			{
				title,
			}
		),
		content: paymentGateway ? (
			<PaymentConnect
				markConfigured={ markConfigured }
				paymentGateway={ paymentGateway }
				recordConnectStartEvent={ recordConnectStartEvent }
			/>
		) : null,
	};

	const stepperPending =
		! installStep.isComplete ||
		isOptionUpdating ||
		isPaymentGatewayResolving ||
		! isPluginLoaded;

	const DefaultStepper = useCallback(
		( props ) => (
			<Stepper
				isVertical
				isPending={ stepperPending }
				currentStep={ installStep.isComplete ? 'connect' : 'install' }
				steps={ [ installStep, connectStep ] }
				{ ...props }
			/>
		),
		[ stepperPending, installStep ]
	);

	return (
		<Card className="woocommerce-task-payment-method woocommerce-task-card">
			<CardBody>
				{ hasFills ? (
					<WooRemotePayment.Slot
						fillProps={ {
							defaultStepper: DefaultStepper,
							defaultInstallStep: installStep,
							defaultConnectStep: connectStep,
						} }
						id={ key }
					/>
				) : (
					<DefaultStepper />
				) }
			</CardBody>
		</Card>
	);
};
