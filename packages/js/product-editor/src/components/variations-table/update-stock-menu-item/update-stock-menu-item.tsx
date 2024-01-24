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
import { handlePrompt } from '../../../utils/handle-prompt';
import { VariationActionsMenuItemProps } from '../types';

export function UpdateStockMenuItem( {
	selection,
	onChange,
	onClose,
}: VariationActionsMenuItemProps ) {
	return (
		<MenuItem
			onClick={ () => {
				const ids = selection.map( ( { id } ) => id );

				recordEvent( 'product_variations_menu_inventory_select', {
					source: TRACKS_SOURCE,
					action: 'stock_quantity_set',
					variation_id: ids,
				} );
				handlePrompt( {
					onOk( value ) {
						const stockQuantity = Number( value );
						if ( Number.isNaN( stockQuantity ) ) {
							return;
						}
						recordEvent(
							'product_variations_menu_inventory_update',
							{
								source: TRACKS_SOURCE,
								action: 'stock_quantity_set',
								variation_id: ids,
							}
						);
						onChange(
							selection.map( ( { id } ) => ( {
								id,
								stock_quantity: stockQuantity,
								manage_stock: true,
							} ) )
						);
					},
				} );
				onClose();
			} }
		>
			{ __( 'Update stock', 'woocommerce' ) }
		</MenuItem>
	);
}
