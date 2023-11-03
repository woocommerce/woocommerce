/**
 * Internal dependencies
 */
import { renderWrappedComponent } from '../utils';
import { ProductNameSuggestions } from './product-name-suggestions';

if ( window.JP_CONNECTION_INITIAL_STATE?.connectionStatus?.isActive ) {
	renderWrappedComponent(
		ProductNameSuggestions,
		document.getElementById( 'woocommerce-ai-app-product-name-suggestions' )
	);
}

export * from './product-name-suggestions';
export * from './powered-by-link';
export * from './suggestion-item';
export * from './name-utils';
