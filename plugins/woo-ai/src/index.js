/**
 * External dependencies
 */
import { render, createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { WriteItForMeButtonContainer } from './product-description';
import { ProductNameSuggestions } from './product-name';
import { BrandingProvider } from './context/storeBrandingContext'; // replace with the correct path to your file

import './index.scss';

const renderComponent = ( Component, rootElement ) => {
	if ( ! rootElement ) {
		return;
	}

	const WrappedComponent = () => (
		<BrandingProvider>
			<Component />
		</BrandingProvider>
	);

	if ( createRoot ) {
		createRoot( rootElement ).render(<WrappedComponent />);
	} else {
		render(<WrappedComponent />, rootElement);
	}
};

const descriptionButtonRoot = document.getElementById(
	'woocommerce-ai-app-product-gpt-button'
);
const nameSuggestionsRoot = document.getElementById(
	'woocommerce-ai-app-product-name-suggestions'
);

if ( window.JP_CONNECTION_INITIAL_STATE?.connectionStatus?.isActive ) {
	if (descriptionButtonRoot) {
		renderComponent(WriteItForMeButtonContainer, descriptionButtonRoot);
	}
	if (nameSuggestionsRoot) {
		renderComponent(ProductNameSuggestions, nameSuggestionsRoot);
	}
}
