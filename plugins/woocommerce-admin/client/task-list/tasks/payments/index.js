/**
 * External dependencies
 */
import classnames from 'classnames';
import { Card, CardBody, CardMedia, CardFooter } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { H } from '@woocommerce/components';
import { getHistory, getNewPath } from '@woocommerce/navigation';
import {
	ONBOARDING_STORE_NAME,
	OPTIONS_STORE_NAME,
	PLUGINS_STORE_NAME,
	SETTINGS_STORE_NAME,
} from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { useMemo, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { Action } from './action';
import { getCountryCode } from '../../../dashboard/utils';
import { getPaymentMethods } from './methods';
import { RecommendedRibbon } from './recommended-ribbon';
import { Setup } from './setup';

export const setMethodEnabledOption = async (
	optionName,
	value,
	{ clearTaskStatusCache, updateOptions, options }
) => {
	const methodOptions = options[ optionName ];

	// Don't update the option if it already has the same value.
	if ( methodOptions.enabled !== value ) {
		await updateOptions( {
			[ optionName ]: {
				...methodOptions,
				enabled: value,
			},
		} );

		clearTaskStatusCache();
	}
};

export const Payments = ( { query } ) => {
	const { createNotice } = useDispatch( 'core/notices' );
	const {
		installAndActivatePlugins,
		invalidateResolutionForStoreSelector: invalidatePluginStoreSelector,
	} = useDispatch( PLUGINS_STORE_NAME );
	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );
	const {
		invalidateResolution,
		invalidateResolutionForStoreSelector,
	} = useDispatch( ONBOARDING_STORE_NAME );

	const { methods, options } = useSelect( ( select ) => {
		const { getProfileItems } = select( ONBOARDING_STORE_NAME );
		const { getOption } = select( OPTIONS_STORE_NAME );
		const {
			getActivePlugins,
			isJetpackConnected,
			getPaypalOnboardingStatus,
			hasFinishedResolution,
		} = select( PLUGINS_STORE_NAME );
		const { getSettings } = select( SETTINGS_STORE_NAME );
		const { general: generalSettings = {} } = getSettings( 'general' );
		const { getTasksStatus } = select( ONBOARDING_STORE_NAME );

		const activePlugins = getActivePlugins();
		const onboardingStatus = getTasksStatus();
		const profileItems = getProfileItems();

		const optionNames = [
			'woocommerce_woocommerce_payments_settings',
			'woocommerce_stripe_settings',
			'woocommerce-ppcp-settings',
			'woocommerce_ppcp-gateway_settings',
			'woocommerce_payfast_settings',
			'woocommerce_square_credit_card_settings',
			'woocommerce_klarna_payments_settings',
			'woocommerce_kco_settings',
			'wc_square_refresh_tokens',
			'woocommerce_cod_settings',
			'woocommerce_bacs_settings',
			'woocommerce_bacs_accounts',
			'woocommerce_eway_settings',
			'woocommerce_razorpay_settings',
			'woocommerce_mollie_payments_settings',
			'woocommerce_payubiz_settings',
			'woocommerce_paystack_settings',
			'woocommerce_woo-mercado-pago-basic_settings',
		];

		const retrievedOptions = optionNames.reduce( ( result, name ) => {
			result[ name ] = getOption( name );
			return result;
		}, {} );
		const countryCode = getCountryCode(
			generalSettings.woocommerce_default_country
		);

		const paypalOnboardingStatus = activePlugins.includes(
			'woocommerce-paypal-payments'
		)
			? getPaypalOnboardingStatus()
			: null;

		return {
			methods: getPaymentMethods( {
				activePlugins,
				countryCode,
				createNotice,
				installAndActivatePlugins,
				isJetpackConnected: isJetpackConnected(),
				onboardingStatus,
				options: retrievedOptions,
				profileItems,
				paypalOnboardingStatus,
				loadingPaypalStatus:
					! hasFinishedResolution( 'getPaypalOnboardingStatus' ) &&
					! paypalOnboardingStatus,
			} ),
			options: retrievedOptions,
		};
	} );

	const [ enabledMethods, setEnabledMethods ] = useState(
		methods.reduce( ( acc, method ) => {
			acc[ method.key ] = method.isEnabled;
			return acc;
		}, {} )
	);

	const recommendedMethod = useMemo( () => {
		const method = methods.find(
			( m ) =>
				( m.key === 'wcpay' && m.visible ) ||
				( m.key === 'mercadopago' && m.visible )
		);
		if ( ! method ) {
			return 'stripe';
		}
		return method.key;
	}, [ methods ] );

	const markConfigured = async ( methodKey, queryParams = {} ) => {
		const method = methods.find( ( option ) => option.key === methodKey );

		if ( ! method ) {
			throw `Method ${ methodKey } not found in available methods list`;
		}

		setEnabledMethods( {
			...enabledMethods,
			[ methodKey ]: true,
		} );

		const clearTaskStatusCache = () => {
			invalidateResolution( 'getProfileItems', [] );
			invalidateResolution( 'getTasksStatus', [] );
			invalidateResolutionForStoreSelector( 'getTasksStatus' );
			invalidatePluginStoreSelector( 'getPaypalOnboardingStatus' );
		};

		await setMethodEnabledOption( method.optionName, 'yes', {
			clearTaskStatusCache,
			updateOptions,
			options,
		} );

		recordEvent( 'tasklist_payment_connect_method', {
			payment_method: methodKey,
		} );

		getHistory().push(
			getNewPath( { ...queryParams, task: 'payments' }, '/', {} )
		);
	};

	const currentMethod = useMemo( () => {
		if ( ! query.method ) {
			return null;
		}

		const method = methods.find( ( m ) => m.key === query.method );

		if ( ! method ) {
			throw `Current method ${ query.method } not found in available methods list`;
		}

		return method;
	}, [ query ] );

	if ( currentMethod ) {
		return (
			<Setup method={ currentMethod } markConfigured={ markConfigured } />
		);
	}

	return (
		<div className="woocommerce-task-payments">
			{ methods.map( ( method ) => {
				const {
					before,
					container,
					content,
					isConfigured,
					key,
					manageUrl,
					title,
					visible,
					loading,
					onClick,
				} = method;

				if ( ! visible ) {
					return null;
				}

				const classes = classnames(
					'woocommerce-task-payment',
					'woocommerce-task-card',
					! isConfigured && 'woocommerce-task-payment-not-configured',
					'woocommerce-task-payment-' + key
				);

				const isRecommended =
					key === recommendedMethod && ! isConfigured;

				return (
					<Card key={ key } className={ classes }>
						{ isRecommended && key !== 'wcpay' && (
							<RecommendedRibbon methodKey={ key } />
						) }
						<CardMedia isBorderless>{ before }</CardMedia>
						<CardBody>
							<H className="woocommerce-task-payment__title">
								{ title }
								{ isRecommended && key === 'wcpay' && (
									<RecommendedRibbon
										isPill
										methodKey={ key }
									/>
								) }
							</H>
							<div className="woocommerce-task-payment__content">
								{ content }
							</div>
						</CardBody>
						<CardFooter isBorderless>
							<Action
								manageUrl={ manageUrl }
								methodKey={ key }
								hasSetup={ !! container }
								isConfigured={ isConfigured }
								isEnabled={ enabledMethods[ key ] }
								isRecommended={ isRecommended }
								isLoading={ loading }
								markConfigured={ markConfigured }
								onSetup={ () =>
									recordEvent( 'tasklist_payment_setup', {
										options: methods.map(
											( option ) => option.key
										),
										selected: key,
									} )
								}
								onSetupCallback={ onClick }
							/>
						</CardFooter>
					</Card>
				);
			} ) }
		</div>
	);
};

export default Payments;
