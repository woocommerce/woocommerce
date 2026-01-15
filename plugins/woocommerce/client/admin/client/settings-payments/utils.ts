/**
 * External dependencies
 */
import {
	PaymentsProvider,
	PaymentsProviderIncentive,
	RecommendedPaymentMethod,
} from '@woocommerce/data';
import { getAdminLink } from '@woocommerce/settings';
import { recordEvent } from '@woocommerce/tracks';
import { parseAdminUrl } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { getAdminSetting } from '~/utils/admin-settings';
import {
	wooPaymentsProviderId,
	wooPaymentsProviderSuggestionId,
	wooPaymentsSuggestionId,
} from '~/settings-payments/constants';

/**
 * Checks whether a payment provider has an incentive.
 */
export const hasIncentive = ( extension: PaymentsProvider ) => {
	return !! extension._incentive;
};

/**
 * Checks whether an incentive is an action incentive.
 */
export const isActionIncentive = (
	incentive: PaymentsProviderIncentive | undefined
) => {
	if ( ! incentive ) {
		return false;
	}

	return incentive.promo_id.includes( '-action-' );
};

/**
 * Checks whether an incentive is a switch incentive.
 */
export const isSwitchIncentive = (
	incentive: PaymentsProviderIncentive | undefined
) => {
	if ( ! incentive ) {
		return false;
	}

	return incentive.promo_id.includes( '-switch-' );
};

/**
 * Checks whether an incentive is dismissed in a given context.
 */
export const isIncentiveDismissedInContext = (
	incentive: PaymentsProviderIncentive | undefined,
	context: string
) => {
	if ( ! incentive || ! Array.isArray( incentive._dismissals ) ) {
		return false;
	}

	return incentive._dismissals.some(
		( dismissal ) =>
			dismissal.context === 'all' || dismissal.context === context
	);
};

/**
 * Checks whether an incentive is dismissed in a given context and if it was dismissed before a given reference timestamp.
 */
export const isIncentiveDismissedEarlierThanTimestamp = (
	incentive: PaymentsProviderIncentive | undefined,
	context: string,
	referenceTimestampMs: number // UNIX timestamp in milliseconds.
): boolean => {
	if ( ! incentive || ! Array.isArray( incentive._dismissals ) ) {
		return false;
	}

	// Check if the dismissal happened before the provided reference timestamp.
	return incentive._dismissals.some( ( dismissal ) => {
		const dismissalTimestampMs = dismissal.timestamp * 1000; // Convert to milliseconds if stored in seconds
		return (
			( dismissal.context === 'all' || dismissal.context === context ) &&
			dismissalTimestampMs < referenceTimestampMs
		);
	} );
};

/**
 * Handles enabling WooPayments and redirection based on Jetpack connection status.
 */
export const parseScriptTag = ( elementId: string ) => {
	const scriptTag = document.getElementById( elementId );
	return scriptTag ? JSON.parse( scriptTag.textContent || '' ) : [];
};

export const isWooPayments = ( id: string ) => {
	return [
		wooPaymentsProviderSuggestionId,
		wooPaymentsProviderId,
		wooPaymentsSuggestionId,
	].includes( id );
};

/**
 * Checks whether a provider is WooPayments and that it is eligible for WooPay.
 */
export const isWooPayEligible = ( provider: PaymentsProvider ) => {
	return (
		isWooPayments( provider.id ) &&
		( provider.tags?.includes( 'woopay_eligible' ) || false )
	);
};

export const getWooPaymentsTestDriveAccountLink = () => {
	return getAdminLink(
		'admin.php?wcpay-connect=1&_wpnonce=' +
			getAdminSetting( 'wcpay_welcome_page_connect_nonce' ) +
			'&test_drive=true&auto_start_test_drive_onboarding=true&redirect_to_settings_page=true'
	);
};

export const getWooPaymentsSetupLiveAccountLink = () => {
	return getAdminLink(
		'admin.php?wcpay-connect=1&_wpnonce=' +
			getAdminSetting( 'wcpay_welcome_page_connect_nonce' ) +
			'&wcpay-disable-onboarding-test-mode=true&redirect_to_settings_page=true&source=wcpay-setup-live-payments'
	);
};

export const getPaymentMethodById =
	( id: string ) => ( providers: RecommendedPaymentMethod[] ) => {
		return providers.find( ( provider ) => provider.id === id ) || null;
	};

/**
 * Checks whether providers contain WooPayments gateway in test mode that is set up.
 *
 * @param providers payment providers
 */
export const providersContainWooPaymentsInTestMode = (
	providers: PaymentsProvider[]
): boolean => {
	const wooPayments = providers.find( ( obj ) => isWooPayments( obj.id ) );
	return (
		!! wooPayments?.state?.test_mode && ! wooPayments?.state?.needs_setup
	);
};

/**
 * Checks whether providers contain WooPayments gateway that needs set up.
 *
 * @param providers Payment providers
 */
