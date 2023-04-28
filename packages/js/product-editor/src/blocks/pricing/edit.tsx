/**
 * External dependencies
 */
import { Link } from '@woocommerce/components';
import { CurrencyContext } from '@woocommerce/currency';
import { getNewPath } from '@woocommerce/navigation';
import { recordEvent } from '@woocommerce/tracks';
import { useBlockProps } from '@wordpress/block-editor';
import { BlockEditProps } from '@wordpress/blocks';
import { useInstanceId } from '@wordpress/compose';
import { useEntityProp } from '@wordpress/core-data';
import {
	createElement,
	useContext,
	createInterpolateElement,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	BaseControl,
	// @ts-expect-error `__experimentalInputControl` does exist.
	__experimentalInputControl as InputControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { useCurrencyInputProps } from '../../hooks/use-currency-input-props';
import { formatCurrencyDisplayValue } from '../../utils';
import { PricingBlockAttributes } from './types';

export function Edit( {
	attributes,
}: BlockEditProps< PricingBlockAttributes > ) {
	const blockProps = useBlockProps();
	const { name, label, help } = attributes;
	const [ price, setPrice ] = useEntityProp< string >(
		'postType',
		'product',
		name
	);
	const context = useContext( CurrencyContext );
	const { getCurrencyConfig, formatAmount } = context;
	const currencyConfig = getCurrencyConfig();
	const inputProps = useCurrencyInputProps( {
		value: price,
		setValue: setPrice,
	} );

	const interpolatedHelp = help
		? createInterpolateElement( help, {
				PricingTab: (
					<Link
						href={ getNewPath( { tab: 'pricing' } ) }
						onClick={ () => {
							recordEvent( 'product_pricing_help_click' );
						} }
					/>
				),
		  } )
		: null;

	const priceId = useInstanceId(
		BaseControl,
		'wp-block-woocommerce-product-pricing-field'
	) as string;

	return (
		<div { ...blockProps }>
			<BaseControl id={ priceId } help={ interpolatedHelp }>
				<InputControl
					{ ...inputProps }
					id={ priceId }
					name={ name }
					onChange={ setPrice }
					label={ label || __( 'Price', 'woocommerce' ) }
					value={ formatCurrencyDisplayValue(
						String( price ),
						currencyConfig,
						formatAmount
					) }
				/>
			</BaseControl>
		</div>
	);
}
