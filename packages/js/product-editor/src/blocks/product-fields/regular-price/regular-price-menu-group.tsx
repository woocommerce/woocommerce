/**
 * External dependencies
 */
import { createElement, Fragment } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';
import { MenuItem } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { TRACKS_SOURCE } from '../../../constants';
import { handlePrompt } from '../../../utils';
import { SetListPriceMenuItem } from '../../../components/variations-table/set-list-price-menu-item';
import { VariationQuickUpdateMenuGroup } from '../../../components/variations-table/variation-actions-menus/variation-quick-update-menu-group';

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

export function addFixedOrPercentage(
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

export const RegularPriceMenuGroup = () => (
	<VariationQuickUpdateMenuGroup
		name="list-price"
		label={ __( 'List price', 'woocommerce' ) }
	>
		{ ( { selection, onChange, onClose } ) => {
			const ids = selection.map( ( { id } ) => id );
			return (
				<>
					<SetListPriceMenuItem
						selection={ selection }
						onChange={ onChange }
						onClose={ onClose }
					/>
					<MenuItem
						onClick={ () => {
							recordEvent(
								'product_variations_menu_pricing_select',
								{
									source: TRACKS_SOURCE,
									action: 'list_price_increase',
									variation_id: ids,
								}
							);
							handlePrompt( {
								message: __(
									'Enter a value (fixed or %)',
									'woocommerce'
								),
								onOk( value ) {
									recordEvent(
										'product_variations_menu_pricing_update',
										{
											source: TRACKS_SOURCE,
											action: 'list_price_increase',
											variation_id: ids,
										}
									);
									onChange(
										selection.map(
											( { id, regular_price } ) => ( {
												id,
												regular_price:
													addFixedOrPercentage(
														regular_price,
														value
													)?.toFixed( 2 ),
											} )
										)
									);
								},
							} );
							onClose();
						} }
					>
						{ __( 'Increase list price', 'woocommerce' ) }
					</MenuItem>
					<MenuItem
						onClick={ () => {
							recordEvent(
								'product_variations_menu_pricing_select',
								{
									source: TRACKS_SOURCE,
									action: 'list_price_decrease',
									variation_id: ids,
								}
							);
							handlePrompt( {
								message: __(
									'Enter a value (fixed or %)',
									'woocommerce'
								),
								onOk( value ) {
									recordEvent(
										'product_variations_menu_pricing_update',
										{
											source: TRACKS_SOURCE,
											action: 'list_price_increase',
											variation_id: ids,
										}
									);
									onChange(
										selection.map(
											( { id, regular_price } ) => ( {
												id,
												regular_price:
													addFixedOrPercentage(
														regular_price,
														value,
														-1
													)?.toFixed( 2 ),
											} )
										)
									);
								},
							} );
							onClose();
						} }
					>
						{ __( 'Decrease list price', 'woocommerce' ) }
					</MenuItem>
				</>
			);
		} }
	</VariationQuickUpdateMenuGroup>
);
