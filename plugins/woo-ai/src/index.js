/**
 * External dependencies
 */
import { render } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { DescriptionButtonContainer } from './product-description';

import './index.scss';

const buttonRoot = document.getElementById(
	'woocommerce-ai-app-product-gpt-button'
);

if ( buttonRoot ) {
	render( <DescriptionButtonContainer />, buttonRoot );
}
