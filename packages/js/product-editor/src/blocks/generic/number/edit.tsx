/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { useWooBlockProps } from '@woocommerce/block-templates';
import {
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

export function Edit( {
	attributes,
	context: { postType },
}: ProductEditorBlockEditProps< NumberBlockAttributes > ) {
	const blockProps = useWooBlockProps( attributes );
	const { label, property, suffix, placeholder, help } = attributes;
	const [ value, setValue ] = useProductEntityProp( property, {
		postType,
		fallbackValue: '',
	} );

	const inputProps = useNumberInputProps( {
		value: value || '',
		onChange: setValue,
	} );

	return (
		<div { ...blockProps }>
			<InputControl
				{ ...inputProps }
				__next36pxDefaultSize
				label={ label }
				suffix={ suffix }
				help={ help }
				placeholder={ placeholder }
			/>
		</div>
	);
}
