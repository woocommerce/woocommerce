/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { useWooBlockProps } from '@woocommerce/block-templates';

/**
 * Internal dependencies
 */
import { BlockFill } from '../../../components/block-slot-fill';
import { ProductEditorBlockEditProps } from '../../../types';
import { SectionDescriptionBlockAttributes } from './types';

export function SectionDescriptionBlockEdit( {
	attributes,
	clientId,
}: ProductEditorBlockEditProps< SectionDescriptionBlockAttributes > ) {
	const { content } = attributes;
	const blockProps = useWooBlockProps( attributes );

	return (
		<BlockFill
			{ ...blockProps }
			name="section-description"
			clientId={ clientId }
			slotContainerBlockName="woocommerce/product-section"
		>
			<div>{ content }</div>
		</BlockFill>
	);
}
