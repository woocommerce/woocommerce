/**
 * External dependencies
 */
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';

export default function save() {
	const blockProps = useBlockProps.save();
	const innerBlockProps = useInnerBlocksProps.save( blockProps );

	return <nav { ...innerBlockProps } />;
}
