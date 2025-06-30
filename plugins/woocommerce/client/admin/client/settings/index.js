/**
 * External dependencies
 */
import { createRoot } from '@wordpress/element';
import { SettingsEditor } from '@woocommerce/settings-editor';

/**
 * Internal dependencies
 */
import './settings.scss';

const node = document.getElementById( 'wc-settings-page' );

createRoot( node ).render( <SettingsEditor /> );
