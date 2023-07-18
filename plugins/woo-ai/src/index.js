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

import './index.scss';

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

if ( window.JP_CONNECTION_INITIAL_STATE?.connectionStatus?.isActive ) {
	renderComponent( WriteItForMeButtonContainer, descriptionButtonRoot );
	renderComponent( ProductNameSuggestions, nameSuggestionsRoot );
}
