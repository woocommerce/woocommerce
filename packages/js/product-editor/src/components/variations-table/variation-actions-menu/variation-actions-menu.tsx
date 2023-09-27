/**
 * External dependencies
 */
import { DropdownMenu, MenuGroup, MenuItem } from '@wordpress/components';
import { createElement, Fragment } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { moreVertical } from '@wordpress/icons';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { VariationActionsMenuProps } from './types';
import { TRACKS_SOURCE } from '../../../constants';
import { ShippingMenuItem } from '../shipping-menu-item';
import { InventoryMenuItem } from '../inventory-menu-item';
import { PricingMenuItem } from '../pricing-menu-item';
import { ToggleVisibilityMenuItem } from '../toggle-visibility-menu-item';

export function VariationActionsMenu( {
	selection,
	onChange,
	onDelete,
}: VariationActionsMenuProps ) {
	return (
		<DropdownMenu
			icon={ moreVertical }
			label={ __( 'Actions', 'woocommerce' ) }
			toggleProps={ {
				onClick() {
					recordEvent( 'product_variations_menu_view', {
						source: TRACKS_SOURCE,
						variation_id: selection.id,
					} );
				},
			} }
		>
			{ ( { onClose } ) => (
				<>
					<MenuGroup
						label={ sprintf(
							/** Translators: Variation ID */
							__( 'Variation Id: %s', 'woocommerce' ),
							selection.id
						) }
					>
						<MenuItem
							href={ selection.permalink }
							target="_blank"
							rel="noreferrer"
							onClick={ () => {
								recordEvent( 'product_variations_preview', {
									source: TRACKS_SOURCE,
									variation_id: selection.id,
								} );
							} }
						>
							{ __( 'Preview', 'woocommerce' ) }
						</MenuItem>
						<ToggleVisibilityMenuItem
							selection={ selection }
							onChange={ onChange }
							onClose={ onClose }
						/>
					</MenuGroup>
					<MenuGroup>
						<PricingMenuItem
							selection={ selection }
							onChange={ onChange }
							onClose={ onClose }
						/>
						<InventoryMenuItem
							selection={ selection }
							onChange={ onChange }
							onClose={ onClose }
						/>
						<ShippingMenuItem
							selection={ selection }
							onChange={ onChange }
							onClose={ onClose }
						/>
					</MenuGroup>
					<MenuGroup>
						<MenuItem
							isDestructive
							label={ __( 'Delete variation', 'woocommerce' ) }
							variant="link"
							onClick={ () => {
								onDelete( selection );
								onClose();
							} }
							className="woocommerce-product-variations__actions--delete"
						>
							{ __( 'Delete', 'woocommerce' ) }
						</MenuItem>
					</MenuGroup>
				</>
			) }
		</DropdownMenu>
	);
}
