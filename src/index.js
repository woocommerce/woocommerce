/**
 * External dependencies
 */
import { render } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { App } from './App';
import './index.scss';

const appRoot = document.getElementById( 'woocommerce-admin-test-helper-app-root' );

if ( appRoot ) {
    render( <App/>, appRoot );
}