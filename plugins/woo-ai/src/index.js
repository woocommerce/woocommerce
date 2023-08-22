/**
 * External dependencies
 */
import { render, createRoot } from '@wordpress/element';
import { QueryClient, QueryClientProvider } from 'react-query';

/**
 * Internal dependencies
 */
import { WriteItForMeButtonContainer } from './product-description';
import { ProductNameSuggestions } from './product-name';
import { WriteShortDescriptionButtonContainer } from './product-short-description';
import setPreferencesPersistence from './utils/preferencesPersistence';

import './index.scss';

// This sets up loading and saving the plugin's preferences.
setPreferencesPersistence();

const queryClient = new QueryClient();

const renderComponent = ( Component, rootElement ) => {
	if ( ! rootElement ) {
		return;
	}

	const WrappedComponent = () => (
		<QueryClientProvider client={ queryClient }>
			<Component />
		</QueryClientProvider>
	);

	if ( createRoot ) {
		createRoot( rootElement ).render( <WrappedComponent /> );
	} else {
		render( <WrappedComponent />, rootElement );
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
