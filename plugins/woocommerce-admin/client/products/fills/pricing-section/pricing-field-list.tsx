/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { formatCurrencyDisplayValue } from '@woocommerce/product-editor';
import { useFormContext, Link } from '@woocommerce/components';
import { recordEvent } from '@woocommerce/tracks';
import { useContext } from '@wordpress/element';
import { Product, SETTINGS_STORE_NAME } from '@woocommerce/data';
import { useSelect } from '@wordpress/data';
import interpolateComponents from '@automattic/interpolate-components';
import {
	BaseControl,
	// @ts-expect-error `__experimentalInputControl` does exist.
	__experimentalInputControl as InputControl,
} from '@wordpress/components';
import { CurrencyContext } from '@woocommerce/currency';

/**
 * Internal dependencies
 */
import { CurrencyInputProps } from './pricing-section-fills';
import { ADMIN_URL } from '~/utils/admin-settings';

type PricingListFieldProps = {
	currencyInputProps: CurrencyInputProps;
};

export const PricingListField: React.FC< PricingListFieldProps > = ( {
	currencyInputProps,
} ) => {
	const { getInputProps } = useFormContext< Product >();
	const context = useContext( CurrencyContext );
	const { getCurrencyConfig, formatAmount } = context;
	const currencyConfig = getCurrencyConfig();

	const { isResolving: isTaxSettingsResolving, taxSettings } = useSelect(
		( select ) => {
			const { getSettings, hasFinishedResolution } =
				select( SETTINGS_STORE_NAME );
			return {
				isResolving: ! hasFinishedResolution( 'getSettings', [
					'tax',
				] ),
				taxSettings: getSettings( 'tax' ).tax || {},
				taxesEnabled:
					getSettings( 'general' )?.general
						?.woocommerce_calc_taxes === 'yes',
			};
		}
	);

	const regularPriceProps = getInputProps(
		'regular_price',
		currencyInputProps
	);

	const taxIncludedInPriceText = __(
		'Per your {{link}}store settings{{/link}}, tax is {{strong}}included{{/strong}} in the price.',
		'woocommerce'
	);
	const taxNotIncludedInPriceText = __(
		'Per your {{link}}store settings{{/link}}, tax is {{strong}}not included{{/strong}} in the price.',
		'woocommerce'
	);
	const pricesIncludeTax =
		taxSettings.woocommerce_prices_include_tax === 'yes';

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

	return (
		<>
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
		</>
	);
};
