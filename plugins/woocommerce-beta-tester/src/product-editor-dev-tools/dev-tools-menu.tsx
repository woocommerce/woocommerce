/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { MenuGroup } from '@wordpress/components';
import { __experimentalWooProductMoreMenuItem as WooProductMoreMenuItem } from '@woocommerce/product-editor';

/**
 * Internal dependencies
 */
import { BlockInspectorMenuItem } from './block-inspector-menu-item';

export function DevToolsMenu() {
	return (
		<WooProductMoreMenuItem>
			{ ( { onClose }: { onClose: () => void } ) => (
				<MenuGroup label={ __( 'Development tools', 'woocommerce' ) }>
					<BlockInspectorMenuItem onClick={ onClose } />
				</MenuGroup>
			) }
		</WooProductMoreMenuItem>
	);
}
