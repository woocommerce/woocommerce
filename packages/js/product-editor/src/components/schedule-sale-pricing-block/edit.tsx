/**
 * External dependencies
 */
import { DateTimePickerControl } from '@woocommerce/components';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { useBlockProps } from '@wordpress/block-editor';
import { BlockEditProps } from '@wordpress/blocks';
import { ToggleControl } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { createElement, useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { ScheduleSalePricingBlockAttributes } from './types';
import { ScheduleSaleLabel } from './schedule-sale-label';

export function Edit( {
	attributes,
}: BlockEditProps< ScheduleSalePricingBlockAttributes > ) {
	const blockProps = useBlockProps( {
		className: 'wp-block-woocommerce-product-schedule-sale-pricing',
	} );
	const {} = attributes;

	const dateFormat = useSelect( ( select ) => {
		const { getOption } = select( OPTIONS_STORE_NAME );
		return getOption< string | null >( 'date_format' ) || 'F j, Y';
	} );

	const [ showSaleSchedule, setShowSaleSchedule ] = useState( false );

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

	// @ts-ignore
	const today = moment().startOf( 'day' ).toISOString();

	function handleToggleChange( value: boolean ) {
		recordEvent( 'product_pricing_schedule_sale_toggle_click', {
			enabled: value,
		} );

		setShowSaleSchedule( value );

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
			setShowSaleSchedule( false );
			setDateOnSaleFromGmt( '' );
			setDateOnSaleToGmt( '' );
		}
	}, [ isSalePriceGreaterThanZero ] );

	// Automatically show date fields if `from` or `to` dates have
	// any value.
	useEffect( () => {
		if ( dateOnSaleFromGmt || dateOnSaleToGmt ) {
			setShowSaleSchedule( true );
		}
	}, [ dateOnSaleFromGmt, dateOnSaleToGmt ] );

	return (
		<div { ...blockProps }>
			<ToggleControl
				label={ <ScheduleSaleLabel /> }
				checked={ showSaleSchedule }
				onChange={ handleToggleChange }
				disabled={ ! isSalePriceGreaterThanZero }
			/>

			{ showSaleSchedule && (
				<div className="wp-block-columns">
					<div className="wp-block-column">
						<DateTimePickerControl
							label={ __( 'From', 'woocommerce' ) }
							placeholder={ __( 'Now', 'woocommerce' ) }
							timeForDateOnly={ 'start-of-day' }
							isDateOnlyPicker
							dateTimeFormat={ dateFormat }
							currentDate={ dateOnSaleFromGmt || today }
							onChange={ setDateOnSaleFromGmt }
						/>
					</div>

					<div className="wp-block-column">
						<DateTimePickerControl
							label={ __( 'To', 'woocommerce' ) }
							placeholder={ __( 'No end date', 'woocommerce' ) }
							timeForDateOnly={ 'end-of-day' }
							isDateOnlyPicker
							dateTimeFormat={ dateFormat }
							currentDate={ dateOnSaleToGmt }
							onChange={ setDateOnSaleToGmt }
						/>
					</div>
				</div>
			) }
		</div>
	);
}
