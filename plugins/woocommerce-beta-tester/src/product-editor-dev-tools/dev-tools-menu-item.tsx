/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { MenuItem, MenuGroup } from '@wordpress/components';
import { search, Icon } from '@wordpress/icons';
import { __experimentalWooProductMoreMenuItem as WooProductMoreMenuItem } from '@woocommerce/product-editor';

export function DevToolsMenuItem() {
	return (
		<WooProductMoreMenuItem>
			{ ( { onClose }: { onClose: () => void } ) => (
				<MenuGroup label={ __( 'Development tools', 'woocommerce' ) }>
					<MenuItem
						icon={ <Icon icon={ search } /> }
						iconPosition="right"
						onClick={ onClose }
					>
						{ __( 'Show block inspector', 'woocommerce' ) }
					</MenuItem>
				</MenuGroup>
			) }
		</WooProductMoreMenuItem>
	);
}
