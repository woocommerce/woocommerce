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
	SettingsPaymentsBacsWrapper,
	SettingsPaymentsChequeWrapper,
	SettingsPaymentsCodWrapper,
	SettingsPaymentsMainWrapper,
	SettingsPaymentsOfflineWrapper,
	SettingsPaymentsWooCommercePaymentsWrapper,
} from '~/settings-payments';

import { possiblyRenderSettingsSlots } from '~/settings/settings-slots';
import { registerTaxSettingsConflictErrorFill } from '~/settings/conflict-error-slotfill';
import { registerPaymentsSettingsBannerFill } from '~/payments/payments-settings-banner-slotfill';
import { registerSiteVisibilitySlotFill } from '~/launch-your-store';
import { registerBlueprintSlotfill } from '~/blueprint';
import { registerSettingsEmailColorPaletteFill } from '~/settings-email/settings-email-color-palette-slotfill';
import { registerSettingsEmailImageUrlFill } from '~/settings-email/settings-email-image-url-slotfill';
import { registerSettingsEmailPreviewFill } from '~/settings-email/settings-email-preview-slotfill';
import { registerSettingsEmailFeedbackFill } from '~/settings-email/settings-email-feedback-slotfill';
import { registerSettingsEmailListingFill } from '../../settings-email/settings-email-listing-slotfill';

const renderPaymentsSettings = () => {
	if ( ! isFeatureEnabled( 'reactify-classic-payments-settings' ) ) {
		// Render the payment settings components only if the feature flag is enabled.
		return;
	}

	const pages = [
		{
			id: 'experimental_wc_settings_payments_main',
			component: <SettingsPaymentsMainWrapper />,
		},
		{
			id: 'experimental_wc_settings_payments_offline',
			component: <SettingsPaymentsOfflineWrapper />,
		},
		{
			id: 'experimental_wc_settings_payments_bacs',
			component: <SettingsPaymentsBacsWrapper />,
		},
		{
			id: 'experimental_wc_settings_payments_cheque',
			component: <SettingsPaymentsChequeWrapper />,
		},
		{
			id: 'experimental_wc_settings_payments_cod',
			component: <SettingsPaymentsCodWrapper />,
		},
		{
			id: 'experimental_wc_settings_payments_woocommerce_payments',
			component: <SettingsPaymentsWooCommercePaymentsWrapper />,
		},
	];

	// Render each payment component.
	pages.forEach( ( { id, component } ) => {
		const root = document.getElementById( id );
		if ( root ) {
			createRoot(
				root.insertBefore( document.createElement( 'div' ), null )
			).render( component );
		}
	} );
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

	if ( isFeatureEnabled( 'block_email_editor' ) ) {
		registerSettingsEmailListingFill();
	}

	registerSettingsEmailFeedbackFill();
};

renderPaymentsSettings();
registerSlotFills();
