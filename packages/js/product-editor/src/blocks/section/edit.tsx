/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import type { BlockEditProps } from '@wordpress/blocks';
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { SectionBlockAttributes } from './types';
import { sanitizeHTML } from '../../utils/sanitize-html';

export function Edit( {
	attributes,
}: BlockEditProps< SectionBlockAttributes > ) {
	const blockProps = useBlockProps();
	const { description, title } = attributes;

	return (
		<div { ...blockProps }>
			<h2 className="wp-block-woocommerce-product-section__title">
				<span>{ title }</span>
			</h2>
			{ description && (
				<p
					className="wp-block-woocommerce-product-section__description"
					dangerouslySetInnerHTML={ sanitizeHTML( description ) }
				/>
			) }
			<InnerBlocks templateLock="all" />
		</div>
	);
}
