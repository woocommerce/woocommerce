/**
 * External dependencies
 */
import { SelectControl } from '@wordpress/components';
import { useWooBlockProps } from '@woocommerce/block-templates';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useProductEntityProp from '../../../hooks/use-product-entity-prop';
import { ProductEditorBlockEditProps } from '../../../types';
import { SelectBlockAttributes } from './types';

export function Edit( {
	attributes,
	context: { postType },
}: ProductEditorBlockEditProps< SelectBlockAttributes > ) {
	const blockProps = useWooBlockProps( attributes );

	const { label, property, options } = attributes;

	const [ value, setValue ] = useProductEntityProp< [] >( property, {
		postType,
		fallbackValue: [],
	} );

	return (
		<div { ...blockProps }>
			<SelectControl
				label={ label }
				// @ts-ignore wrong types; false ok
				multiple={ false }
				value={ value }
				options={ options || [ { label: 'Loading...', value: '' } ] }
				disabled={ ! options }
				onChange={ setValue }
			/>
		</div>
	);
}
