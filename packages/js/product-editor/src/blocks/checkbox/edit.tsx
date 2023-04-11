/**
 * External dependencies
 */
import { createElement, Fragment } from '@wordpress/element';
import type { BlockAttributes } from '@wordpress/blocks';
import { CheckboxControl } from '@wordpress/components';
import { useBlockProps } from '@wordpress/block-editor';
import { useEntityProp } from '@wordpress/core-data';

/**
 * Internal dependencies
 */

export function Edit( { attributes }: { attributes: BlockAttributes } ) {
	const blockProps = useBlockProps();
	const { property, title, label } = attributes;
	const [ value, setValue ] = useEntityProp< boolean >(
		'postType',
		'product',
		property
	);

	return (
		<div { ...blockProps }>
			<h4> { title } </h4>
			<CheckboxControl
				label={ label }
				checked={ value }
				onChange={ ( selected ) => setValue( selected ) }
			/>
		</div>
	);
}
