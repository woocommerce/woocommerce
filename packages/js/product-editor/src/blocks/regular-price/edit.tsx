/**
 * External dependencies
 */
import classNames from 'classnames';
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
import { SalePriceBlockAttributes } from './types';
import { useValidation } from '../../hooks/use-validation';

export function Edit( {
	attributes,
}: BlockEditProps< SalePriceBlockAttributes > ) {
	const blockProps = useBlockProps();
	const { label, help } = attributes;
	const [ regularPrice, setRegularPrice ] = useEntityProp< string >(
		'postType',
		'product',
		'regular_price'
	);
	const [ salePrice ] = useEntityProp< string >(
		'postType',
		'product',
		'sale_price'
	);
	const context = useContext( CurrencyContext );
	const { getCurrencyConfig, formatAmount } = context;
	const currencyConfig = getCurrencyConfig();
	const inputProps = useCurrencyInputProps( {
		value: regularPrice,
		setValue: setRegularPrice,
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

	const regularPriceId = useInstanceId(
		BaseControl,
		'wp-block-woocommerce-product-regular-price-field'
	) as string;

	const regularPriceValidationError = useValidation(
		'product/regular_price',
		function regularPriceValidator() {
			const listPrice = Number.parseFloat( regularPrice );
			if ( listPrice ) {
				if ( listPrice < 0 ) {
					return __(
						'List price must be greater than or equals to zero.',
						'woocommerce'
					);
				}
				if (
					salePrice &&
					listPrice <= Number.parseFloat( salePrice )
				) {
					return __(
						'List price must be greater than the sale price.',
						'woocommerce'
					);
				}
			}
		}
	);

	return (
		<div { ...blockProps }>
			<BaseControl
				id={ regularPriceId }
				help={
					regularPriceValidationError
						? regularPriceValidationError
						: interpolatedHelp
				}
				className={ classNames( {
					'has-error': regularPriceValidationError,
				} ) }
			>
				<InputControl
					{ ...inputProps }
					id={ regularPriceId }
					name={ 'regular_price' }
					label={ label }
					value={ formatCurrencyDisplayValue(
						String( regularPrice ),
						currencyConfig,
						formatAmount
					) }
					onChange={ setRegularPrice }
				/>
			</BaseControl>
		</div>
	);
}
