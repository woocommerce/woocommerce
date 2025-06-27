/**
 * External dependencies
 */
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';

export default function ProductItemTemplateSave() {
	const blockProps = useBlockProps.save();
	const innerBlocksProps = useInnerBlocksProps.save( {
		...blockProps,
		role: 'listitem',
	} );
	return <div { ...innerBlocksProps } />;
}
