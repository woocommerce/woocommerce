/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';

export function Edit( { attributes } ) {
	const blockProps = useBlockProps();
	const { description, title } = attributes;

	return (
		<div { ...blockProps }>
			<h2>{ title }</h2>
			<p>{ description }</p>
			<InnerBlocks templateLock="all" />
		</div>
	);
}
