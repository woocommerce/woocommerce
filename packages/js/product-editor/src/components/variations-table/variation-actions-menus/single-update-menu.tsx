/**
 * External dependencies
 */
import { DropdownMenu } from '@wordpress/components';
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { moreVertical } from '@wordpress/icons';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { VariationActionsMenuProps } from './types';
import { TRACKS_SOURCE } from '../../../constants';
import { VariationActions } from './variation-actions';

export function SingleUpdateMenu( {
	selection,
	onChange,
	onDelete,
}: VariationActionsMenuProps ) {
	if ( ! selection || selection.length !== 1 ) {
		return null;
	}

	return (
		<DropdownMenu
			popoverProps={ {
				// @ts-expect-error missing TS.
				placement: 'left-start',
			} }
			icon={ moreVertical }
			label={ __( 'Actions', 'woocommerce' ) }
			toggleProps={ {
				onClick() {
					recordEvent( 'product_variations_menu_view', {
						source: TRACKS_SOURCE,
						variation_id: selection[ 0 ].id,
					} );
				},
			} }
		>
			{ ( { onClose }: { onClose: () => void } ) => (
				<VariationActions
					selection={ selection }
					onClose={ onClose }
					onChange={ onChange }
					onDelete={ onDelete }
					supportsMultipleSelection={ false }
				/>
			) }
		</DropdownMenu>
	);
}
