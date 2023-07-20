/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { BlockEditProps } from '@wordpress/blocks';
import { useInstanceId } from '@wordpress/compose';
import { useEntityProp } from '@wordpress/core-data';
import { createElement } from '@wordpress/element';
import { BaseControl, SelectControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { PricingBlockAttributes } from './types';

export function Edit( {
	attributes,
}: BlockEditProps< PricingBlockAttributes > ) {
	const blockProps = useBlockProps();
	const { name, label, help, options } = attributes;
	const [ value, setValue ] = useEntityProp< string >(
		'postType',
		'product',
		name
	);

	const selectId = useInstanceId(
		BaseControl,
		'wp-block-woocommerce-product-select-field'
	) as string;

	return (
		<div { ...blockProps }>
			<BaseControl id={ selectId } help={ help }>
				<SelectControl
					id={ selectId }
					name={ name }
					label={ label }
					value={ value }
					onChange={ setValue }
					options={ options }
				/>
			</BaseControl>
		</div>
	);
}
