/**
 * External dependencies
 */
import { registerPlugin } from '@wordpress/plugins';

/**
 * Internal dependencies
 */
import { DevToolsMenuItem } from './dev-tools-menu-item';

export function registerProductEditorDevTools() {
	registerPlugin( 'woocommerce-product-editor-dev-tools', {
		// @ts-expect-error: 'scope' does exist
		scope: 'woocommerce-product-block-editor',
		render: DevToolsMenuItem,
	} );
}
