/**
 * External dependencies
 */
import { dispatch } from '@wordpress/data';
import { render, createRoot } from '@wordpress/element';
import { store as preferencesStore } from '@wordpress/preferences';

/**
 * Internal dependencies
 */
import { WriteItForMeButtonContainer } from './product-description';
import { ProductNameSuggestions } from './product-name';
import { WriteShortDescriptionButtonContainer } from './product-short-description';

import './index.scss';

// @todo: move to a utils function or something.
dispatch( preferencesStore ).setPersistenceLayer( {
	get: async () => {
		const savedPreferences = window.localStorage.getItem(
			'woo-ai-plugin-prefs'
		);
		return savedPreferences ? JSON.parse( savedPreferences ) : {};
	},
	set: ( preferences ) => {
		window.localStorage.setItem(
			'woo-ai-plugin-prefs',
			JSON.stringify( preferences )
		);
	},
} );

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
