/**
 * External dependencies
 */
import { Dropdown, MenuItem, MenuGroup } from '@wordpress/components';
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { chevronRight } from '@wordpress/icons';
import { ProductVariation } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { TRACKS_SOURCE } from '../../../constants';
import { VariationActionsMenuItemProps } from '../types';
import { handlePrompt } from '../../../utils/handle-prompt';
import { VariationQuickUpdateMenuItem } from '../variation-actions-menus/variation-quick-update-menu-item';

export function ShippingMenuItem( {
	selection,
	onChange,
	onClose,
	supportsMultipleSelection = false,
}: VariationActionsMenuItemProps ) {
	const ids = selection.map( ( { id } ) => id );

	function handleDimensionsChange(
		value: Partial< ProductVariation[ 'dimensions' ] >
	) {
		onChange(
			selection.map( ( { id, dimensions } ) => ( {
				id,
				dimensions: {
					...dimensions,
					...value,
				},
			} ) )
		);
	}

	return (
		<Dropdown
			// @ts-expect-error missing prop in types.
			popoverProps={ {
				placement: 'right-start',
			} }
			renderToggle={ ( { isOpen, onToggle } ) => (
				<MenuItem
					onClick={ () => {
						recordEvent( 'product_variations_menu_shipping_click', {
							source: TRACKS_SOURCE,
							variation_id: ids,
						} );
						onToggle();
					} }
					aria-expanded={ isOpen }
					icon={ chevronRight }
					iconPosition="right"
				>
					{ __( 'Shipping', 'woocommerce' ) }
				</MenuItem>
			) }
			renderContent={ () => (
				<div className="components-dropdown-menu__menu">
					<MenuGroup>
						{ window.wcAdminFeatures[
							'product-virtual-downloadable'
						] && (
							<MenuItem
								onClick={ () => {
									recordEvent(
										'product_variations_menu_shipping_select',
										{
											source: TRACKS_SOURCE,
											action: 'toggle_shipping',
											variation_id: ids,
										}
									);
									onChange(
										selection.map(
											( { id, virtual } ) => ( {
												id,
												virtual: ! virtual,
											} )
										)
									);
									recordEvent(
										'product_variations_menu_shipping_update',
										{
											source: TRACKS_SOURCE,
											action: 'toggle_shipping',
											variation_id: ids,
										}
									);
									onClose();
								} }
							>
								{ __( 'Toggle shipping', 'woocommerce' ) }
							</MenuItem>
						) }
						<MenuItem
							onClick={ () => {
								recordEvent(
									'product_variations_menu_shipping_select',
									{
										source: TRACKS_SOURCE,
										action: 'dimensions_length_set',
										variation_id: ids,
									}
								);
								handlePrompt( {
									onOk( value ) {
										recordEvent(
											'product_variations_menu_shipping_update',
											{
												source: TRACKS_SOURCE,
												action: 'dimensions_length_set',
												variation_id: ids,
											}
										);
										handleDimensionsChange( {
											length: value,
										} );
									},
								} );
								onClose();
							} }
						>
							{ __( 'Set length', 'woocommerce' ) }
						</MenuItem>
						<MenuItem
							onClick={ () => {
								recordEvent(
									'product_variations_menu_shipping_select',
									{
										source: TRACKS_SOURCE,
										action: 'dimensions_width_set',
										variation_id: ids,
									}
								);
								handlePrompt( {
									onOk( value ) {
										recordEvent(
											'product_variations_menu_shipping_update',
											{
												source: TRACKS_SOURCE,
												action: 'dimensions_width_set',
												variation_id: ids,
											}
										);
										handleDimensionsChange( {
											width: value,
										} );
									},
								} );
								onClose();
							} }
						>
							{ __( 'Set width', 'woocommerce' ) }
						</MenuItem>
						<MenuItem
							onClick={ () => {
								recordEvent(
									'product_variations_menu_shipping_select',
									{
										source: TRACKS_SOURCE,
										action: 'dimensions_height_set',
										variation_id: ids,
									}
								);
								handlePrompt( {
									onOk( value ) {
										recordEvent(
											'product_variations_menu_shipping_update',
											{
												source: TRACKS_SOURCE,
												action: 'dimensions_height_set',
												variation_id: ids,
											}
										);
										handleDimensionsChange( {
											height: value,
										} );
									},
								} );
								onClose();
							} }
						>
							{ __( 'Set height', 'woocommerce' ) }
						</MenuItem>
						<MenuItem
							onClick={ () => {
								recordEvent(
									'product_variations_menu_shipping_select',
									{
										source: TRACKS_SOURCE,
										action: 'weight_set',
										variation_id: ids,
									}
								);
								handlePrompt( {
									onOk( value ) {
										recordEvent(
											'product_variations_menu_shipping_update',
											{
												source: TRACKS_SOURCE,
												action: 'weight_set',
												variation_id: ids,
											}
										);
										onChange(
											selection.map( ( { id } ) => ( {
												id,
												weight: value,
											} ) )
										);
									},
								} );
								onClose();
							} }
						>
							{ __( 'Set weight', 'woocommerce' ) }
						</MenuItem>
					</MenuGroup>
					<VariationQuickUpdateMenuItem.Slot
						group={ 'shipping' }
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
