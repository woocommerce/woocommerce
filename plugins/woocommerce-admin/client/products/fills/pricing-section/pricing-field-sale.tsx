/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	useFormContext,
	Link,
	__experimentalTooltip as Tooltip,
	DateTimePickerControl,
} from '@woocommerce/components';
import { recordEvent } from '@woocommerce/tracks';
import { useContext, useState, useEffect } from '@wordpress/element';
import { Product, OPTIONS_STORE_NAME } from '@woocommerce/data';
import { useSelect } from '@wordpress/data';
import interpolateComponents from '@automattic/interpolate-components';
import { format as formatDate } from '@wordpress/date';
import { formatCurrencyDisplayValue } from '@woocommerce/product-editor';
import moment from 'moment';
import { CurrencyContext } from '@woocommerce/currency';
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

type PricingListFieldProps = {
	currencyInputProps: CurrencyInputProps;
};

const PRODUCT_SCHEDULED_SALE_SLUG = 'product-scheduled-sale';

export const PricingSaleField: React.FC< PricingListFieldProps > = ( {
	currencyInputProps,
} ) => {
	const { getInputProps, values, setValues } = useFormContext< Product >();

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
			typeof values.date_on_sale_from_gmt === 'string' &&
			values.date_on_sale_from_gmt.length > 0;
		const hasDateOnSaleTo =
			typeof values.date_on_sale_to_gmt === 'string' &&
			values.date_on_sale_to_gmt.length > 0;

		const hasSaleSchedule = hasDateOnSaleFrom || hasDateOnSaleTo;

		if ( hasSaleSchedule ) {
			setAutoToggledSaleSchedule( true );
			setShowSaleSchedule( true );
		}
	}, [ userToggledSaleSchedule, autoToggledSaleSchedule, values ] );

	const salePriceProps = getInputProps( 'sale_price', currencyInputProps );

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
			setValues( {
				date_on_sale_from_gmt: moment().startOf( 'day' ).toISOString(),
				date_on_sale_to_gmt: null,
			} as Product );
		} else {
			setValues( {
				date_on_sale_from_gmt: null,
				date_on_sale_to_gmt: null,
			} as Product );
		}
	};

	return (
		<>
			<BaseControl
				id="product_pricing_sale_price"
				help={ salePriceProps?.help ?? '' }
			>
				<InputControl
					{ ...salePriceProps }
					name="sale_price"
					label={ __( 'Sale price', 'woocommerce' ) }
					value={ formatCurrencyDisplayValue(
						String( salePriceProps?.value ),
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
											href="https://woo.com/document/managing-products/#product-data"
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
				disabled={ ! ( values.sale_price?.length > 0 ) }
			/>

			{ showSaleSchedule && (
				<>
					<DateTimePickerControl
						label={ __( 'From', 'woocommerce' ) }
						placeholder={ __( 'Now', 'woocommerce' ) }
						timeForDateOnly={ 'start-of-day' }
						currentDate={ values.date_on_sale_from_gmt }
						{ ...getInputProps( 'date_on_sale_from_gmt', {
							...dateTimePickerProps,
						} ) }
					/>

					<DateTimePickerControl
						label={ __( 'To', 'woocommerce' ) }
						placeholder={ __( 'No end date', 'woocommerce' ) }
						timeForDateOnly={ 'end-of-day' }
						currentDate={ values.date_on_sale_to_gmt }
						{ ...getInputProps( 'date_on_sale_to_gmt', {
							...dateTimePickerProps,
						} ) }
					/>
				</>
			) }
		</>
	);
};
