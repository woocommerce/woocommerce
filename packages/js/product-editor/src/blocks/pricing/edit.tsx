/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	createElement,
	useContext,
	createInterpolateElement,
} from '@wordpress/element';
import { Link } from '@woocommerce/components';
import { useBlockProps } from '@wordpress/block-editor';
import { useEntityProp } from '@wordpress/core-data';
import { BlockAttributes } from '@wordpress/blocks';
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

export function Edit( { attributes }: { attributes: BlockAttributes } ) {
	const blockProps = useBlockProps();
	const { name, label, showPricingSection = false } = attributes;
	const [ regularPrice, setRegularPrice ] = useEntityProp< string >(
		'postType',
		'product',
		name
	);
	const context = useContext( CurrencyContext );
	const { getCurrencyConfig, formatAmount } = context;
	const currencyConfig = getCurrencyConfig();
	const inputProps = useCurrencyInputProps( {
		value: regularPrice,
		setValue: setRegularPrice,
	} );

	const taxSettingsElement = showPricingSection
		? createInterpolateElement(
				__(
					'Manage more settings in <link>Pricing.</link>',
					'woocommerce'
				),
				{
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
						></Link>
					),
				}
		  )
		: null;

	return (
		<div { ...blockProps }>
			<BaseControl
				id={ 'product_pricing_' + name }
				help={ taxSettingsElement ? taxSettingsElement : '' }
			>
				<InputControl
					name={ name }
					onChange={ setRegularPrice }
					label={ label || __( 'Price', 'woocommerce' ) }
					value={ formatCurrencyDisplayValue(
						String( regularPrice ),
						currencyConfig,
						formatAmount
					) }
					{ ...inputProps }
				/>
			</BaseControl>
		</div>
	);
}
