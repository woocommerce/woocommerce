/**
 * External dependencies
 */
import { render } from '@wordpress/element';

/**
 * Internal dependencies
 */
import {
	ProductDescriptionForm,
	ProductDescriptionButton,
} from './product-description';

import './product-text-generation';
import './index.scss';

const buttonRoot = document.getElementById(
	'woocommerce-ai-app-product-gpt-button'
);
const formRoot = document.getElementById(
	'woocommerce-ai-app-product-gpt-form'
);

if ( buttonRoot ) {
	render( <ProductDescriptionButton />, buttonRoot );
}

if ( formRoot ) {
	render( <ProductDescriptionForm />, formRoot );
}
