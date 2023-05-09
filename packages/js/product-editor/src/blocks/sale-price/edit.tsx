/**
 * External dependencies
 */
import classNames from 'classnames';
import { CurrencyContext } from '@woocommerce/currency';
import { useBlockProps } from '@wordpress/block-editor';
import { BlockEditProps } from '@wordpress/blocks';
import { useInstanceId } from '@wordpress/compose';
import { useEntityProp } from '@wordpress/core-data';
import { createElement, useContext } from '@wordpress/element';
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
	const [ regularPrice ] = useEntityProp< string >(
		'postType',
		'product',
		'regular_price'
	);
	const [ salePrice, setSalePrice ] = useEntityProp< string >(
		'postType',
		'product',
		'sale_price'
	);
	const context = useContext( CurrencyContext );
	const { getCurrencyConfig, formatAmount } = context;
	const currencyConfig = getCurrencyConfig();
	const inputProps = useCurrencyInputProps( {
		value: salePrice,
		setValue: setSalePrice,
	} );

	const salePriceId = useInstanceId(
		BaseControl,
		'wp-block-woocommerce-product-sale-price-field'
	) as string;

	const salePriceValidationError = useValidation(
		'product/sale_price',
		function salePriceValidator() {
			if ( salePrice ) {
				if ( Number.parseFloat( salePrice ) < 0 ) {
					return __(
						'Sale price must be greater than or equals to zero.',
						'woocommerce'
					);
				}
				const listPrice = Number.parseFloat( regularPrice );
				if (
					! listPrice ||
					listPrice <= Number.parseFloat( salePrice )
				) {
					return __(
						'Sale price must be lower than the list price.',
						'woocommerce'
					);
				}
			}
		}
	);

	return (
		<div { ...blockProps }>
			<BaseControl
				id={ salePriceId }
				help={
					salePriceValidationError ? salePriceValidationError : help
				}
				className={ classNames( {
					'has-error': salePriceValidationError,
				} ) }
			>
				<InputControl
					{ ...inputProps }
					id={ salePriceId }
					name={ 'sale_price' }
					onChange={ setSalePrice }
					label={ label }
					value={ formatCurrencyDisplayValue(
						String( salePrice ),
						currencyConfig,
						formatAmount
					) }
				/>
			</BaseControl>
		</div>
	);
}
