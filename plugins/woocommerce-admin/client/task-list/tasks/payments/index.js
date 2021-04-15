/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useDispatch, useSelect } from '@wordpress/data';
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
import { WCPayCard } from './components/WCPayCard';
import { PaymentMethodList } from './components/PaymentMethodList';
import { getCountryCode } from '../../../dashboard/utils';
import { getPaymentMethods } from './methods';
import { PaymentSetup } from './components/PaymentSetup';
import { sift } from '../../../utils';

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

	const [ enabledMethods, setEnabledMethods ] = useState(
		methods.reduce( ( acc, method ) => {
			acc[ method.key ] = method.isEnabled;
			return acc;
		}, {} )
	);

	if ( currentMethod ) {
		return (
			<PaymentSetup
				method={ currentMethod }
				markConfigured={ markConfigured }
			/>
		);
	}

	const [ enabledCardMethods, additionalCardMethods ] = sift(
		methods,
		( method ) => method.isEnabled && method.isConfigured
	);

	const wcPayIndex = additionalCardMethods.findIndex(
		( m ) => m.key === 'wcpay'
	);

	const wcPayMethod =
		wcPayIndex === -1
			? null
			: additionalCardMethods.splice( wcPayIndex, 1 );

	return (
		<div className="woocommerce-task-payments">
			{ !! wcPayMethod && <WCPayCard method={ wcPayMethod[ 0 ] } /> }

			{ !! enabledCardMethods.length && (
				<PaymentMethodList
					recommendedMethod={ recommendedMethod }
					heading={ __( 'Enabled payment methods', 'wc-admin' ) }
					methods={ enabledCardMethods }
				/>
			) }

			{ !! additionalCardMethods.length && (
				<PaymentMethodList
					recommendedMethod={ recommendedMethod }
					heading={ __( 'Additional payment methods', 'wc-admin' ) }
					methods={ additionalCardMethods }
					markConfigured={ markConfigured }
				/>
			) }
		</div>
	);
};

export default Payments;
