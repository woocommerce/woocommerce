/**
 * External dependencies
 */
import { createRoot } from '@wordpress/element';
import { addFilter } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import { App } from './app';
import './index.scss';
import './example-fills/experimental-woocommerce-wcpay-feature';
import { registerProductEditorDevTools } from './product-editor-dev-tools';
import { registerExceptionFilter } from './remote-logging/register-exception-filter';

const appRoot = document.getElementById(
	'woocommerce-admin-test-helper-app-root'
);

if ( appRoot ) {
	createRoot( appRoot ).render( <App /> );
}

registerProductEditorDevTools();
registerExceptionFilter();
