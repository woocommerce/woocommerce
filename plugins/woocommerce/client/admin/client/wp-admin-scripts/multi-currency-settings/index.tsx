/**
 * External dependencies
 */
import { createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { MultiCurrencySettingsApp } from './app';
import './style.scss';

const container = document.getElementById(
	'wcpay_multi_currency_settings_container'
);

if ( container ) {
	createRoot( container ).render( <MultiCurrencySettingsApp /> );
}
