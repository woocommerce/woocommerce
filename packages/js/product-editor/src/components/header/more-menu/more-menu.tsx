/**
 * External dependencies
 */
import { createElement, Fragment } from '@wordpress/element';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { WooProductMoreMenuItem } from '../woo-more-menu-item';
import { MoreMenuDropdown } from '../../more-menu-dropdown';

export const MoreMenu = () => {
	return (
		<>
			<MoreMenuDropdown
				toggleProps={ {
					onClick: () => recordEvent( 'product_dropdown_click' ),
				} }
				popoverProps={ {
					className: 'woocommerce-product-header__more-menu',
				} }
			>
				{ ( onClose ) => (
					<>
						<WooProductMoreMenuItem.Slot
							fillProps={ { onClose } }
						/>
					</>
				) }
			</MoreMenuDropdown>
		</>
	);
};
