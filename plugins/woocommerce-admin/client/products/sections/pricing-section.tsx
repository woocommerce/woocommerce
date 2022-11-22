/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	DateTimePickerControl,
	Link,
	useFormContext,
	__experimentalTooltip as Tooltip,
} from '@woocommerce/components';
import {
	Product,
	OPTIONS_STORE_NAME,
	SETTINGS_STORE_NAME,
} from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { useContext, useEffect, useState } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { format as formatDate } from '@wordpress/date';
import moment from 'moment';
import interpolateComponents from '@automattic/interpolate-components';
import {
	// @ts-expect-error `__experimentalInputControl` does exist.
	__experimentalInputControl as InputControl,
	BaseControl,
	Card,
	CardBody,
	ToggleControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import './pricing-section.scss';
import { formatCurrencyDisplayValue, getCurrencySymbolProps } from './utils';
import { ProductSectionLayout } from '../layout/product-section-layout';
import { ADMIN_URL } from '../../utils/admin-settings';
import { CurrencyContext } from '../../lib/currency-context';
import { useProductHelper } from '../use-product-helper';

const PRODUCT_SCHEDULED_SALE_SLUG = 'product-scheduled-sale';

export const PricingSection: React.FC = () => {
	const { sanitizePrice } = useProductHelper();
	const { getInputProps, setValues, values } = useFormContext< Product >();
	const [ showSaleSchedule, setShowSaleSchedule ] = useState( false );
	const [ userToggledSaleSchedule, setUserToggledSaleSchedule ] =
		useState( false );
	const [ autoToggledSaleSchedule, setAutoToggledSaleSchedule ] =
		useState( false );
	const { isResolving: isTaxSettingsResolving, taxSettings } = useSelect(
		( select ) => {
			const { getSettings, hasFinishedResolution } =
				select( SETTINGS_STORE_NAME );
			return {
				isResolving: ! hasFinishedResolution( 'getSettings', [
					'tax',
				] ),
				taxSettings: getSettings( 'tax' ).tax || {},
			};
		}
	);
	const pricesIncludeTax =
		taxSettings.woocommerce_prices_include_tax === 'yes';
	const context = useContext( CurrencyContext );
	const { getCurrencyConfig, formatAmount } = context;
	const currencyConfig = getCurrencyConfig();

	const taxIncludedInPriceText = __(
		'Per your {{link}}store settings{{/link}}, tax is {{strong}}included{{/strong}} in the price.',
		'woocommerce'
	);
	const taxNotIncludedInPriceText = __(
		'Per your {{link}}store settings{{/link}}, tax is {{strong}}not included{{/strong}} in the price.',
		'woocommerce'
	);

	const { dateFormat, timeFormat } = useSelect( ( select ) => {
		const { getOption } = select( OPTIONS_STORE_NAME );
		return {
			dateFormat: ( getOption( 'date_format' ) as string ) || 'F j, Y',
			timeFormat: ( getOption( 'time_format' ) as string ) || 'H:i',
		};
	} );

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

	const taxSettingsElement = interpolateComponents( {
		mixedString: pricesIncludeTax
			? taxIncludedInPriceText
			: taxNotIncludedInPriceText,
		components: {
			link: (
				<Link
					href={ `${ ADMIN_URL }admin.php?page=wc-settings&tab=tax` }
					target="_blank"
					type="external"
					onClick={ () => {
						recordEvent(
							'product_pricing_list_price_help_tax_settings_click'
						);
					} }
				>
					<></>
				</Link>
			),
			strong: <strong />,
		},
	} );

	const currencyInputProps = {
		...getCurrencySymbolProps( currencyConfig ),
		className: 'half-width-field',
		sanitize: ( value: Product[ keyof Product ] ) => {
			return sanitizePrice( String( value ) );
		},
	};
	const regularPriceProps = getInputProps(
		'regular_price',
		currencyInputProps
	);
	const salePriceProps = getInputProps( 'sale_price', currencyInputProps );

	const dateTimePickerProps = {
		className: 'woocommerce-product__date-time-picker',
		isDateOnlyPicker: true,
		dateTimeFormat: dateFormat,
	};

	return (
		<ProductSectionLayout
			title={ __( 'Pricing', 'woocommerce' ) }
			description={
				<>
					<span>
						{ __(
							'Set a competitive price, put the product on sale, and manage tax calculations.',
							'woocommerce'
						) }
					</span>
					<Link
						className="woocommerce-form-section__header-link"
						href="https://woocommerce.com/posts/how-to-price-products-strategies-expert-tips/"
						target="_blank"
						type="external"
						onClick={ () => {
							recordEvent( 'add_product_pricing_help' );
						} }
					>
						{ __(
							'How to price your product: expert tips',
							'woocommerce'
						) }
					</Link>
				</>
			}
		>
			<Card>
				<CardBody>
					<BaseControl
						id="product_pricing_regular_price"
						help={ regularPriceProps?.help ?? '' }
					>
						<InputControl
							{ ...regularPriceProps }
							label={ __( 'List price', 'woocommerce' ) }
							value={ formatCurrencyDisplayValue(
								String( regularPriceProps?.value ),
								currencyConfig,
								formatAmount
							) }
						/>
					</BaseControl>
					{ ! isTaxSettingsResolving && (
						<span className="woocommerce-product-form__secondary-text">
							{ taxSettingsElement }
						</span>
					) }

					<BaseControl
						id="product_pricing_sale_price"
						help={ salePriceProps?.help ?? '' }
					>
						<InputControl
							{ ...salePriceProps }
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
														moment().startOf(
															'day'
														)
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
								placeholder={ __(
									'No end date',
									'woocommerce'
								) }
								timeForDateOnly={ 'end-of-day' }
								currentDate={ values.date_on_sale_to_gmt }
								{ ...getInputProps( 'date_on_sale_to_gmt', {
									...dateTimePickerProps,
								} ) }
							/>
						</>
					) }
				</CardBody>
			</Card>
		</ProductSectionLayout>
	);
};
