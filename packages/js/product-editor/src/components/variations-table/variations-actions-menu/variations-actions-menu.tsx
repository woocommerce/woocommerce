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

export function VariationsActionsMenu( {}: VariationsActionsMenuProps ) {
	return (
		<Dropdown
			position="bottom right"
			renderToggle={ ( { isOpen, onToggle } ) => (
				<Button
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
						<MenuItem
							onClick={ () => {
								onClose();
							} }
						>
							{ __( 'Update stock', 'woocommerce' ) }
						</MenuItem>
					</MenuGroup>
					<MenuGroup>
						<MenuItem
							isDestructive
							variant="link"
							onClick={ () => {
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
