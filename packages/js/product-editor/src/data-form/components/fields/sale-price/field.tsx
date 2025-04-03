/**
 * External dependencies
 */
import classNames from 'classnames';
import { Product } from '@woocommerce/data';
import { useInstanceId } from '@wordpress/compose';
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { DataFormControlProps } from '@wordpress/dataviews';
import {
	BaseControl,
	__experimentalInputControl as InputControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { useValidation } from '../../../../contexts/validation-context';
import { useCurrencyInputProps } from '../../../../hooks/use-currency-input-props';

export function SalePriceBlockEdit( {
	data,
	onChange,
}: DataFormControlProps< Product > ) {
	const salePrice = data.sale_price;
	const regularPrice = data.regular_price;
	const label = __( 'Sale Price', 'woocommerce' );

	// const { label, help, tooltip, disabled } = attributes;
	// const [ regularPrice ] = useEntityProp< string >(
	// 	'postType',
	// 	context.postType || 'product',
	// 	'regular_price'
	// );
	// const [ salePrice, setSalePrice ] = useEntityProp< string >(
	// 	'postType',
	// 	context.postType || 'product',
	// 	'sale_price'
	// );

	const inputProps = useCurrencyInputProps( {
		value: salePrice,
		onChange: ( nextValue ) => {
			onChange( {
				sale_price: nextValue ?? '',
			} );
		},
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
		`sale-price`,
		async function salePriceValidator() {
			if ( salePrice ) {
				if ( Number.parseFloat( salePrice ) < 0 ) {
					return {
						message: __(
							'Sale price must be greater than or equals to zero.',
							'woocommerce'
						),
					};
				}
				const listPrice = Number.parseFloat( regularPrice );
				if (
					! listPrice ||
					listPrice <= Number.parseFloat( salePrice )
				) {
					return {
						message: __(
							'Sale price must be lower than the regular price.',
							'woocommerce'
						),
					};
				}
			}
		},
		[ regularPrice, salePrice ]
	);

	return (
		<div>
			<BaseControl
				id={ salePriceId }
				help={ salePriceValidationError ?? '' }
				className={ classNames( {
					'has-error': salePriceValidationError,
				} ) }
				label={ label }
			>
				<InputControl
					{ ...inputProps }
					id={ salePriceId }
					name={ 'sale_price' }
					inputMode="decimal"
					ref={ salePriceRef }
					// disabled={ disabled }
					onBlur={ () => validateSalePrice() }
				/>
			</BaseControl>
		</div>
	);
}
