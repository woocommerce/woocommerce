/**
 * External dependencies
 */
import { MenuGroup, MenuItem } from '@wordpress/components';
import { createElement, Fragment } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';
import { ProductVariation } from '@woocommerce/data';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import { VariationActionsMenuProps } from './types';
import { TRACKS_SOURCE } from '../../../constants';
import { ShippingMenuItem } from '../shipping-menu-item';
import { InventoryMenuItem } from '../inventory-menu-item';
import { PricingMenuItem } from '../pricing-menu-item';
import { ToggleVisibilityMenuItem } from '../toggle-visibility-menu-item';
import { DownloadsMenuItem } from '../downloads-menu-item';
import { VariationQuickUpdateMenuItem } from './variation-quick-update-menu-item';
import { UpdateStockMenuItem } from '../update-stock-menu-item';
import { SetListPriceMenuItem } from '../set-list-price-menu-item';

export function VariationActions( {
	selection,
	onChange,
	onDelete,
	onClose,
	supportsMultipleSelection = false,
}: VariationActionsMenuProps & {
	onClose: () => void;
	supportsMultipleSelection: boolean;
} ) {
	return (
		<div
			className={ classNames( {
				'components-dropdown-menu__menu': supportsMultipleSelection,
			} ) }
		>
			<MenuGroup
				label={
					supportsMultipleSelection
						? undefined
						: sprintf(
								/** Translators: Variation ID */
								__( 'Variation Id: %s', 'woocommerce' ),
								( selection as ProductVariation ).id
						  )
				}
			>
				{ supportsMultipleSelection ? (
					<>
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
					</>
				) : (
					<MenuItem
						href={ ( selection as ProductVariation ).permalink }
						target="_blank"
						rel="noreferrer"
						onClick={ () => {
							recordEvent( 'product_variations_preview', {
								source: TRACKS_SOURCE,
								variation_id: ( selection as ProductVariation )
									.id,
							} );
						} }
					>
						{ __( 'Preview', 'woocommerce' ) }
					</MenuItem>
				) }

				<ToggleVisibilityMenuItem
					selection={ selection }
					onChange={ onChange }
					onClose={ onClose }
				/>
				<VariationQuickUpdateMenuItem.Slot
					group={ '_main' }
					supportsMultipleSelection={ supportsMultipleSelection }
				/>
			</MenuGroup>
			<MenuGroup>
				<PricingMenuItem
					selection={ selection }
					onChange={ onChange }
					onClose={ onClose }
					supportsMultipleSelection={ supportsMultipleSelection }
				/>
				<InventoryMenuItem
					selection={ selection }
					onChange={ onChange }
					onClose={ onClose }
					supportsMultipleSelection={ supportsMultipleSelection }
				/>
				<ShippingMenuItem
					selection={ selection }
					onChange={ onChange }
					onClose={ onClose }
					supportsMultipleSelection={ supportsMultipleSelection }
				/>
				{ window.wcAdminFeatures[ 'product-virtual-downloadable' ] && (
					<DownloadsMenuItem
						selection={ selection }
						onChange={ onChange }
						onClose={ onClose }
						supportsMultipleSelection={ supportsMultipleSelection }
					/>
				) }
				<VariationQuickUpdateMenuItem.Slot
					group={ '_secondary' }
					supportsMultipleSelection={ supportsMultipleSelection }
				/>
			</MenuGroup>
			<MenuGroup>
				<MenuItem
					isDestructive
					label={
						! supportsMultipleSelection
							? __( 'Delete variation', 'woocommerce' )
							: undefined
					}
					variant="link"
					onClick={ () => {
						onDelete( selection );
						onClose();
					} }
					className="woocommerce-product-variations__actions--delete"
				>
					{ __( 'Delete', 'woocommerce' ) }
				</MenuItem>
				<VariationQuickUpdateMenuItem.Slot
					group={ '_tertiary' }
					supportsMultipleSelection={ supportsMultipleSelection }
				/>
			</MenuGroup>
		</div>
	);
}
