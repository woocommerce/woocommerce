/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createElement, useContext, Fragment } from '@wordpress/element';
import interpolateComponents from '@automattic/interpolate-components';
import { Link } from '@woocommerce/components';
import { useBlockProps } from '@wordpress/block-editor';
import { useEntityProp } from '@wordpress/core-data';
import { CurrencyContext } from '@woocommerce/currency';
import { getSetting } from '@woocommerce/settings';
import { recordEvent } from '@woocommerce/tracks';
import { useSelect } from '@wordpress/data';
import { SETTINGS_STORE_NAME, WCDataSelector } from '@woocommerce/data';
import {
	BaseControl,
	// @ts-expect-error `__experimentalInputControl` does exist.
	__experimentalInputControl as InputControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { formatCurrencyDisplayValue } from '../../utils';

export function Edit() {
	const blockProps = useBlockProps();
	const [ regularPrice, setRegularPrice ] = useEntityProp(
		'postType',
		'product',
		'regular_price'
	);
	const context = useContext( CurrencyContext );
	const { getCurrencyConfig, formatAmount } = context;
	const currencyConfig = getCurrencyConfig();

	const { isResolving: isTaxSettingsResolving, taxSettings } = useSelect(
		( select ) => {
			const { getSettings, hasFinishedResolution } = (
				select as WCDataSelector
			 )( SETTINGS_STORE_NAME );
			return {
				isResolving: ! hasFinishedResolution( 'getSettings', [
					'tax',
				] ),
				taxSettings: getSettings( 'tax' ).tax || {},
				taxesEnabled:
					getSettings( 'general' )?.general
						?.woocommerce_calc_taxes === 'yes',
			};
		},
		[]
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
					href={ `${ getSetting(
						'adminUrl'
					) }admin.php?page=wc-settings&tab=tax` }
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
			<BaseControl id="product_pricing_regular_price" help={ '' }>
				<InputControl
					name="regular_price"
					onChange={ setRegularPrice }
					label={ __( 'List price', 'woocommerce' ) }
					value={ formatCurrencyDisplayValue(
						String( regularPrice ),
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
}
