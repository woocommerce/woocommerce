/**
 * External dependencies
 */
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';

const Save = (): JSX.Element => {
	const blockProps = useBlockProps.save( {
		className: 'wc-block-product-image-with-color-swatches',
	} );
	const innerBlocksProps = useInnerBlocksProps.save( blockProps );

	return <div { ...innerBlocksProps } />;
};

export default Save;
