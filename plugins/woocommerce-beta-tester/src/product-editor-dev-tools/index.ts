/**
 * External dependencies
 */
import { registerPlugin } from '@wordpress/plugins';

/**
 * Internal dependencies
 */
import { DevToolsMenu } from './dev-tools-menu';

export function registerProductEditorDevTools() {
	registerPlugin( 'woocommerce-product-editor-dev-tools', {
		// @ts-expect-error: 'scope' does exist
		scope: 'woocommerce-product-block-editor',
		render: DevToolsMenu,
	} );
}
