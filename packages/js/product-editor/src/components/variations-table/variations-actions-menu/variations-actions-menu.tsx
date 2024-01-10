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
import { DownloadsMenuItem } from '../downloads-menu-item';
import { VariationActionsMenuItem } from '../variation-actions/variation-actions-menu-item';

export function VariationsActionsMenu( {
	selection,
	disabled,
	onChange,
	onDelete,
}: VariationsActionsMenuProps ) {
	return (
		<Dropdown
			// @ts-expect-error missing prop in types.
			popoverProps={ {
				placement: 'bottom-end',
			} }
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
						<VariationActionsMenuItem.Slot
							group={ '_main' }
							type={ 'quick-update' }
						/>
					</MenuGroup>
					<MenuGroup>
						<PricingMenuItem
							selection={ selection }
							onChange={ onChange }
							onClose={ onClose }
							type="quick-update"
						/>
						<InventoryMenuItem
							selection={ selection }
							onChange={ onChange }
							onClose={ onClose }
							type="quick-update"
						/>
						<ShippingMenuItem
							selection={ selection }
							onChange={ onChange }
							onClose={ onClose }
							type="quick-update"
						/>
						{ window.wcAdminFeatures[
							'product-virtual-downloadable'
						] && (
							<DownloadsMenuItem
								selection={ selection }
								onChange={ onChange }
								onClose={ onClose }
								type="quick-update"
							/>
						) }
						<VariationActionsMenuItem.Slot
							group={ '_secondary' }
							type={ 'quick-update' }
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
						<VariationActionsMenuItem.Slot
							group={ '_tertiary' }
							type={ 'quick-update' }
						/>
					</MenuGroup>
				</div>
			) }
		/>
	);
}
