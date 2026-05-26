/**
 * This file is used to enhance the settings page with additional payment settings.
 */

/**
 * External dependencies
 */
import { createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import {
	SettingsPaymentsBacsWrapper,
	SettingsPaymentsChequeWrapper,
	SettingsPaymentsCodWrapper,
	SettingsPaymentsMainWrapper,
	SettingsPaymentsOfflineWrapper,
	SettingsPaymentsWooPaymentsWrapper,
} from '~/settings-payments';

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

renderPaymentsSettings();
