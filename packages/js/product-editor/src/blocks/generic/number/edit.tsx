/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { useWooBlockProps } from '@woocommerce/block-templates';
import { Product } from '@woocommerce/data';
import { __, sprintf } from '@wordpress/i18n';
/**
 * Internal dependencies
 */
import { ProductEditorBlockEditProps } from '../../../types';
import useProductEntityProp from '../../../hooks/use-product-entity-prop';
import { NumberBlockAttributes } from './types';
import { useValidation } from '../../../contexts/validation-context';
import { NumberControl } from '../../../components/number-control';

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
			<NumberControl
				label={ label }
				onChange={ setValue }
				value={ value || '' }
				help={ help }
				suffix={ suffix }
				placeholder={ placeholder }
				error={ error }
				onBlur={ validate }
			/>
		</div>
	);
}
