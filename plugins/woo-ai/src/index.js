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
import { ProductCategorySuggestions } from './product-category';

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

const renderProductCategorySuggestions = () => {
	const root = document.createElement( 'div' );
	root.id = 'woocommerce-ai-app-product-category-suggestions';

	renderComponent( ProductCategorySuggestions, root );

	// Insert the category suggestions node in the product category meta box.
	document.getElementById( 'taxonomy-product_cat' ).append( root );
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
	renderProductCategorySuggestions();
}
