/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
// @ts-ignore
import { useInnerBlocksProps, useBlockProps } from '@wordpress/block-editor';

export default function save() {
	const blockProps = useBlockProps.save();
	const innerBlocksProps = useInnerBlocksProps.save();

	return (
		<div { ...blockProps }>
			<div { ...innerBlocksProps } />
		</div>
	);
}
