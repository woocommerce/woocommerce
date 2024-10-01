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
import { VariationActionsMenuItemProps } from '../types';

export function ToggleVisibilityMenuItem( {
	selection,
	onChange,
	onClose,
}: VariationActionsMenuItemProps ) {
	function toggleStatus( currentStatus: string ) {
		return currentStatus === 'private' ? 'publish' : 'private';
	}

	function handleMenuItemClick() {
		const ids = selection.map( ( { id } ) => id );

		recordEvent( 'product_variations_menu_toggle_visibility_select', {
			source: TRACKS_SOURCE,
			action: 'status_set',
			variation_id: ids,
		} );

		onChange(
			selection.map( ( { id, status } ) => ( {
				id,
				status: toggleStatus( status ),
			} ) )
		);

		recordEvent( 'product_variations_toggle_visibility_update', {
			source: TRACKS_SOURCE,
			action: 'status_set',
			variation_id: ids,
		} );

		onClose();
	}

	return (
		<MenuItem onClick={ handleMenuItemClick }>
			{ __( 'Toggle visibility', 'woocommerce' ) }
		</MenuItem>
	);
}
