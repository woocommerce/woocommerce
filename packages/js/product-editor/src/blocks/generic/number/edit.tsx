/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { useWooBlockProps } from '@woocommerce/block-templates';
import { Product } from '@woocommerce/data';
import { __, sprintf } from '@wordpress/i18n';
import classNames from 'classnames';
import { useInstanceId } from '@wordpress/compose';
import {
	BaseControl,
	// @ts-expect-error `__experimentalInputControl` does exist.
	__experimentalInputControl as InputControl,
} from '@wordpress/components';
/**
 * Internal dependencies
 */
import { ProductEditorBlockEditProps } from '../../../types';
import useProductEntityProp from '../../../hooks/use-product-entity-prop';
import { useNumberInputProps } from '../../../hooks/use-number-input-props';
import { NumberBlockAttributes } from './types';
import { useValidation } from '../../../contexts/validation-context';

export function Edit( {
	attributes,
	context: { postType },
}: ProductEditorBlockEditProps< NumberBlockAttributes > ) {
	const blockProps = useWooBlockProps( attributes );
	const { label, property, suffix, placeholder, help, min, max } = attributes;
	const [ value, setValue ] = useProductEntityProp( property, {
		postType,
		fallbackValue: '',
	} );

	const inputProps = useNumberInputProps( {
		value: value || '',
		onChange: setValue,
	} );

	const id = useInstanceId( BaseControl, 'product_number_field' ) as string;

	const { error, validate } = useValidation< Product >(
		property,
		async function validator() {
			if (
				typeof min === 'number' &&
				value &&
				parseFloat( value ) < min
			) {
				return sprintf(
					__(
						// translators: %d is the minimum value of the number input.
						'Value must be greater than or equal to %d',
						'woocommerce'
					),
					min
				);
			}
			if (
				typeof max === 'number' &&
				value &&
				parseFloat( value ) > max
			) {
				return sprintf(
					__(
						// translators: %d is the maximum value of the number input.
						'Value must be less than or equal to %d',
						'woocommerce'
					),
					max
				);
			}
		},
		[ value ]
	);

	return (
		<div { ...blockProps }>
			<BaseControl
				className={ classNames( {
					'has-error': error,
				} ) }
				id={ id }
				label={ label }
				help={ error || help }
			>
				<InputControl
					{ ...inputProps }
					id={ id }
					suffix={ suffix }
					placeholder={ placeholder }
					onBlur={ validate }
				/>
			</BaseControl>
		</div>
	);
}
