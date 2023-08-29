/**
 * External dependencies
 */
import { MenuItem } from '@wordpress/components';
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { TRACKS_SOURCE } from '../../../constants';
import { UpdateStockMenuItemProps } from './types';
import { handlePrompt } from '../../../utils/handle-prompt';

export function UpdateStockMenuItem( {
	variation,
	onChange,
	onClose,
}: UpdateStockMenuItemProps ) {
	return (
		<MenuItem
			onClick={ () => {
				recordEvent( 'product_variations_menu_inventory_select', {
					source: TRACKS_SOURCE,
					action: 'stock_quantity_set',
					variation_id: variation?.id,
				} );
				handlePrompt().then( ( value ) => {
					const stockQuantity = Number( value );
					if ( Number.isNaN( stockQuantity ) ) {
						return;
					}
					recordEvent( 'product_variations_menu_inventory_update', {
						source: TRACKS_SOURCE,
						action: 'stock_quantity_set',
						variation_id: variation?.id,
					} );
					onChange( {
						stock_quantity: stockQuantity,
						manage_stock: true,
					} );
				} );
				onClose();
			} }
		>
			{ __( 'Update stock', 'woocommerce' ) }
		</MenuItem>
	);
}
