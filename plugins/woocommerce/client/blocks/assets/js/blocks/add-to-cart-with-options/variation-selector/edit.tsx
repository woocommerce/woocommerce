/**
 * External dependencies
 */
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import { BlockEditProps } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { ATTRIBUTE_ITEM_TEMPLATE } from './attribute/constants';

interface Attributes {
	className?: string;
}

export default function AddToCartWithOptionsVariationSelectorEdit(
	props: BlockEditProps< Attributes >
) {
	const { className } = props.attributes;

	const blockProps = useBlockProps( {
		className,
	} );
	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		template: ATTRIBUTE_ITEM_TEMPLATE,
		templateLock: 'all',
	} );

	return <div { ...innerBlocksProps } />;
}
