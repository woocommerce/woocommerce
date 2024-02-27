/**
 * External dependencies
 */
import { MenuGroup, MenuItem } from '@wordpress/components';
import { createElement, Fragment } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';
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
	const singleSelection =
		! supportsMultipleSelection && selection.length === 1
			? selection[ 0 ]
			: null;

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
								singleSelection?.id
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
						href={ singleSelection?.permalink }
						target="_blank"
						rel="noreferrer"
						onClick={ () => {
							recordEvent( 'product_variations_preview', {
								source: TRACKS_SOURCE,
								variation_id: singleSelection?.id,
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
			</MenuGroup>
			<VariationQuickUpdateMenuItem.Slot
				group={ 'top-level' }
				onChange={ onChange }
				onClose={ onClose }
				selection={ selection }
				supportsMultipleSelection={ supportsMultipleSelection }
			/>
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
			</MenuGroup>
			<VariationQuickUpdateMenuItem.Slot
				group={ 'secondary' }
				onChange={ onChange }
				onClose={ onClose }
				selection={ selection }
				supportsMultipleSelection={ supportsMultipleSelection }
			/>
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
			</MenuGroup>
			<VariationQuickUpdateMenuItem.Slot
				group={ 'tertiary' }
				onChange={ onChange }
				onClose={ onClose }
				selection={ selection }
				supportsMultipleSelection={ supportsMultipleSelection }
			/>
		</div>
	);
}
