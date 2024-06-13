/**
 * External dependencies
 */
import {
	InnerBlocks,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { InnerBlockTemplate } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import './editor.scss';

const TEMPLATE: InnerBlockTemplate[] = [ [ 'woocommerce/product-filters' ] ];

export const Edit = () => {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<InnerBlocks templateLock={ false } template={ TEMPLATE } />
		</div>
	);
};

export const Save = () => {
	const blockProps = useBlockProps.save();
	const innerBlocksProps = useInnerBlocksProps.save( blockProps );
	return <div { ...innerBlocksProps } />;
};
