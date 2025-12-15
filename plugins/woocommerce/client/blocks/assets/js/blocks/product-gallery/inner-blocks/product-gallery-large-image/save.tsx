/**
 * External dependencies
 */
import {
	useBlockProps,
	// @ts-expect-error no exported member.
	useInnerBlocksProps,
} from '@wordpress/block-editor';

export const Save = () => {
	const blockProps = useBlockProps.save( {
		className: 'wc-block-product-gallery-large-image__inner-blocks',
	} );
	const innerBlocksProps = useInnerBlocksProps.save( blockProps );
	return <div { ...innerBlocksProps } />;
};
