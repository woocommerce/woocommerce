/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { ProductVariation } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { Dropdown, MenuGroup, MenuItem } from '@wordpress/components';
import { createElement } from '@wordpress/element';
import { chevronRight } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { TRACKS_SOURCE } from '../../../constants';
import { PRODUCT_STOCK_STATUS_KEYS } from '../../../utils/get-product-stock-status';
import { UpdateStockMenuItem } from '../update-stock-menu-item';

export type InventoryMenuItemProps = {
	variation: ProductVariation;
	handlePrompt(
		label?: string,
		parser?: ( value: string ) => Partial< ProductVariation > | null
	): void;
	onChange( values: Partial< ProductVariation > ): void;
	onClose(): void;
};

export function InventoryMenuItem( {
	variation,
	handlePrompt,
	onChange,
	onClose,
}: InventoryMenuItemProps ) {
	return (
		<Dropdown
			position="middle right"
			renderToggle={ ( { isOpen, onToggle } ) => (
				<MenuItem
					onClick={ () => {
						recordEvent(
							'product_variations_menu_inventory_click',
							{
								source: TRACKS_SOURCE,
								variation_id: variation.id,
							}
						);
						onToggle();
					} }
					aria-expanded={ isOpen }
					icon={ chevronRight }
					iconPosition="right"
				>
					{ __( 'Inventory', 'woocommerce' ) }
				</MenuItem>
			) }
			renderContent={ () => (
				<div className="components-dropdown-menu__menu">
					<MenuGroup>
						<UpdateStockMenuItem
							selection={ variation }
							onChange={ onChange }
							onClose={ onClose }
						/>
						<MenuItem
							onClick={ () => {
								recordEvent(
									'product_variations_menu_inventory_select',
									{
										source: TRACKS_SOURCE,
										action: 'manage_stock_toggle',
										variation_id: variation.id,
									}
								);
								onChange( {
									manage_stock: ! variation.manage_stock,
								} );
								onClose();
							} }
						>
							{ __( 'Toggle "track quantity"', 'woocommerce' ) }
						</MenuItem>
						<MenuItem
							onClick={ () => {
								recordEvent(
									'product_variations_menu_inventory_select',
									{
										source: TRACKS_SOURCE,
										action: 'set_status_in_stock',
										variation_id: variation.id,
									}
								);
								onChange( {
									stock_status:
										PRODUCT_STOCK_STATUS_KEYS.instock,
									manage_stock: false,
								} );
								onClose();
							} }
						>
							{ __( 'Set status to In stock', 'woocommerce' ) }
						</MenuItem>
						<MenuItem
							onClick={ () => {
								recordEvent(
									'product_variations_menu_inventory_select',
									{
										source: TRACKS_SOURCE,
										action: 'set_status_out_of_stock',
										variation_id: variation.id,
									}
								);
								onChange( {
									stock_status:
										PRODUCT_STOCK_STATUS_KEYS.outofstock,
									manage_stock: false,
								} );
								onClose();
							} }
						>
							{ __(
								'Set status to Out of stock',
								'woocommerce'
							) }
						</MenuItem>
						<MenuItem
							onClick={ () => {
								recordEvent(
									'product_variations_menu_inventory_select',
									{
										source: TRACKS_SOURCE,
										action: 'set_status_on_back_order',
										variation_id: variation.id,
									}
								);
								onChange( {
									stock_status:
										PRODUCT_STOCK_STATUS_KEYS.onbackorder,
									manage_stock: false,
								} );
								onClose();
							} }
						>
							{ __(
								'Set status to On back order',
								'woocommerce'
							) }
						</MenuItem>
						<MenuItem
							onClick={ () => {
								recordEvent(
									'product_variations_menu_inventory_select',
									{
										source: TRACKS_SOURCE,
										action: 'low_stock_amount_set',
										variation_id: variation.id,
									}
								);
								handlePrompt( undefined, ( value ) => {
									recordEvent(
										'product_variations_menu_inventory_select',
										{
											source: TRACKS_SOURCE,
											action: 'low_stock_amount_set',
											variation_id: variation.id,
										}
									);
									const lowStockAmount = Number( value );
									if ( Number.isNaN( lowStockAmount ) ) {
										return null;
									}
									return {
										low_stock_amount: lowStockAmount,
										manage_stock: true,
									};
								} );
								onClose();
							} }
						>
							{ __( 'Edit low stock threshold', 'woocommerce' ) }
						</MenuItem>
					</MenuGroup>
				</div>
			) }
		/>
	);
}
