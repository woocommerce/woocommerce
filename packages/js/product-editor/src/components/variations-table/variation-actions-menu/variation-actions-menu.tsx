/**
 * External dependencies
 */
import { DropdownMenu, MenuGroup, MenuItem } from '@wordpress/components';
import { createElement, Fragment } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { moreVertical } from '@wordpress/icons';
import { ProductVariation } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { VariationActionsMenuProps } from './types';
import { TRACKS_SOURCE } from '../../../constants';
import { ShippingMenuItem } from '../shipping-menu-item';
import { InventoryMenuItem } from '../inventory-menu-item';
import { PricingMenuItem } from '../pricing-menu-item';

export function VariationActionsMenu( {
	selection,
	onChange,
	onDelete,
}: VariationActionsMenuProps ) {
	function handlePrompt(
		label: string = __( 'Enter a value', 'woocommerce' ),
		parser: ( value: string ) => Partial< ProductVariation > | null = () =>
			null
	) {
		// eslint-disable-next-line no-alert
		const value = window.prompt( label );
		if ( value === null ) return;

		const updates = parser( value.trim() );
		if ( updates ) {
			onChange( updates );
		}
	}

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
							onClick={ () => {
								recordEvent( 'product_variations_preview', {
									source: TRACKS_SOURCE,
									variation_id: selection.id,
								} );
							} }
						>
							{ __( 'Preview', 'woocommerce' ) }
						</MenuItem>
					</MenuGroup>
					<MenuGroup>
						<PricingMenuItem
							selection={ selection }
							onChange={ onChange }
							onClose={ onClose }
						/>
						<InventoryMenuItem
							variation={ selection }
							handlePrompt={ handlePrompt }
							onChange={ onChange }
							onClose={ onClose }
						/>
						<ShippingMenuItem
							variation={ selection }
							handlePrompt={ handlePrompt }
							onClose={ onClose }
						/>
					</MenuGroup>
					<MenuGroup>
						<MenuItem
							isDestructive
							label={ __( 'Delete variation', 'woocommerce' ) }
							variant="link"
							onClick={ () => {
								onDelete( selection.id );
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
