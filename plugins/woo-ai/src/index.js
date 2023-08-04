/**
 * External dependencies
 */
import { render, createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { WriteItForMeButtonContainer } from './product-description';
import { ProductNameSuggestions } from './product-name';
import { WriteShortDescriptionButtonContainer } from './product-short-description';

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

const shortDescriptionButtonRoot = document.getElementById(
	'woocommerce-ai-app-product-short-description-gpt-button'
);

if ( window.JP_CONNECTION_INITIAL_STATE?.connectionStatus?.isActive ) {
	renderComponent( WriteItForMeButtonContainer, descriptionButtonRoot );
	renderComponent( ProductNameSuggestions, nameSuggestionsRoot );
	renderComponent(
		WriteShortDescriptionButtonContainer,
		shortDescriptionButtonRoot
	);
}
