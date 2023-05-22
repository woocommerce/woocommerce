/**
 * External dependencies
 */
import { render } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { WriteItForMeButtonContainer } from './product-description';
import { ProductNameSuggestions } from './product-name';

import './index.scss';

const descriptionButtonRoot = document.getElementById(
	'woocommerce-ai-app-product-gpt-button'
);
const nameSuggestionsRoot = document.getElementById(
	'woocommerce-ai-app-product-name-suggestions'
);

if ( descriptionButtonRoot ) {
	render( <WriteItForMeButtonContainer />, descriptionButtonRoot );
}
if ( nameSuggestionsRoot ) {
	render( <ProductNameSuggestions />, nameSuggestionsRoot );
}
