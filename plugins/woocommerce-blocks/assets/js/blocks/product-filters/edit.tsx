/**
 * External dependencies
 */
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import { BlockEditProps } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import type { ProductFiltersBlockAttributes } from './types';

export const Edit = ( {}: BlockEditProps< ProductFiltersBlockAttributes > ) => {
	const blockProps = useBlockProps();

	return <div { ...blockProps }>Product Filters</div>;
};

export const Save = () => {
	const blockProps = useBlockProps.save();
	const innerBlocksProps = useInnerBlocksProps.save( blockProps );
	return <div { ...innerBlocksProps } />;
};
