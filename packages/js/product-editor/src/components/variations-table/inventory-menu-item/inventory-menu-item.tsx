/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
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
import { VariationActionsMenuItemProps } from '../types';
import { handlePrompt } from '../../../utils/handle-prompt';
import { VariationQuickUpdateMenuItem } from '../variation-actions-menus';

export function InventoryMenuItem( {
	selection,
	onChange,
	onClose,
	supportsMultipleSelection = false,
}: VariationActionsMenuItemProps ) {
	const ids = selection.map( ( { id } ) => id );

	return (
		<Dropdown
			// @ts-expect-error missing prop in types.
			popoverProps={ {
				placement: 'right-start',
			} }
			renderToggle={ ( { isOpen, onToggle } ) => (
				<MenuItem
					onClick={ () => {
						recordEvent(
							'product_variations_menu_inventory_click',
							{
								source: TRACKS_SOURCE,
								variation_id: ids,
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
							selection={ selection }
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
										variation_id: ids,
									}
								);
								onChange(
									selection.map(
										( { id, manage_stock } ) => ( {
											id,
											manage_stock: ! manage_stock,
										} )
									)
								);
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
										variation_id: ids,
									}
								);
								onChange(
									selection.map( ( { id } ) => ( {
										id,
										stock_status:
											PRODUCT_STOCK_STATUS_KEYS.instock,
										manage_stock: false,
									} ) )
								);
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
										variation_id: ids,
									}
								);
								onChange(
									selection.map( ( { id } ) => ( {
										id,
										stock_status:
											PRODUCT_STOCK_STATUS_KEYS.outofstock,
										manage_stock: false,
									} ) )
								);
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
										variation_id: ids,
									}
								);
								onChange(
									selection.map( ( { id } ) => ( {
										id,
										stock_status:
											PRODUCT_STOCK_STATUS_KEYS.onbackorder,
										manage_stock: false,
									} ) )
								);
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
										variation_id: ids,
									}
								);
								handlePrompt( {
									onOk( value ) {
										recordEvent(
											'product_variations_menu_inventory_update',
											{
												source: TRACKS_SOURCE,
												action: 'low_stock_amount_set',
												variation_id: ids,
											}
										);
										const lowStockAmount = Number( value );
										if ( Number.isNaN( lowStockAmount ) ) {
											return null;
										}
										onChange(
											selection.map( ( { id } ) => ( {
												id,
												low_stock_amount:
													lowStockAmount,
												manage_stock: true,
											} ) )
										);
									},
								} );
								onClose();
							} }
						>
							{ __( 'Edit low stock threshold', 'woocommerce' ) }
						</MenuItem>
					</MenuGroup>
					<VariationQuickUpdateMenuItem.Slot
						group={ 'inventory' }
						onChange={ onChange }
						onClose={ onClose }
						selection={ selection }
						supportsMultipleSelection={ supportsMultipleSelection }
					/>
				</div>
			) }
		/>
	);
}
