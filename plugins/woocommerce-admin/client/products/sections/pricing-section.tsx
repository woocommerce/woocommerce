/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Link, useFormContext } from '@woocommerce/components';
import { Product, SETTINGS_STORE_NAME } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { useContext } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import interpolateComponents from '@automattic/interpolate-components';
import {
	// @ts-expect-error `__experimentalInputControl` does exist.
	__experimentalInputControl as InputControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import './pricing-section.scss';
import { ProductSectionLayout } from '../layout/product-section-layout';
import { getInputControlProps } from './utils';
import { ADMIN_URL } from '../../utils/admin-settings';
import { CurrencyContext } from '../../lib/currency-context';
import {
	NUMBERS_AND_DECIMAL_SEPARATOR,
	ONLY_ONE_DECIMAL_SEPARATOR,
} from '../constants';

export const PricingSection: React.FC = () => {
	const { getInputProps, setValue } = useFormContext< Product >();
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
	const { getCurrencyConfig } = context;
	const { decimalSeparator } = getCurrencyConfig();
	const priceValidation = ( value: string, name: string ) => {
		// Build regex to strip out everything except digits, decimal point and minus sign.
		const regex = new RegExp(
			NUMBERS_AND_DECIMAL_SEPARATOR.replace( '%s', decimalSeparator ),
			'g'
		);
		const decimalRegex = new RegExp(
			ONLY_ONE_DECIMAL_SEPARATOR.replaceAll( '%s', decimalSeparator ),
			'g'
		);
		const cleanValue = value
			.replace( regex, '' )
			.replace( decimalRegex, '' )
			.replace( decimalSeparator, '.' );
		setValue( name, cleanValue );
		return cleanValue;
	};

	const taxSettingsText =
		'Per your {{link}}store settings{{/link}}, tax is {{strong}}%sincluded{{/strong}} in the price.';
	const addNot = pricesIncludeTax ? '' : 'not ';
	taxSettingsText.replace( taxSettingsText, addNot );

	const taxSettingsElement = interpolateComponents( {
		mixedString: __(
			'Per your {{link}}store settings{{/link}}, tax is {{strong}}not included{{/strong}} in the price.',
			'woocommerce'
		),
		components: {
			link: (
				<Link
					href={ `${ ADMIN_URL }admin.php?page=wc-settings&tab=tax` }
					target="_blank"
					type="external"
					onClick={ () => {
						recordEvent( 'product_pricing_list_price_help' );
					} }
				>
					<></>
				</Link>
			),
			strong: <strong />,
		},
	} );

	const salePriceTitle = interpolateComponents( {
		mixedString: __(
			'Sale price {{span}}(optional){{/span}}',
			'woocommerce'
		),
		components: {
			span: <span className="woocommerce-product-form__optional-input" />,
		},
	} );

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
						How to price your product: expert tips
					</Link>
				</>
			}
		>
			<div className="woocommerce-product-form__custom-label-input">
				<InputControl
					label={ __( 'List price', 'woocommerce' ) }
					placeholder={ __( '10.59', 'woocommerce' ) }
					{ ...getInputControlProps( {
						...getInputProps( 'regular_price' ),
						context,
					} ) }
					onChange={ ( value: string ) =>
						priceValidation( value, 'regular_price' )
					}
				/>
				{ ! isTaxSettingsResolving && (
					<span className="woocommerce-product-form__secondary-text">
						{ taxSettingsElement }
					</span>
				) }
			</div>

			<div className="woocommerce-product-form__custom-label-input">
				<label htmlFor="sale_price">{ salePriceTitle }</label>
				<InputControl
					hideLabelFromVision={ true }
					id="sale_price"
					placeholder={ __( '8.59', 'woocommerce' ) }
					{ ...getInputControlProps( {
						...getInputProps( 'sale_price' ),
						context,
					} ) }
					onChange={ ( value: string ) =>
						priceValidation( value, 'sale_price' )
					}
				/>
			</div>
		</ProductSectionLayout>
	);
};
