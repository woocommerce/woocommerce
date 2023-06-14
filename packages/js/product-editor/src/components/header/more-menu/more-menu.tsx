/**
 * External dependencies
 */
import { createElement, Fragment } from '@wordpress/element';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { MoreMenuDropdown } from '@wordpress/interface';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { WooProductMoreMenuItem } from '../woo-more-menu-item';

export const MoreMenu = () => {
	return (
		<>
			<MoreMenuDropdown
				toggleProps={ {
					onClick: () => recordEvent( 'product_dropdown_click' )
				} }
				popoverProps={ {
					className: 'woocommerce-product-header__more-menu',
				} }
			>
				{ ( { onClose }: { onClose: () => void } ) => (
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
