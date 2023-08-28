/**
 * External dependencies
 */
import { Dropdown, MenuItem } from '@wordpress/components';
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { chevronRight } from '@wordpress/icons';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { ShippingMenuItemProps } from './types';
import { TRACKS_SOURCE } from '../../../constants';

export function ShippingMenuItem( {
	variation,
	handlePrompt,
	onClose,
}: ShippingMenuItemProps ) {
	return (
		<Dropdown
			position="middle right"
			renderToggle={ ( { isOpen, onToggle } ) => (
				<MenuItem
					onClick={ () => {
						recordEvent( 'product_variations_menu_shipping_click', {
							source: TRACKS_SOURCE,
							variation_id: variation.id,
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
					<MenuItem
						onClick={ () => {
							recordEvent(
								'product_variations_menu_shipping_select',
								{
									source: TRACKS_SOURCE,
									action: 'dimensions_length_set',
									variation_id: variation.id,
								}
							);
							handlePrompt(
								'dimensions',
								undefined,
								( value ) => {
									recordEvent(
										'product_variations_menu_shipping_update',
										{
											source: TRACKS_SOURCE,
											action: 'dimensions_length_set',
											variation_id: variation.id,
										}
									);
									return {
										...variation.dimensions,
										length: value,
									};
								}
							);
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
									variation_id: variation.id,
								}
							);
							handlePrompt(
								'dimensions',
								undefined,
								( value ) => {
									recordEvent(
										'product_variations_menu_shipping_update',
										{
											source: TRACKS_SOURCE,
											action: 'dimensions_width_set',
											variation_id: variation.id,
										}
									);
									return {
										...variation.dimensions,
										width: value,
									};
								}
							);
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
									variation_id: variation.id,
								}
							);
							handlePrompt(
								'dimensions',
								undefined,
								( value ) => {
									recordEvent(
										'product_variations_menu_shipping_update',
										{
											source: TRACKS_SOURCE,
											action: 'dimensions_height_set',
											variation_id: variation.id,
										}
									);
									return {
										...variation.dimensions,
										height: value,
									};
								}
							);
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
									variation_id: variation.id,
								}
							);
							handlePrompt( 'weight', undefined, ( value ) => {
								recordEvent(
									'product_variations_menu_shipping_update',
									{
										source: TRACKS_SOURCE,
										action: 'weight_set',
										variation_id: variation.id,
									}
								);
								return value;
							} );
							onClose();
						} }
					>
						{ __( 'Set weight', 'woocommerce' ) }
					</MenuItem>
				</div>
			) }
		/>
	);
}
