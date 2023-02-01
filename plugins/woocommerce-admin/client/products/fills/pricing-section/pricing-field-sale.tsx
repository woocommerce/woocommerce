/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	Link,
	__experimentalTooltip as Tooltip,
	DateTimePickerControl,
	useFormContext2,
} from '@woocommerce/components';
import { recordEvent } from '@woocommerce/tracks';
import { useContext, useState, useEffect } from '@wordpress/element';
import { useController } from 'react-hook-form';
import { Product, OPTIONS_STORE_NAME } from '@woocommerce/data';
import { useSelect } from '@wordpress/data';
import interpolateComponents from '@automattic/interpolate-components';
import { format as formatDate } from '@wordpress/date';
import moment from 'moment';
import {
	BaseControl,
	// @ts-expect-error `__experimentalInputControl` does exist.
	__experimentalInputControl as InputControl,
	ToggleControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { CurrencyInputProps } from './pricing-section-fills';
import { formatCurrencyDisplayValue } from '../../sections/utils';
import { CurrencyContext } from '../../../lib/currency-context';

type PricingListFieldProps = {
	currencyInputProps: CurrencyInputProps;
};

const PRODUCT_SCHEDULED_SALE_SLUG = 'product-scheduled-sale';

export const PricingSaleField: React.FC< PricingListFieldProps > = ( {
	currencyInputProps,
} ) => {
	const { control, setValue, watch } = useFormContext2< Product >();
	const [ date_on_sale_from_gmt, date_on_sale_to_gmt ] = watch( [
		'date_on_sale_from_gmt',
		'date_on_sale_to_gmt',
	] );
	const { field } = useController( {
		name: 'sale_price',
		control,
	} );
	const { field: toGmtField } = useController( {
		name: 'date_on_sale_to_gmt',
		control,
	} );
	const { field: fromGmtField } = useController( {
		name: 'date_on_sale_from_gmt',
		control,
	} );

	const { dateFormat, timeFormat } = useSelect( ( select ) => {
		const { getOption } = select( OPTIONS_STORE_NAME );
		return {
			dateFormat: ( getOption( 'date_format' ) as string ) || 'F j, Y',
			timeFormat: ( getOption( 'time_format' ) as string ) || 'H:i',
		};
	} );

	const context = useContext( CurrencyContext );
	const { getCurrencyConfig, formatAmount } = context;
	const currencyConfig = getCurrencyConfig();

	const [ showSaleSchedule, setShowSaleSchedule ] = useState( false );
	const [ userToggledSaleSchedule, setUserToggledSaleSchedule ] =
		useState( false );
	const [ autoToggledSaleSchedule, setAutoToggledSaleSchedule ] =
		useState( false );

	useEffect( () => {
		if ( userToggledSaleSchedule || autoToggledSaleSchedule ) {
			return;
		}

		const hasDateOnSaleFrom =
			typeof date_on_sale_from_gmt === 'string' &&
			date_on_sale_from_gmt.length > 0;
		const hasDateOnSaleTo =
			typeof date_on_sale_to_gmt === 'string' &&
			date_on_sale_to_gmt.length > 0;

		const hasSaleSchedule = hasDateOnSaleFrom || hasDateOnSaleTo;

		if ( hasSaleSchedule ) {
			setAutoToggledSaleSchedule( true );
			setShowSaleSchedule( true );
		}
	}, [
		userToggledSaleSchedule,
		autoToggledSaleSchedule,
		date_on_sale_from_gmt,
		date_on_sale_to_gmt,
	] );

	const dateTimePickerProps = {
		className: 'woocommerce-product__date-time-picker',
		isDateOnlyPicker: true,
		dateTimeFormat: dateFormat,
	};

	const onSaleScheduleToggleChange = ( value: boolean ) => {
		recordEvent( 'product_pricing_schedule_sale_toggle_click', {
			enabled: value,
		} );

		setUserToggledSaleSchedule( true );
		setShowSaleSchedule( value );

		if ( value ) {
			setValue(
				'date_on_sale_from_gmt',
				moment().startOf( 'day' ).toISOString()
			);
			setValue( 'date_on_sale_to_gmt', null );
		} else {
			setValue( 'date_on_sale_from_gmt', null );
			setValue( 'date_on_sale_to_gmt', null );
		}
	};

	return (
		<>
			<BaseControl id="product_pricing_sale_price">
				<InputControl
					{ ...field }
					name="sale_price"
					label={ __( 'Sale price', 'woocommerce' ) }
					value={ formatCurrencyDisplayValue(
						String( field?.value ),
						currencyConfig,
						formatAmount
					) }
				/>
			</BaseControl>

			<ToggleControl
				label={
					<>
						{ __( 'Schedule sale', 'woocommerce' ) }
						<Tooltip
							text={ interpolateComponents( {
								mixedString: __(
									'The sale will start at the beginning of the "From" date ({{startTime/}}) and expire at the end of the "To" date ({{endTime/}}). {{moreLink/}}',
									'woocommerce'
								),
								components: {
									startTime: (
										<span>
											{ formatDate(
												timeFormat,
												moment().startOf( 'day' )
											) }
										</span>
									),
									endTime: (
										<span>
											{ formatDate(
												timeFormat,
												moment().endOf( 'day' )
											) }
										</span>
									),
									moreLink: (
										<Link
											href="https://woocommerce.com/document/managing-products/#product-data"
											target="_blank"
											type="external"
											onClick={ () =>
												recordEvent(
													'add_product_learn_more',
													{
														category:
															PRODUCT_SCHEDULED_SALE_SLUG,
													}
												)
											}
										>
											{ __(
												'Learn more',
												'woocommerce'
											) }
										</Link>
									),
								},
							} ) }
						/>
					</>
				}
				checked={ showSaleSchedule }
				onChange={ onSaleScheduleToggleChange }
				// eslint-disable-next-line @typescript-eslint/ban-ts-comment
				// @ts-ignore disabled prop exists
				disabled={ ! ( field.value?.length > 0 ) }
			/>

			{ showSaleSchedule && (
				<>
					<DateTimePickerControl
						label={ __( 'From', 'woocommerce' ) }
						placeholder={ __( 'Now', 'woocommerce' ) }
						timeForDateOnly={ 'start-of-day' }
						currentDate={ fromGmtField.value }
						{ ...fromGmtField }
						{ ...dateTimePickerProps }
					/>

					<DateTimePickerControl
						label={ __( 'To', 'woocommerce' ) }
						placeholder={ __( 'No end date', 'woocommerce' ) }
						timeForDateOnly={ 'end-of-day' }
						currentDate={ toGmtField.value }
						{ ...toGmtField }
						{ ...dateTimePickerProps }
					/>
				</>
			) }
		</>
	);
};
