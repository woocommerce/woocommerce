/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import type { BlockAttributes } from '@wordpress/blocks';
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

export function Edit( {
	attributes,
}: ProductEditorBlockEditProps< BlockAttributes > ) {
	const blockProps = useWooBlockProps( attributes );
	const { label, property, suffix } = attributes;
	const [ value, setValue ] = useProductEntityProp( property );

	const inputProps = useNumberInputProps( {
		value,
		onChange: setValue,
	} );

	return (
		<div { ...blockProps }>
			<InputControl label={ label } { ...inputProps } suffix={ suffix } />
		</div>
	);
}
