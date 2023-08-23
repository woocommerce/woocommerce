/**
 * External dependencies
 */
import {
	Dropdown,
	DropdownMenu,
	MenuGroup,
	MenuItem,
} from '@wordpress/components';
import { createElement, Fragment } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { chevronRight, moreVertical } from '@wordpress/icons';
import { ProductVariation } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { VariationActionsMenuProps } from './types';
import { TRACKS_SOURCE } from '../../../constants';

export function VariationActionsMenu( {
	variation,
	onChange,
	onDelete,
}: VariationActionsMenuProps ) {
	function handlePropmt( propertyName: keyof ProductVariation ) {
		const value = window.prompt( __( 'Enter a value', 'woocommerce' ) );

		onChange( {
			[ propertyName ]: value,
		} );
	}

	return (
		<DropdownMenu
			icon={ moreVertical }
			label={ __( 'Actions', 'woocommerce' ) }
			toggleProps={ {
				onClick() {
					recordEvent( 'product_variations_menu_view', {
						source: TRACKS_SOURCE,
					} );
				},
			} }
		>
			{ ( { onClose } ) => (
				<>
					<MenuGroup
						label={ sprintf(
							/** Translators: Variation ID */
							__( 'Variation Id: %s', 'woocommerce' ),
							variation.id
						) }
					>
						<MenuItem
							href={ variation.permalink }
							onClick={ () => {
								recordEvent( 'product_variations_preview', {
									source: TRACKS_SOURCE,
								} );
							} }
						>
							{ __( 'Preview', 'woocommerce' ) }
						</MenuItem>
					</MenuGroup>
					<MenuGroup>
						<Dropdown
							position="middle right"
							renderToggle={ ( { isOpen, onToggle } ) => (
								<MenuItem
									onClick={ onToggle }
									aria-expanded={ isOpen }
									icon={ chevronRight }
									iconPosition="right"
								>
									{ __( 'Pricing', 'woocommerce' ) }
								</MenuItem>
							) }
							renderContent={ () => (
								<MenuGroup
									label={ __( 'List price', 'woocommerce' ) }
								>
									<MenuItem
										onClick={ () => {
											handlePropmt( 'regular_price' );
											onClose();
										} }
									>
										{ __(
											'Set list price',
											'woocommerce'
										) }
									</MenuItem>
								</MenuGroup>
							) }
						/>
					</MenuGroup>
					<MenuGroup>
						<MenuItem
							isDestructive
							variant="link"
							onClick={ () => {
								onDelete( variation.id );
								onClose();
							} }
							className="woocommerce-product-variations__actions--delete"
						>
							{ __( 'Delete', 'woocommerce' ) }
						</MenuItem>
					</MenuGroup>
				</>
			) }
		</DropdownMenu>
	);
}
