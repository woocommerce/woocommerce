/**
 * External dependencies
 */
import { Button, Dropdown, MenuGroup, MenuItem } from '@wordpress/components';
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { chevronDown, chevronUp } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { VariationsActionsMenuProps } from './types';
import { UpdateStockMenuItem } from '../update-stock-menu-item';
import { PricingMenuItem } from '../pricing-menu-item';
import { SetListPriceMenuItem } from '../set-list-price-menu-item';
import { InventoryMenuItem } from '../inventory-menu-item';
import { ShippingMenuItem } from '../shipping-menu-item';
import { ToggleVisibilityMenuItem } from '../toggle-visibility-menu-item';

export function VariationsActionsMenu( {
	selection,
	disabled,
	onChange,
	onDelete,
}: VariationsActionsMenuProps ) {
	return (
		<Dropdown
			position="bottom left"
			renderToggle={ ( { isOpen, onToggle } ) => (
				<Button
					disabled={ disabled }
					aria-expanded={ isOpen }
					icon={ isOpen ? chevronUp : chevronDown }
					variant="secondary"
					onClick={ onToggle }
					className="variations-actions-menu__toogle"
				>
					<span>{ __( 'Quick update', 'woocommerce' ) }</span>
				</Button>
			) }
			renderContent={ ( { onClose } ) => (
				<div className="components-dropdown-menu__menu">
					<MenuGroup>
						<UpdateStockMenuItem
							selection={ selection }
							onChange={ onChange }
							onClose={ onClose }
						/>
						<SetListPriceMenuItem
							selection={ selection }
							onChange={ onChange }
							onClose={ onClose }
						/>
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
				</div>
			) }
		/>
	);
}
