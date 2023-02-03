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
import { useController, useWatch } from 'react-hook-form';
import { Product, OPTIONS_STORE_NAME } from '@woocommerce/data';
import { useSelect } from '@wordpress/data';
import interpolateComponents from '@automattic/interpolate-components';
import { format as formatDate } from '@wordpress/date';
import moment from 'moment';
import classNames from 'classnames';
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
import { getErrorMessageProps } from '~/products/utils/get-error-message-props';

type PricingListFieldProps = {
	currencyInputProps: CurrencyInputProps;
};

const PRODUCT_SCHEDULED_SALE_SLUG = 'product-scheduled-sale';

export const PricingSaleField: React.FC< PricingListFieldProps > = ( {
	currencyInputProps,
} ) => {
	const { control, setValue } = useFormContext2< Product >();
	const { field, fieldState } = useController( {
		name: 'sale_price',
		control,
		rules: {
			pattern: {
				value: /^[0-9.,]+$/,
				message: __(
					'Please enter a price with one monetary decimal point without thousand separators and currency symbols.',
					'woocommerce'
				),
			},
			validate: ( sale_price, { regular_price } ) => {
				if (
					sale_price &&
					( ! regular_price ||
						parseFloat( sale_price ) >=
							parseFloat( regular_price ) )
				) {
					return __(
						'Sale price cannot be equal to or higher than list price.',
						'woocommerce'
					);
				}
			},
		},
	} );
	const { field: fromGmtField, fieldState: fromGmtFieldState } =
		useController( {
			name: 'date_on_sale_from_gmt',
			control,
			rules: {
				validate: ( fromGmt, { date_on_sale_to_gmt } ) => {
					const dateOnSaleFrom = moment(
						fromGmt,
						moment.ISO_8601,
						true
					);
					if ( fromGmt && ! dateOnSaleFrom.isValid() ) {
						return __(
							'Please enter a valid date.',
							'woocommerce'
						);
					}
					const dateOnSaleTo = moment(
						date_on_sale_to_gmt,
						moment.ISO_8601,
						true
					);
					if ( dateOnSaleFrom.isAfter( dateOnSaleTo ) ) {
						return __(
							'The start date of the sale must be before the end date.',
							'woocommerce'
						);
					}
				},
			},
		} );
	const { field: toGmtField, fieldState: toGmtFieldState } = useController( {
		name: 'date_on_sale_to_gmt',
		control,
		rules: {
			validate: ( toGmt, { date_on_sale_from_gmt } ) => {
				const dateOnSaleTo = moment( toGmt, moment.ISO_8601, true );
				if ( toGmt && ! dateOnSaleTo.isValid() ) {
					return __( 'Please enter a valid date.', 'woocommerce' );
				}
				const dateOnSaleFrom = moment(
					date_on_sale_from_gmt,
					moment.ISO_8601,
					true
				);
				if ( dateOnSaleTo.isBefore( dateOnSaleFrom ) ) {
					return __(
						'The end date of the sale must be after the start date.',
						'woocommerce'
					);
				}
			},
		},
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
			typeof fromGmtField.value === 'string' &&
			fromGmtField.value.length > 0;
		const hasDateOnSaleTo =
			typeof toGmtField.value === 'string' && toGmtField.value.length > 0;

		const hasSaleSchedule = hasDateOnSaleFrom || hasDateOnSaleTo;

		if ( hasSaleSchedule ) {
			setAutoToggledSaleSchedule( true );
			setShowSaleSchedule( true );
		}
	}, [
		userToggledSaleSchedule,
		autoToggledSaleSchedule,
		fromGmtField.value,
		toGmtField.value,
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

	const errorMessageProps = getErrorMessageProps( fieldState );
	const toGmtErrorMessageProps = getErrorMessageProps( toGmtFieldState );
	const fromGmtMessageProps = getErrorMessageProps( fromGmtFieldState );

	return (
		<>
			<BaseControl
				id="product_pricing_sale_price"
				help={ errorMessageProps.help ?? '' }
			>
				<InputControl
					{ ...currencyInputProps }
					{ ...field }
					className={ classNames(
						errorMessageProps.className,
						currencyInputProps.className
					) }
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
						className={ classNames(
							fromGmtMessageProps.className,
							dateTimePickerProps.className
						) }
						help={ fromGmtMessageProps.help ?? '' }
					/>

					<DateTimePickerControl
						label={ __( 'To', 'woocommerce' ) }
						placeholder={ __( 'No end date', 'woocommerce' ) }
						timeForDateOnly={ 'end-of-day' }
						currentDate={ toGmtField.value }
						{ ...toGmtField }
						{ ...dateTimePickerProps }
						className={ classNames(
							toGmtErrorMessageProps.className,
							dateTimePickerProps.className
						) }
						help={ toGmtErrorMessageProps.help ?? '' }
					/>
				</>
			) }
		</>
	);
};
