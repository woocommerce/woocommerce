/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import type { BlockEditProps } from '@wordpress/blocks';
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';
import { sanitize } from 'dompurify';

const ALLOWED_TAGS = [ 'a', 'b', 'em', 'i', 'strong', 'p', 'br' ];
const ALLOWED_ATTR = [ 'target', 'href', 'rel', 'name', 'download' ];

function sanitizeHTML( html: string ) {
	return {
		__html: sanitize( html, { ALLOWED_TAGS, ALLOWED_ATTR } ),
	};
}

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
			<p
				className="wp-block-woocommerce-product-section__description"
				dangerouslySetInnerHTML={ sanitizeHTML( description ) }
			/>
			<InnerBlocks templateLock="all" />
		</div>
	);
}
