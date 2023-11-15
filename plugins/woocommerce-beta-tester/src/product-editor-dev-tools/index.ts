/**
 * External dependencies
 */
import { registerPlugin } from '@wordpress/plugins';

/**
 * Internal dependencies
 */
import { ProductEditorDevTools } from './product-editor-dev-tools';
import './index.scss';

export function registerProductEditorDevTools() {
	registerPlugin( 'woocommerce-product-editor-dev-tools', {
		// @ts-expect-error: 'scope' does exist
		scope: 'woocommerce-product-block-editor',
		render: ProductEditorDevTools,
	} );
}
