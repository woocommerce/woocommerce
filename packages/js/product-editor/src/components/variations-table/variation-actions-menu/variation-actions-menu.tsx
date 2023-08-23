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

function isPercentage( value: string ) {
	return value.endsWith( '%' );
}

function parsePercentage( value: string ) {
	const stringNumber = value.substring( 0, value.length - 1 );
	if ( Number.isNaN( Number( stringNumber ) ) ) {
		return undefined;
	}
	return Number( stringNumber );
}

function addFixedOrPercentage(
	value: string,
	fixedOrPercentage: string,
	increaseOrDecrease: 1 | -1 = 1
) {
	if ( isPercentage( fixedOrPercentage ) ) {
		if ( Number.isNaN( Number( value ) ) ) {
			return 0;
		}
		const percentage = parsePercentage( fixedOrPercentage );
		if ( percentage === undefined ) {
			return Number( value );
		}
		return (
			Number( value ) +
			Number( value ) * ( percentage / 100 ) * increaseOrDecrease
		);
	}
	if ( Number.isNaN( Number( value ) ) ) {
		if ( Number.isNaN( Number( fixedOrPercentage ) ) ) {
			return undefined;
		}
		return Number( fixedOrPercentage );
	}
	return Number( value ) + Number( fixedOrPercentage ) * increaseOrDecrease;
}

export function VariationActionsMenu( {
	variation,
	onChange,
	onDelete,
}: VariationActionsMenuProps ) {
	function handlePropmt(
		propertyName: keyof ProductVariation,
		label: string = __( 'Enter a value', 'woocommerce' ),
		parser: ( value: string ) => unknown = ( value ) => value
	) {
		const value = window.prompt( label );
		if ( value === null ) return;

		onChange( {
			[ propertyName ]: parser( value.trim() ),
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
								<div className="components-dropdown-menu__menu">
									<MenuGroup
										label={ __(
											'List price',
											'woocommerce'
										) }
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
										<MenuItem
											onClick={ () => {
												handlePropmt(
													'regular_price',
													__(
														'Enter a value (fixed or %)',
														'woocommerce'
													),
													( value ) =>
														addFixedOrPercentage(
															variation.regular_price,
															value
														)?.toFixed( 2 )
												);
												onClose();
											} }
										>
											{ __(
												'Increase list price',
												'woocommerce'
											) }
										</MenuItem>
										<MenuItem
											onClick={ () => {
												handlePropmt(
													'regular_price',
													__(
														'Enter a value (fixed or %)',
														'woocommerce'
													),
													( value ) =>
														addFixedOrPercentage(
															variation.regular_price,
															value,
															-1
														)?.toFixed( 2 )
												);
												onClose();
											} }
										>
											{ __(
												'Decrease list price',
												'woocommerce'
											) }
										</MenuItem>
									</MenuGroup>
									<MenuGroup
										label={ __(
											'Sale price',
											'woocommerce'
										) }
									>
										<MenuItem
											onClick={ () => {
												handlePropmt( 'sale_price' );
												onClose();
											} }
										>
											{ __(
												'Set sale price',
												'woocommerce'
											) }
										</MenuItem>
										<MenuItem
											onClick={ () => {
												handlePropmt(
													'sale_price',
													__(
														'Enter a value (fixed or %)',
														'woocommerce'
													),
													( value ) =>
														addFixedOrPercentage(
															variation.sale_price,
															value
														)?.toFixed( 2 )
												);
												onClose();
											} }
										>
											{ __(
												'Increase sale price',
												'woocommerce'
											) }
										</MenuItem>
										<MenuItem
											onClick={ () => {
												handlePropmt(
													'sale_price',
													__(
														'Enter a value (fixed or %)',
														'woocommerce'
													),
													( value ) =>
														addFixedOrPercentage(
															variation.sale_price,
															value,
															-1
														)?.toFixed( 2 )
												);
												onClose();
											} }
										>
											{ __(
												'Decrease sale price',
												'woocommerce'
											) }
										</MenuItem>
									</MenuGroup>
								</div>
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
