/**
 * External dependencies
 */
import classNames from 'classnames';
import { useWooBlockProps } from '@woocommerce/block-templates';
import { Product } from '@woocommerce/data';
import { useInstanceId } from '@wordpress/compose';
import { useEntityProp } from '@wordpress/core-data';
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	BaseControl,
	// @ts-expect-error `__experimentalInputControl` does exist.
	__experimentalInputControl as InputControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { useValidation } from '../../../contexts/validation-context';
import { useCurrencyInputProps } from '../../../hooks/use-currency-input-props';
import { SalePriceBlockAttributes } from './types';
import { ProductEditorBlockEditProps } from '../../../types';
import { Label } from '../../../components/label/label';

export function Edit( {
	attributes,
	clientId,
	context,
}: ProductEditorBlockEditProps< SalePriceBlockAttributes > ) {
	const blockProps = useWooBlockProps( attributes );
	const { label, help, tooltip } = attributes;
	const [ regularPrice ] = useEntityProp< string >(
		'postType',
		context.postType || 'product',
		'regular_price'
	);
	const [ salePrice, setSalePrice ] = useEntityProp< string >(
		'postType',
		context.postType || 'product',
		'sale_price'
	);
	const inputProps = useCurrencyInputProps( {
		value: salePrice,
		onChange: setSalePrice,
	} );

	const salePriceId = useInstanceId(
		BaseControl,
		'wp-block-woocommerce-product-sale-price-field'
	) as string;

	const {
		ref: salePriceRef,
		error: salePriceValidationError,
		validate: validateSalePrice,
	} = useValidation< Product >(
		`sale-price-${ clientId }`,
		async function salePriceValidator() {
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
		},
		[ regularPrice, salePrice ]
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
					ref={ salePriceRef }
					label={
						tooltip ? (
							<Label label={ label } tooltip={ tooltip } />
						) : (
							label
						)
					}
					onBlur={ validateSalePrice }
				/>
			</BaseControl>
		</div>
	);
}
