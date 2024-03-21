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

export function SetListPriceMenuItem( {
	selection,
	onChange,
	onClose,
}: VariationActionsMenuItemProps ) {
	return (
		<MenuItem
			onClick={ () => {
				const ids = selection.map( ( { id } ) => id );

				recordEvent( 'product_variations_menu_pricing_select', {
					source: TRACKS_SOURCE,
					action: 'list_price_set',
					variation_id: ids,
				} );
				handlePrompt( {
					onOk( value ) {
						recordEvent( 'product_variations_menu_pricing_update', {
							source: TRACKS_SOURCE,
							action: 'list_price_set',
							variation_id: ids,
						} );
						onChange(
							selection.map( ( { id } ) => ( {
								id,
								regular_price: value,
							} ) )
						);
					},
				} );
				onClose();
			} }
		>
			{ __( 'Set list price', 'woocommerce' ) }
		</MenuItem>
	);
}
