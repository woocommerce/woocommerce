/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { MenuGroup, MenuItem } from '@wordpress/components';

/**
 * Internal dependencies
 */

export type DeleteVariationMenuItemProps = { onClick(): void };

export const DeleteVariationMenuItem = ( {
	onClick,
}: DeleteVariationMenuItemProps ) => {
	function handleMenuItemClick() {
		onClick();
	}

	return (
		<MenuGroup>
			<MenuItem
				isDestructive
				variant="tertiary"
				onClick={ handleMenuItemClick }
			>
				{ __( 'Delete variation', 'woocommerce' ) }
			</MenuItem>
		</MenuGroup>
	);
};
