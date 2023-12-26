/**
 * Internal dependencies
 */
import { renderWrappedComponent } from '../utils';
import { ProductCategorySuggestions } from './product-category-suggestions';

if ( window.JP_CONNECTION_INITIAL_STATE?.connectionStatus?.isActive ) {
	const root = document.createElement( 'div' );
	root.id = 'woocommerce-ai-app-product-category-suggestions';

	renderWrappedComponent( ProductCategorySuggestions, root );

	// Insert the category suggestions node in the product category meta box.
	document.getElementById( 'taxonomy-product_cat' )?.append( root );
}
