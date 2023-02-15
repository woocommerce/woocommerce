/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import nameBlock from '../name/block';
import summaryBlock from '../summary/block';
import imagesBlock from '../images/block';

const ALLOWED_BLOCKS = [ nameBlock.name, summaryBlock.name ];

export function Edit( { attributes } ) {
	const blockProps = useBlockProps();
	const { title, description } = attributes;

	return (
		<div { ...blockProps }>
			<h2>{ title }</h2>
			<p>{ description }</p>
			<InnerBlocks
				allowedBlocks={ ALLOWED_BLOCKS }
				template={ [
					[ nameBlock.name, {} ],
					[
						summaryBlock.name,
						{
							placeholder: 'Test placeholder',
							content: 'Test content',
						},
					],
					[ imagesBlock.name, {} ],
				] }
				templateLock="all"
			/>
		</div>
	);
}
