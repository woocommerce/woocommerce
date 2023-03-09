/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createElement, useContext, Fragment } from '@wordpress/element';
import interpolateComponents from '@automattic/interpolate-components';
import { Link } from '@woocommerce/components';
import { useBlockProps } from '@wordpress/block-editor';
import {
	// @ts-expect-error missing prop.
	useEntityProp,
} from '@wordpress/core-data';
import { CurrencyContext } from '@woocommerce/currency';
import { getSetting } from '@woocommerce/settings';
import { recordEvent } from '@woocommerce/tracks';
import {
	BaseControl,
	// @ts-expect-error `__experimentalInputControl` does exist.
	__experimentalInputControl as InputControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { formatCurrencyDisplayValue } from '../../utils';
import { useCurrencyInputProps } from '../../hooks/use-currency-input-props';

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
	const inputProps = useCurrencyInputProps( {
		value: regularPrice,
		setValue: setRegularPrice,
	} );

	const taxSettingsElement = interpolateComponents( {
		mixedString: 'Manage more settings in {{link}}Pricing.{{/link}}',
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
		},
	} );

	return (
		<div { ...blockProps }>
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
					{ ...inputProps }
				/>
			</BaseControl>
			<span className="woocommerce-product-form__secondary-text">
				{ taxSettingsElement }
			</span>
		</div>
	);
}
