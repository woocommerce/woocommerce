/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	CollapsibleContent,
	DateTimePickerControl,
	Link,
	useFormContext,
	__experimentalTooltip as Tooltip,
} from '@woocommerce/components';
import {
	Product,
	OPTIONS_STORE_NAME,
	SETTINGS_STORE_NAME,
	EXPERIMENTAL_TAX_CLASSES_STORE_NAME,
	TaxClass,
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
	RadioControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import './pricing-section.scss';
import { formatCurrencyDisplayValue } from './utils';
import { ProductSectionLayout } from '../layout/product-section-layout';
import { ADMIN_URL } from '../../utils/admin-settings';
import { CurrencyContext } from '../../lib/currency-context';
import { useProductHelper } from '../use-product-helper';
import { STANDARD_RATE_TAX_CLASS_SLUG } from '../constants';

const PRODUCT_SCHEDULED_SALE_SLUG = 'product-scheduled-sale';

export const PricingSection: React.FC = () => {
	const { sanitizePrice } = useProductHelper();
	const { getInputProps, setValues, values } = useFormContext< Product >();
	const [ showSaleSchedule, setShowSaleSchedule ] = useState( false );
	const [ userToggledSaleSchedule, setUserToggledSaleSchedule ] =
		useState( false );
	const [ autoToggledSaleSchedule, setAutoToggledSaleSchedule ] =
		useState( false );
	const {
		isResolving: isTaxSettingsResolving,
		taxSettings,
		taxesEnabled,
	} = useSelect( ( select ) => {
		const { getSettings, hasFinishedResolution } =
			select( SETTINGS_STORE_NAME );
		return {
			isResolving: ! hasFinishedResolution( 'getSettings', [ 'tax' ] ),
			taxSettings: getSettings( 'tax' ).tax || {},
			taxesEnabled:
				getSettings( 'general' )?.general?.woocommerce_calc_taxes ===
				'yes',
		};
	} );

	const { isResolving: isTaxClassesResolving, taxClasses } = useSelect(
		( select ) => {
			const { hasFinishedResolution, getTaxClasses } = select(
				EXPERIMENTAL_TAX_CLASSES_STORE_NAME
			);
			return {
				isResolving: ! hasFinishedResolution( 'getTaxClasses' ),
				taxClasses: getTaxClasses< TaxClass[] >(),
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
		prefix: currencyConfig.symbol,
		className: 'half-width-field components-currency-control',
		sanitize: ( value: Product[ keyof Product ] ) => {
			return sanitizePrice( String( value ) );
		},
		onFocus( event: React.FocusEvent< HTMLInputElement > ) {
			// In some browsers like safari .select() function inside
			// the onFocus event doesn't work as expected because it
			// conflicts with onClick the first time user click the
			// input. Using setTimeout defers the text selection and
			// avoid the unexpected behaviour.
			setTimeout(
				function deferSelection( element: HTMLInputElement ) {
					element.select();
				},
				0,
				event.currentTarget
			);
		},
		onKeyUp( event: React.KeyboardEvent< HTMLInputElement > ) {
			const name = event.currentTarget.name as keyof Pick<
				Product,
				'regular_price' | 'sale_price'
			>;
			const amount = Number.parseFloat(
				sanitizePrice( values[ name ] || '0' )
			);
			const step = Number( event.currentTarget.step || '1' );
			if ( event.code === 'ArrowUp' ) {
				setValues( {
					[ name ]: String( amount + step ),
				} as unknown as Product );
			}
			if ( event.code === 'ArrowDown' ) {
				setValues( {
					[ name ]: String( amount - step ),
				} as unknown as Product );
			}
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

	const taxStatusProps = getInputProps( 'tax_status' );
	// These properties cause issues with the RadioControl component.
	// A fix to form upstream would help if we can identify what type of input is used.
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore
	delete taxStatusProps.checked;
	delete taxStatusProps.value;

	const taxClassProps = getInputProps( 'tax_class' );
	// These properties cause issues with the RadioControl component.
	// A fix to form upstream would help if we can identify what type of input is used.
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore
	delete taxClassProps.checked;
	delete taxClassProps.value;

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
							name="regular_price"
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

			{ taxesEnabled && (
				<Card>
					<CardBody>
						<RadioControl
							{ ...taxStatusProps }
							label={ __( 'Charge sales tax on', 'woocommerce' ) }
							options={ [
								{
									label: __(
										'Product and shipping',
										'woocommerce'
									),
									value: 'taxable',
								},
								{
									label: __( 'Only shipping', 'woocommerce' ),
									value: 'shipping',
								},
								{
									label: __(
										'Don’t charge tax',
										'woocommerce'
									),
									value: 'none',
								},
							] }
						/>

						<CollapsibleContent
							toggleText={ __( 'Advanced', 'woocommerce' ) }
						>
							{ ! isTaxClassesResolving &&
								taxClasses.length > 0 && (
									<RadioControl
										{ ...taxClassProps }
										label={
											<>
												<span>
													{ __(
														'Tax class',
														'woocommerce'
													) }
												</span>
												<span className="woocommerce-product-form__secondary-text">
													{ interpolateComponents( {
														mixedString: __(
															'Apply a tax rate if this product qualifies for tax reduction or exemption. {{link}}Learn more{{/link}}',
															'woocommerce'
														),
														components: {
															link: (
																<Link
																	href="https://woocommerce.com/document/setting-up-taxes-in-woocommerce/#shipping-tax-class"
																	target="_blank"
																	type="external"
																>
																	<></>
																</Link>
															),
														},
													} ) }
												</span>
											</>
										}
										options={ taxClasses.map(
											( taxClass ) => ( {
												label: taxClass.name,
												value:
													taxClass.slug ===
													STANDARD_RATE_TAX_CLASS_SLUG
														? ''
														: taxClass.slug,
											} )
										) }
									/>
								) }
						</CollapsibleContent>
					</CardBody>
				</Card>
			) }
		</ProductSectionLayout>
	);
};
