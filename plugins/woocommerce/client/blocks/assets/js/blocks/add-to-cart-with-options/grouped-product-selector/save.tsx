/**
 * External dependencies
 */
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';

export default function AddToCartWithOptionsGroupedProductSelectorSave() {
	const blockProps = useBlockProps.save();
	const innerBlocksProps = useInnerBlocksProps.save( {
		...blockProps,
		role: 'list',
	} );
	return <div { ...innerBlocksProps } />;
}
