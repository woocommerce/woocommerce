/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { Card, CardBody } from '@wordpress/components';
import { enqueueScript } from '@woocommerce/wc-admin-settings';
import {
	OPTIONS_STORE_NAME,
	PLUGINS_STORE_NAME,
	pluginNames,
} from '@woocommerce/data';
import { Plugins, Stepper, WooRemotePayment } from '@woocommerce/components';
import { recordEvent } from '@woocommerce/tracks';
import { useEffect, useState } from '@wordpress/element';
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
	const [ isFetchingPaymentGateway, setIsFetchingPaymentGateway ] = useState(
		false
	);
	const [ paymentGateway, setPaymentGateway ] = useState( null );

	useEffect( () => {
		recordEvent( 'payments_task_stepper_view', {
			payment_method: key,
		} );
	}, [] );

	const { activePlugins } = useSelect( ( select ) => {
		const { getActivePlugins } = select( PLUGINS_STORE_NAME );

		return {
			activePlugins: getActivePlugins(),
		};
	} );

	const isOptionsRequesting = useSelect( ( select ) => {
		const { isOptionsUpdating } = select( OPTIONS_STORE_NAME );

		return isOptionsUpdating();
	} );

	const pluginsToInstall = plugins.filter(
		( m ) => ! activePlugins.includes( m )
	);

	useEffect( () => {
		if (
			pluginsToInstall.length ||
			paymentGateway ||
			isFetchingPaymentGateway
		) {
			return;
		}
		fetchGateway();
	}, [ pluginsToInstall ] );

	// @todo This should updated to use the data store in https://github.com/woocommerce/woocommerce-admin/pull/6918
	const fetchGateway = () => {
		setIsFetchingPaymentGateway( true );
		apiFetch( {
			path: 'wc/v3/payment_gateways/' + key,
		} ).then( async ( results ) => {
			const { post_install_scripts: postInstallScripts } = results;
			if ( postInstallScripts ) {
				const scriptPromises = postInstallScripts.map( ( script ) =>
					enqueueScript( script )
				);
				await Promise.all( scriptPromises );
			}
			setPaymentGateway( results );
			setIsFetchingPaymentGateway( false );
		} );
	};

	const pluginNamesString = plugins
		.map( ( pluginSlug ) => pluginNames[ pluginSlug ] )
		.join( ' ' + __( 'and', 'woocommerce-admin' ) + ' ' );

	const installStep =
		plugins && plugins.length
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
					isComplete: ! pluginsToInstall.length,
			  }
			: null;

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
				method={ method }
				markConfigured={ markConfigured }
				recordConnectStartEvent={ recordConnectStartEvent }
			/>
		) : null,
	};

	const DefaultStepper = ( props ) => (
		<Stepper
			isVertical
			isPending={
				! installStep.isComplete ||
				isOptionsRequesting ||
				isFetchingPaymentGateway
			}
			currentStep={ installStep.isComplete ? 'connect' : 'install' }
			steps={ [ installStep, connectStep ] }
			{ ...props }
		/>
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
