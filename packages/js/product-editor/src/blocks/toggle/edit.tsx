/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { BlockEditProps } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { useEntityProp } from '@wordpress/core-data';
import { ToggleControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { ToggleBlockAttributes } from './types';

export function Edit( {
	attributes,
}: BlockEditProps< ToggleBlockAttributes > ) {
	const blockProps = useBlockProps();
	const { label, property } = attributes;
	const [ value, setValue ] = useEntityProp< boolean >(
		'postType',
		'product',
		property
	);

	return (
		<div { ...blockProps }>
			<ToggleControl
				label={ label }
				checked={ value }
				onChange={ setValue }
			/>
		</div>
	);
}
