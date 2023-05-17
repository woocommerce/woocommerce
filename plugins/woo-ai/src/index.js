/**
 * External dependencies
 */
import { render } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { WriteItForMeButtonContainer } from './product-description';

import './index.scss';

const buttonRoot = document.getElementById(
	'woocommerce-ai-app-product-gpt-button'
);

if ( buttonRoot ) {
	render( <WriteItForMeButtonContainer />, buttonRoot );
}