export const providersContainWooPaymentsNeedsSetup = (
	providers: PaymentsProvider[]
): boolean => {
	const wooPayments = providers.find( ( obj ) => isWooPayments( obj.id ) );
	return wooPayments?.state?.needs_setup || false;
};

/**
 * Return the WooPayments gateway if it exists in the providers list.
 *
 * @param providers payment providers
 */
export const getWooPaymentsFromProviders = (
	providers: PaymentsProvider[]
): PaymentsProvider | null => {
	return providers.find( ( obj ) => isWooPayments( obj.id ) ) ?? null;
};

/**
 * Retrieves updated recommended payment methods for WooPayments.
 *
 * @param {PaymentsProvider[]} providers Array of updated payment providers.
 * @return {RecommendedPaymentMethod[]} List of recommended payment methods.
 */
export const getRecommendedPaymentMethods = (
	providers: PaymentsProvider[]
): RecommendedPaymentMethod[] => {
	const updatedWooPaymentsProvider = providers.find(
		( provider: PaymentsProvider ) => isWooPayments( provider.id )
	);

	return (
		updatedWooPaymentsProvider?.onboarding?.recommended_payment_methods ??
		( [] as RecommendedPaymentMethod[] )
	);
};

/**
 * Checks whether providers contain WooPayments gateway in dev mode that is set up.
 *
 * @param providers payment providers
 */
export const providersContainWooPaymentsInDevMode = (
	providers: PaymentsProvider[]
): boolean => {
	const wooPayments = providers.find( ( obj ) => isWooPayments( obj.id ) );
	return !! wooPayments?.state?.dev_mode;
};

/**
 * Combines Apple Pay and Google Pay into a single payment method.
 *
 * If both Apple Pay and Google Pay exist in the list of payment methods, they are combined into a single
 * method with the ID `apple_google`, including data from both methods. If either is missing, the original
 * list is returned.
 */
export const combineRequestMethods = (
	paymentMethods: RecommendedPaymentMethod[]
) => {
	const applePay = getPaymentMethodById( 'apple_pay' )( paymentMethods );
	const googlePay = getPaymentMethodById( 'google_pay' )( paymentMethods );

	if ( ! applePay || ! googlePay ) {
		return paymentMethods; // If either Apple Pay or Google Pay is not found, return the original paymentMethods
	}

	return paymentMethods
		.map( ( method ) => {
			if ( method.id === 'apple_pay' ) {
				// Combine apple_pay and google_pay data into a new payment method
				return {
					...method,
					id: 'apple_google',
					extraTitle: googlePay.title,
					extraDescription: googlePay.description,
					extraIcon: googlePay.icon,
				};
			}

			// Exclude GooglePay from the list
			if ( method.id === 'google_pay' ) {
				return null;
			}

			return method; // Keep the rest of the payment methods
		} )
		.filter(
			( method ): method is RecommendedPaymentMethod => method !== null
		); // Filter null values
};

/**
 * Combines Apple Pay and Google Pay into a single state.
 *
 * If both Apple Pay and Google Pay exist in the list of payment methods, they are combined into a single
 * state with the ID `apple_google`, including data from both methods. If either is missing, the original
 * state is returned.
 */
export const combinePaymentMethodsState = (
	paymentMethodsState: Record< string, boolean >
) => {
	return Object.keys( paymentMethodsState ).reduce( ( acc, key ) => {
		if ( key === 'apple_pay' || key === 'google_pay' ) {
			acc.apple_google = paymentMethodsState[ key ];
		} else {
			acc[ key ] = paymentMethodsState[ key ];
		}
		return acc;
	}, {} as Record< string, boolean > );
};

/**
 * Checks whether a payment method should be rendered.
 */
export const shouldRenderPaymentMethodInMainList = (
	method: RecommendedPaymentMethod,
	method_enabled: boolean
) => {
	// Starting from WooPayments 9.2, the method has a category which we use to determine if it should be rendered.
	if ( method.category === 'primary' ) {
		return true;
	}

	// However, in WooPayments < 9.2, we use the `enabled` property (returned from the server) to determine if the method should be rendered.
	if ( method.enabled ) {
		return true;
	}

	return method_enabled ?? false;
};

/**
 * Records a payments-related event with the WooCommerce Tracks system.
 *
 * This function ensures that the event name starts with 'settings_payments_'.
 *
 * @param eventName The partial name of the event to record.
 *                  This should be a string that represents the specific event being tracked,
 *                  such as 'gateway_enabled' or 'incentive_accepted'.
 *                  Event names should focus on the action or outcome, e.g., 'started' not 'start'.
 * @param data      An object containing additional data to be sent with the event.
 */
export const recordPaymentsEvent = (
	eventName: string,
	data: Record< string, string | boolean | number > = {}
) => {
	// Ensure the event name starts with 'settings_payments_'.
	if ( ! eventName.startsWith( 'settings_payments_' ) ) {
		eventName = `settings_payments_${ eventName }`;
	}

	// Capture the business registration country code from the WooCommerce settings if not provided.
	if ( ! data.business_country ) {
		data.business_country =
			window.wcSettings?.admin?.woocommerce_payments_nox_profile
				?.business_country_code ?? 'unknown';
	}

	recordEvent( eventName, data );
};

