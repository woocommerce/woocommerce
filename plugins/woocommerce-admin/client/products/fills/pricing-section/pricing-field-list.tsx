/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useFormContext2, Link } from '@woocommerce/components';
import { useController } from 'react-hook-form';
import { recordEvent } from '@woocommerce/tracks';
import { useContext } from '@wordpress/element';
import { Product, SETTINGS_STORE_NAME } from '@woocommerce/data';
import { useSelect } from '@wordpress/data';
import interpolateComponents from '@automattic/interpolate-components';
import classNames from 'classnames';
import {
	BaseControl,
	// @ts-expect-error `__experimentalInputControl` does exist.
	__experimentalInputControl as InputControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { CurrencyInputProps } from './pricing-section-fills';
import { formatCurrencyDisplayValue } from '../../sections/utils';
import { CurrencyContext } from '../../../lib/currency-context';
import { ADMIN_URL } from '~/utils/admin-settings';
import { getErrorMessageProps } from '~/products/utils/get-error-message-props';

type PricingListFieldProps = {
	currencyInputProps: CurrencyInputProps;
};

export const PricingListField: React.FC< PricingListFieldProps > = ( {
	currencyInputProps,
} ) => {
	const { control } = useFormContext2< Product >();
	const { field, fieldState } = useController( {
		name: 'regular_price',
		control,
		rules: {
			pattern: {
				value: /^[0-9.,]+$/,
				message: __(
					'Please enter a price with one monetary decimal point without thousand separators and currency symbols.',
					'woocommerce'
				),
			},
		},
	} );
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

	const errorMessageProps = getErrorMessageProps( fieldState );

	return (
		<>
			<BaseControl
				id="product_pricing_regular_price"
				help={ errorMessageProps.help ?? '' }
			>
				<InputControl
					{ ...field }
					{ ...currencyInputProps }
					{ ...errorMessageProps }
					className={ classNames(
						currencyInputProps.className,
						errorMessageProps.className
					) }
					name="regular_price"
					label={ __( 'List price', 'woocommerce' ) }
					value={ formatCurrencyDisplayValue(
						String( field?.value ),
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
