/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import type { BlockAttributes } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { useEntityProp } from '@wordpress/core-data';
import { useInstanceId } from '@wordpress/compose';
import {
	Button,
	BaseControl,
	// @ts-expect-error `__experimentalInputControl` does exist.
	__experimentalInputControl as InputControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */

export function Edit( { attributes }: { attributes: BlockAttributes } ) {
	const blockProps = useBlockProps( {} );
	const { property, label, placeholder } = attributes;
	const [ value, setValue ] = useEntityProp< boolean >(
		'postType',
		'product',
		property
	);

	const nameControlId = useInstanceId( BaseControl, property ) as string;

	return (
		<div { ...blockProps }>
			<BaseControl id={ nameControlId } label={ label }>
				<InputControl
					id={ nameControlId }
					placeholder={ placeholder }
					value={ value }
					onChange={ setValue }
				></InputControl>
			</BaseControl>
		</div>
	);
}
