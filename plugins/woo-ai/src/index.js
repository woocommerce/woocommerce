/**
 * External dependencies
 */
import { render } from '@wordpress/element';

/**
 * Internal dependencies
 */
import {
	ProductDescriptionForm,
	DescriptionButtonContainer,
} from './product-description';

import './index.scss';
import './product-description/product-text-meta-box';

const buttonRoot = document.getElementById(
	'woocommerce-ai-app-product-gpt-button'
);
const formRoot = document.getElementById(
	'woocommerce-ai-app-product-gpt-form'
);

if ( buttonRoot ) {
	render( <DescriptionButtonContainer />, buttonRoot );
}

if ( formRoot ) {
	render( <ProductDescriptionForm />, formRoot );
}
