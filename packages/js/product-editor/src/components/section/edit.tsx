/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';
import type { BlockAttributes } from '@wordpress/blocks';

export function Edit( { attributes }: { attributes: BlockAttributes } ) {
	const blockProps = useBlockProps();
	const { description, title } = attributes;

	return (
		<div { ...blockProps }>
			<h2 className="wp-block-woocommerce-product-section__title">
				{ title }
			</h2>
			<p className="wp-block-woocommerce-product-section__description">
				{ description }
			</p>
			<InnerBlocks templateLock="all" />
		</div>
	);
}
