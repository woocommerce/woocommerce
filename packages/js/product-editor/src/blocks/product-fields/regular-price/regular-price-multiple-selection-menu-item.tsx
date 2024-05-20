/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { TRACKS_SOURCE } from '../../../constants';
import { handlePrompt } from '../../../utils';
import { VariationQuickUpdateMenuItem } from '../../../components/variations-table';

export const RegularPriceMultipleSelectionMenuItem = () => (
	<VariationQuickUpdateMenuItem
		group="multiple-selections"
		order={ 20 }
		supportsMultipleSelection
		onClick={ ( { selection, onChange, onClose } ) => {
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
	</VariationQuickUpdateMenuItem>
);
