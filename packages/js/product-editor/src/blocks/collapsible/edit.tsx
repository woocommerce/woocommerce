/**
 * External dependencies
 */
import { CollapsibleContent } from '@woocommerce/components';
import type { BlockAttributes } from '@wordpress/blocks';
import { createElement } from '@wordpress/element';
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';

export function Edit( { attributes }: { attributes: BlockAttributes } ) {
	const blockProps = useBlockProps();
	const { toggleText, initialCollapsed, persistRender = true } = attributes;

	return (
		<div { ...blockProps }>
			<CollapsibleContent
				toggleText={ toggleText }
				initialCollapsed={ initialCollapsed }
				persistRender={ persistRender }
			>
				<InnerBlocks templateLock="all" />
			</CollapsibleContent>
		</div>
	);
}
