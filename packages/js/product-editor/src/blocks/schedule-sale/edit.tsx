/**
 * External dependencies
 */
import { DateTimePickerControl } from '@woocommerce/components';
import { recordEvent } from '@woocommerce/tracks';
import { useBlockProps } from '@wordpress/block-editor';
import { BlockEditProps } from '@wordpress/blocks';
import { ToggleControl } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { createElement, useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import moment from 'moment';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore We need this to get the datetime format for the DateTimePickerControl.
// eslint-disable-next-line @woocommerce/dependency-group
import { getSettings } from '@wordpress/date';

/**
 * Internal dependencies
 */
import { ScheduleSalePricingBlockAttributes } from './types';
import { useValidation } from '../../hooks/use-validation';

export function Edit( {}: BlockEditProps< ScheduleSalePricingBlockAttributes > ) {
	const blockProps = useBlockProps( {
		className: 'wp-block-woocommerce-product-schedule-sale-fields',
	} );

	const dateTimeFormat = getSettings().formats.datetime;

	const [ showScheduleSale, setShowScheduleSale ] = useState( false );

	const [ salePrice ] = useEntityProp< string | null >(
		'postType',
		'product',
		'sale_price'
	);

	const isSalePriceGreaterThanZero =
		Number.parseFloat( salePrice || '0' ) > 0;

	const [ dateOnSaleFromGmt, setDateOnSaleFromGmt ] = useEntityProp<
		string | null
	>( 'postType', 'product', 'date_on_sale_from_gmt' );

	const [ dateOnSaleToGmt, setDateOnSaleToGmt ] = useEntityProp<
		string | null
	>( 'postType', 'product', 'date_on_sale_to_gmt' );

	const today = moment().toISOString();

	function handleToggleChange( value: boolean ) {
		recordEvent( 'product_pricing_schedule_sale_toggle_click', {
			enabled: value,
		} );

		setShowScheduleSale( value );

		if ( value ) {
			setDateOnSaleFromGmt( today );
			setDateOnSaleToGmt( '' );
		} else {
			setDateOnSaleFromGmt( '' );
			setDateOnSaleToGmt( '' );
		}
	}

	// Hide and clean date fields if the user manually change
	// the sale price to zero or less.
	useEffect( () => {
		if ( ! isSalePriceGreaterThanZero ) {
			setShowScheduleSale( false );
			setDateOnSaleFromGmt( '' );
			setDateOnSaleToGmt( '' );
		}
	}, [ isSalePriceGreaterThanZero ] );

	// Automatically show date fields if `from` or `to` dates have
	// any value.
	useEffect( () => {
		if ( dateOnSaleFromGmt || dateOnSaleToGmt ) {
			setShowScheduleSale( true );
		}
	}, [ dateOnSaleFromGmt, dateOnSaleToGmt ] );

	const isDateOnSaleToGmtValid = useValidation(
		'product/date_on_sale_to_gmt',
		() =>
			! showScheduleSale ||
			! dateOnSaleToGmt ||
			moment( dateOnSaleFromGmt ).isBefore( dateOnSaleToGmt, 'minute' )
	);

	return (
		<div { ...blockProps }>
			<ToggleControl
				label={ __( 'Schedule sale', 'woocommerce' ) }
				checked={ showScheduleSale }
				onChange={ handleToggleChange }
				disabled={ ! isSalePriceGreaterThanZero }
			/>

			{ showScheduleSale && (
				<div className="wp-block-columns">
					<div className="wp-block-column">
						<DateTimePickerControl
							label={ __( 'From', 'woocommerce' ) }
							placeholder={ __(
								'Sale start date and time (optional)',
								'woocommerce'
							) }
							dateTimeFormat={ dateTimeFormat }
							currentDate={ dateOnSaleFromGmt }
							onChange={ setDateOnSaleFromGmt }
						/>
					</div>

					<div className="wp-block-column">
						<DateTimePickerControl
							label={ __( 'To', 'woocommerce' ) }
							placeholder={ __(
								'Sale end date and time (optional)',
								'woocommerce'
							) }
							dateTimeFormat={ dateTimeFormat }
							currentDate={ dateOnSaleToGmt }
							onChange={ setDateOnSaleToGmt }
							className={
								isDateOnSaleToGmtValid ? undefined : 'has-error'
							}
							help={
								isDateOnSaleToGmtValid
									? undefined
									: __(
											'To date must be greater than From date.',
											'woocommerce'
									  )
							}
						/>
					</div>
				</div>
			) }
		</div>
	);
}
