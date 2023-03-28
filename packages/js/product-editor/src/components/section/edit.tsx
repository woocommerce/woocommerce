/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';
import type { BlockEditProps } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { SectionBlockAttributes } from './types';
import { BlockIcon } from '../block-icon';

export function Edit( {
	attributes,
	clientId,
}: BlockEditProps< SectionBlockAttributes > ) {
	const blockProps = useBlockProps();
	const { description, title } = attributes;

	return (
		<div { ...blockProps }>
			<h2 className="wp-block-woocommerce-product-section__title">
				<BlockIcon clientId={ clientId } />
				<span>{ title }</span>
			</h2>
			<p className="wp-block-woocommerce-product-section__description">
				{ description }
			</p>
			<InnerBlocks templateLock="all" />
		</div>
	);
}
