/**
 * External dependencies
 */
import { getAdminLink } from '@woocommerce/wc-admin-settings';
import { WPDataSelectors } from '@woocommerce/data';

/**
 * Plugins required to automate taxes.
 */
export const AUTOMATION_PLUGINS = [ 'jetpack', 'woocommerce-services' ];

/**
 * Check if a store has a complete address given general settings.
 *
 * @param {Object} generalSettings General settings.
 */
export const hasCompleteAddress = ( generalSettings ): boolean => {
	const {
		woocommerce_store_address: storeAddress,
		woocommerce_default_country: defaultCountry,
		woocommerce_store_postcode: storePostCode,
	} = generalSettings;
	return Boolean( storeAddress && defaultCountry && storePostCode );
};

/**
 * Redirect to the core tax settings screen.
 */
export const redirectToTaxSettings = (): void => {
	window.location.href = getAdminLink(
		'admin.php?page=wc-settings&tab=tax&section=standard&wc_onboarding_active_task=tax'
	);
};

/**
 * Types for settings selectors.
 */
export type SettingsSelector = WPDataSelectors & {
	getSettings: (
		type: string
	) => { general: { woocommerce_default_country?: string } };
	getOption: (
		type: string
	) => {
		wc_connect_options: {
			tos_accepted: string;
		};
	};
};
