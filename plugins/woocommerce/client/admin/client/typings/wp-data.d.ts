/**
 * External dependencies
 */
import type * as controls from '@wordpress/data-controls';

declare module '@wordpress/data' {
	// we need to declare this here as an interim solution until we bump the @wordpress/data version to wp-6.6 across the board in WC.
	// this is being added to get around the issue that surfaced after upgrading to typescript 5.7.2 where the types for @wordpress/data
	// is being checked due to the imports in plugins/woocommerce/client/admin/client/homescreen/mobile-app-modal/components/useJetpackPluginState.tsx
	// and plugins/woocommerce/client/admin/client/homescreen/stats-overview/install-jetpack-cta.js
	const controls: {
		select: typeof controls.select;
		resolveSelect: typeof controls.resolveSelect;
		dispatch: typeof controls.dispatch;
	};
}
