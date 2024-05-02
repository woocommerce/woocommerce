/**
 * External dependencies
 */
import classNames from 'classnames';
import { useWooBlockProps } from '@woocommerce/block-templates';
import { useInstanceId } from '@wordpress/compose';
import { useEntityProp } from '@wordpress/core-data';
import { createElement } from '@wordpress/element';
import {
	BaseControl,
	// @ts-expect-error `__experimentalInputControl` does exist.
	__experimentalInputControl as InputControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { Label } from '../../../components/label/label';
import { useCurrencyInputProps } from '../../../hooks/use-currency-input-props';
import type { ProductEditorBlockEditProps } from '../../../types';
import type { SalePriceBlockAttributes } from './types';

export function Edit( {
	attributes,
	context,
}: ProductEditorBlockEditProps< SalePriceBlockAttributes > ) {
	const blockProps = useWooBlockProps( attributes );
	const { postType, validationErrors } = context;
	const error = validationErrors.regular_price;
	const { label, tooltip, disabled } = attributes;
	const [ regularPrice, setRegularPrice ] = useEntityProp< string >(
		'postType',
		postType || 'product',
		'regular_price'
	);

	const inputProps = useCurrencyInputProps( {
		value: regularPrice,
		onChange: setRegularPrice,
	} );

	const regularPriceId = useInstanceId(
		BaseControl,
		'wp-block-woocommerce-product-regular-price-field'
	) as string;

	return (
		<div { ...blockProps }>
			<BaseControl
				id={ regularPriceId }
				help={ !! error && error.message }
				className={ classNames( {
					'has-error': !! error,
				} ) }
			>
				<InputControl
					{ ...inputProps }
					id={ regularPriceId }
					name={ 'regular_price' }
					label={
						tooltip ? (
							<Label label={ label } tooltip={ tooltip } />
						) : (
							label
						)
					}
					disabled={ disabled }
				/>
			</BaseControl>
		</div>
	);
}
