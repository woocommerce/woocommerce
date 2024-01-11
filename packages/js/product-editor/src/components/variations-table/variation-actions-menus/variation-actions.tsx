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
import { VariationActionsMenuItem } from './variation-actions-menu-item';
import { QUICK_UPDATE, SINGLE_UPDATE } from './constants';
import { UpdateStockMenuItem } from '../update-stock-menu-item';
import { SetListPriceMenuItem } from '../set-list-price-menu-item';

export function VariationActions( {
	selection,
	onChange,
	onDelete,
	onClose,
	type = SINGLE_UPDATE,
}: VariationActionsMenuProps & {
	onClose: () => void;
	type: string;
} ) {
	const isQuickUpdate = type === QUICK_UPDATE;
	return (
		<div
			className={ classNames( {
				'components-dropdown-menu__menu': isQuickUpdate,
			} ) }
		>
			<MenuGroup
				label={
					isQuickUpdate
						? undefined
						: sprintf(
								/** Translators: Variation ID */
								__( 'Variation Id: %s', 'woocommerce' ),
								( selection as ProductVariation ).id
						  )
				}
			>
				{ isQuickUpdate ? (
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
				<VariationActionsMenuItem.Slot
					group={ '_main' }
					type={ type }
				/>
			</MenuGroup>
			<MenuGroup>
				<PricingMenuItem
					selection={ selection }
					onChange={ onChange }
					onClose={ onClose }
					type={ type }
				/>
				<InventoryMenuItem
					selection={ selection }
					onChange={ onChange }
					onClose={ onClose }
					type={ type }
				/>
				<ShippingMenuItem
					selection={ selection }
					onChange={ onChange }
					onClose={ onClose }
					type={ type }
				/>
				{ window.wcAdminFeatures[ 'product-virtual-downloadable' ] && (
					<DownloadsMenuItem
						selection={ selection }
						onChange={ onChange }
						onClose={ onClose }
						type={ type }
					/>
				) }
				<VariationActionsMenuItem.Slot
					group={ '_secondary' }
					type={ type }
				/>
			</MenuGroup>
			<MenuGroup>
				<MenuItem
					isDestructive
					label={
						! isQuickUpdate
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
				<VariationActionsMenuItem.Slot
					group={ '_tertiary' }
					type={ type }
				/>
			</MenuGroup>
		</div>
	);
}