/**
 * Records a payments-provider-related event with the WooCommerce Tracks system.
 *
 * This function ensures that the event name starts with 'settings_payments_provider_'.
 *
 * @param eventName The partial name of the event to record.
 *                  This should be a string that represents the specific event being tracked,
 *                  such as 'enabled' or 'incentive_accepted'.
 *                  Event names should focus on the action or outcome, e.g., 'started' not 'start'.
 * @param provider  The payments provider for which the event is being recorded.
 * @param data      An object containing additional data to be sent with the event.
 */
export const recordPaymentsProviderEvent = (
	eventName: string,
	provider: PaymentsProvider,
	data: Record< string, string | boolean | number > = {}
) => {
	// Ensure the event name starts with 'provider_'.
	// The rest of the prefixing is handled by `recordPaymentsEvent`.
	if ( ! eventName.startsWith( 'provider_' ) ) {
		eventName = `provider_${ eventName }`;
	}

	const enrichedData: Record< string, string | boolean | number > = {
		...data,
		provider_id: provider.id,
		provider_type: provider._type ?? 'unknown',
		suggestion_id: provider._suggestion_id ?? 'unknown',
	};

	// Capture the provider state.
	enrichedData.provider_enabled = provider.state?.enabled ?? false;
	enrichedData.provider_account_connected =
		provider.state?.account_connected ?? false;
	enrichedData.provider_needs_setup = provider.state?.needs_setup ?? false;
	enrichedData.provider_test_mode = provider.state?.test_mode ?? false;
	enrichedData.provider_dev_mode = provider.state?.dev_mode ?? false;
	// The provider onboarding state.
	enrichedData.provider_onboarding_started =
		provider.onboarding?.state?.started ?? false;
	enrichedData.provider_onboarding_completed =
		provider.onboarding?.state?.completed ?? false;
	enrichedData.provider_account_test_mode =
		provider.onboarding?.state?.test_mode ?? false;
	// The provider extension data.
	enrichedData.provider_extension_slug = provider.plugin.slug ?? 'unknown';
	// WooPayments-specific data.
	if ( isWooPayments( provider.id ) ) {
		enrichedData.provider_has_test_drive_account =
			provider.onboarding?.state?.test_drive_account ?? false;
		enrichedData.provider_has_working_wpcom_connection =
			provider.onboarding?.state?.wpcom_has_working_connection ?? false;
	}

	recordPaymentsEvent( eventName, enrichedData );
};

/**
 * Records a payments onboarding-related event with the WooCommerce Tracks system.
 *
 * This function ensures that the event name starts with 'settings_payments_' and attaches contextual data
 * such as the `source` and `from` parameters from the URL if they are not provided in the data object.
 *
 * @param eventName The partial name of the event to record.
 *                  This should be a string that represents the specific event being tracked,
 *                  such as 'onboarding_started' or 'gateway_configured'.
 *                  Event names should focus on the action or outcome, e.g., 'started' not 'start'.
 *                  Event names are best to include the provider or gateway id, e.g., 'woopayments_onboarding_started'.
 * @param data      An object containing additional data to be sent with the event.
 */
export const recordPaymentsOnboardingEvent = (
	eventName: string,
	data: Record< string, string | boolean | number > = {}
) => {
	// Ensure the event name starts with 'settings_payments_'.
	if ( ! eventName.startsWith( 'settings_payments_' ) ) {
		eventName = `settings_payments_${ eventName }`;
	}

	// Capture the business registration country code from the WooCommerce settings if not provided.
	if ( ! data.business_country ) {
		data.business_country =
			window.wcSettings?.admin?.woocommerce_payments_nox_profile
				?.business_country_code ?? 'unknown';
	}

	// Capture the onboarding flow `source` from the URL parameters, if not provided.
	const urlParams = new URLSearchParams( window.location.search );
	if ( ! data.source ) {
		data.source =
			urlParams.get( 'source' )?.replace( /[^\w-]+/g, '' ) || 'unknown';
	}
	// We should not carry over the `from` parameter from the URL,
	// as it meant to indicate the immediate source of the action that triggered the event.

	recordEvent( eventName, data );
};

/**
 * Strips the origin from a URL. This is used for front-end navigation using react-router-dom.
 *
 * @example
 * ```
 * removeOriginFromURL( 'https://example.com/wp-admin/admin.php?page=wc-settings&tab=checkout&path=/offline' )
 * // returns '/wp-admin/admin.php?page=wc-settings&tab=checkout&path=/offline'
 * ```
 *
 * @param url The URL to strip the origin from.
 * @return The URL with the origin stripped.
 */
export const removeOriginFromURL = ( url: string ) => {
	const parsedUrl = parseAdminUrl( url );
	return parsedUrl.href?.replace( parsedUrl.origin, '' ) ?? url;
};
