/**
 * External dependencies
 */
import { render, createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { WriteItForMeButtonContainer } from './product-description';
import { ProductNameSuggestions } from './product-name';
import { RemoveBackgroundButton } from './background-removal';

import './index.scss';

const renderComponent = ( Component, rootElement ) => {
	if ( ! rootElement ) {
		return;
	}

	if ( createRoot ) {
		createRoot( rootElement ).render( <Component /> );
	} else {
		render( <Component />, rootElement );
	}
};

const descriptionButtonRoot = document.getElementById(
	'woocommerce-ai-app-product-gpt-button'
);
const nameSuggestionsRoot = document.getElementById(
	'woocommerce-ai-app-product-name-suggestions'
);
const removeBackgroundButtonRoot = document.getElementById(
	'woocommerce-ai-app-remove-background-button'
);
console.log( 'removeBackgroundButtonRoot', removeBackgroundButtonRoot );

if ( window.JP_CONNECTION_INITIAL_STATE?.connectionStatus?.isActive ) {
	console.log( 'rendering' );
	renderComponent( WriteItForMeButtonContainer, descriptionButtonRoot );
	renderComponent( ProductNameSuggestions, nameSuggestionsRoot );
	renderComponent( RemoveBackgroundButton, removeBackgroundButtonRoot );
}
