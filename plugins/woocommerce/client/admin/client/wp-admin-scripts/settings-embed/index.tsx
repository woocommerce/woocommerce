/**
 * This file is used to enhance the settings page with additional features such as registering slot fills.
 */

/**
 * External dependencies
 */
import { createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { isFeatureEnabled } from '~/utils/features';
import {
	SettingsPaymentsMainWrapper,
	SettingsPaymentsOfflineWrapper,
	SettingsPaymentsWooCommercePaymentsWrapper,
} from '../../settings-payments';

import { possiblyRenderSettingsSlots } from '../../settings/settings-slots';
import { registerTaxSettingsConflictErrorFill } from '../../settings/conflict-error-slotfill';
import { registerPaymentsSettingsBannerFill } from '../../payments/payments-settings-banner-slotfill';
import { registerSiteVisibilitySlotFill } from '../../launch-your-store';
import { registerBlueprintSlotfill } from '../../blueprint';
import { registerSettingsEmailColorPaletteFill } from '../../settings-email/settings-email-color-palette-slotfill';
import { registerSettingsEmailImageUrlFill } from '../../settings-email/settings-email-image-url-slotfill';
import { registerSettingsEmailPreviewFill } from '../../settings-email/settings-email-preview-slotfill';
import { registerSettingsEmailFeedbackFill } from '~/settings-email/settings-email-feedback-slotfill';

const renderPaymentsSettings = () => {
	if (
		! window.wcAdminFeatures ||
		window.wcAdminFeatures[ 'reactify-classic-payments-settings' ] !== true
	) {
		// Render the payment settings components only if the feature flag is enabled.
		return;
	}

	const paymentsMainRoot = document.getElementById(
		'experimental_wc_settings_payments_main'
	);
	const paymentsOfflineRoot = document.getElementById(
		'experimental_wc_settings_payments_offline'
	);
	const paymentsWooCommercePaymentsRoot = document.getElementById(
		'experimental_wc_settings_payments_woocommerce_payments'
	);

	if ( paymentsMainRoot ) {
		createRoot(
			paymentsMainRoot.insertBefore(
				document.createElement( 'div' ),
				null
			)
		).render( <SettingsPaymentsMainWrapper /> );
	}

	if ( paymentsOfflineRoot ) {
		createRoot(
			paymentsOfflineRoot.insertBefore(
				document.createElement( 'div' ),
				null
			)
		).render( <SettingsPaymentsOfflineWrapper /> );
	}

	if ( paymentsWooCommercePaymentsRoot ) {
		createRoot(
			paymentsWooCommercePaymentsRoot.insertBefore(
				document.createElement( 'div' ),
				null
			)
		).render( <SettingsPaymentsWooCommercePaymentsWrapper /> );
	}
};

const registerSlotFills = () => {
	possiblyRenderSettingsSlots();
	registerTaxSettingsConflictErrorFill();
	registerPaymentsSettingsBannerFill();

	const features = window.wcAdminFeatures;
	if ( features?.[ 'launch-your-store' ] === true ) {
		registerSiteVisibilitySlotFill();
	}

	if ( isFeatureEnabled( 'blueprint' ) ) {
		registerBlueprintSlotfill();
	}

	if ( isFeatureEnabled( 'email_improvements' ) ) {
		registerSettingsEmailPreviewFill( true );
		registerSettingsEmailColorPaletteFill();
		registerSettingsEmailImageUrlFill();
	} else {
		registerSettingsEmailPreviewFill( false );
	}

	registerSettingsEmailFeedbackFill();
};

renderPaymentsSettings();
registerSlotFills();
