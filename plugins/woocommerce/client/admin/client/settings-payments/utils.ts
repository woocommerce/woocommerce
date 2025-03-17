/**
 * External dependencies
 */
import {
	PaymentProvider,
	PaymentIncentive,
	RecommendedPaymentMethod,
} from '@woocommerce/data';
import { getAdminLink } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import { getAdminSetting } from '~/utils/admin-settings';

/**
 * Checks whether a payment provider has an incentive.
 */
export const hasIncentive = ( extension: PaymentProvider ) => {
	return !! extension._incentive;
};

/**
 * Checks whether an incentive is an action incentive.
 */
export const isActionIncentive = (
	incentive: PaymentIncentive | undefined
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
	incentive: PaymentIncentive | undefined
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
	incentive: PaymentIncentive | undefined,
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
	incentive: PaymentIncentive | undefined,
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
	return [ '_wc_pes_woopayments', 'woocommerce_payments' ].includes( id );
};

/**
 * Checks whether a provider is WooPayments and that it is eligible for WooPay.
 */
export const isWooPayEligible = ( provider: PaymentProvider ) => {
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

export const getWooPaymentsResetAccountLink = () => {
	return getAdminLink(
		'admin.php?wcpay-connect=1&_wpnonce=' +
			getAdminSetting( 'wcpay_welcome_page_connect_nonce' ) +
			'&wcpay-reset-account=true&redirect_to_settings_page=true'
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
	providers: PaymentProvider[]
): boolean => {
	const wooPayments = providers.find( ( obj ) => isWooPayments( obj.id ) );
	return (
		!! wooPayments?.state?.test_mode && ! wooPayments?.state?.needs_setup
	);
};

/**
 * Return the WooPayments gateway if it exists in the providers list.
 *
 * @param providers payment providers
 */
export const getWooPaymentsFromProviders = (
	providers: PaymentProvider[]
): PaymentProvider | null => {
	return providers.find( ( obj ) => isWooPayments( obj.id ) ) ?? null;
};

/**
 * Retrieves updated recommended payment methods for WooPayments.
 *
 * @param {PaymentProvider[]} providers Array of updated payment providers.
 * @return {RecommendedPaymentMethod[]} List of recommended payment methods.
 */
export const getRecommendedPaymentMethods = (
	providers: PaymentProvider[]
): RecommendedPaymentMethod[] => {
	const updatedWooPaymentsProvider = providers.find(
		( provider: PaymentProvider ) => isWooPayments( provider.id )
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
	providers: PaymentProvider[]
): boolean => {
	const wooPayments = providers.find( ( obj ) => isWooPayments( obj.id ) );
	return !! wooPayments?.state?.dev_mode;
};
