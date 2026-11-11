/**
 * This file is used to enhance the settings page with additional features such as registering slot fills.
 */

/**
 * External dependencies
 */
import { createRoot, lazy, Suspense } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './settings-ui.scss';
import { isFeatureEnabled } from '~/utils/features';
import {
	SettingsPaymentsBacsWrapper,
	SettingsPaymentsChequeWrapper,
	SettingsPaymentsCodWrapper,
	SettingsPaymentsMainWrapper,
	SettingsPaymentsOfflineWrapper,
	SettingsPaymentsWooPaymentsWrapper,
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
import { registerSettingsEmailListingFill } from '~/settings-email/settings-email-listing-slotfill';
import { registerSettingsUIScreens } from '~/settings/settings-ui-registry';
import { registerAiShoppingHeader } from '~/sales-readiness/ai-shopping-header';

const renderPaymentsSettings = () => {
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
			component: <SettingsPaymentsWooPaymentsWrapper />,
		},
	];

	// Render each payment component.
	pages.forEach( ( { id, component } ) => {
		const root = document.getElementById( id );
		if ( root ) {
			const newDiv = document.createElement( 'div' );
			newDiv.className = 'wc-settings-prevent-change-event';
			createRoot( root.insertBefore( newDiv, null ) ).render( component );
		}
	} );
};

const SalesReadinessOverview = lazy(
	() =>
		import(
			/* webpackChunkName: "sales-readiness-overview" */ '~/sales-readiness/overview'
		)
);

/**
 * WooCommerce > Settings > AI tools opens on the Overview, which the PHP tab leaves a root for.
 */
const renderSalesReadinessOverview = () => {
	const root = document.getElementById( 'wc-agent-readiness-overview' );
	if ( root ) {
		let initialView = null;
		try {
			initialView = JSON.parse( root.dataset.view ?? 'null' );
		} catch {
			// The Overview loads the view itself.
		}
		createRoot( root ).render(
			<Suspense fallback={ null }>
				<SalesReadinessOverview initialView={ initialView } />
			</Suspense>
		);
	}
};

/**
 * AI tools > AI shopping: the breadcrumb and description become the page header. The PHP section
 * leaves them on a root, with a plain breadcrumb that shows until this runs.
 */
const renderAiShoppingHeader = () => {
	const root = document.getElementById( 'wc-agentic-commerce-header' );
	if ( root ) {
		const { parentLabel, parentUrl, title, description } = root.dataset;
		registerAiShoppingHeader( {
			parentLabel: parentLabel ?? '',
			parentUrl: parentUrl ?? '',
			title: title ?? '',
			description: description ?? '',
		} );
		root.remove();
	}
};

const registerSlotFills = () => {
	possiblyRenderSettingsSlots();
	registerTaxSettingsConflictErrorFill();
	registerPaymentsSettingsBannerFill();

	const features = window.wcAdminFeatures;

	registerSiteVisibilitySlotFill();

	if ( isFeatureEnabled( 'blueprint' ) ) {
		registerBlueprintSlotfill();
	}

	if ( isFeatureEnabled( 'block_email_editor' ) ) {
		registerSettingsEmailListingFill();
	}

	if ( features?.[ 'settings-ui' ] === true ) {
		registerSettingsUIScreens();
	}

	registerSettingsEmailColorPaletteFill();
	registerSettingsEmailFeedbackFill();
	registerSettingsEmailImageUrlFill();
	registerSettingsEmailPreviewFill();
};

renderPaymentsSettings();
renderSalesReadinessOverview();
renderAiShoppingHeader();
registerSlotFills();
