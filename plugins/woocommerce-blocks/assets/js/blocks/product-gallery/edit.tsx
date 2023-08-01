/**
 * External dependencies
 */
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';

export const Edit = (): JSX.Element => {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<InnerBlocks
				allowedBlocks={ [ 'woocommerce/product-gallery-large-image' ] }
				templateLock={ false }
			/>
		</div>
	);
};

export const Save = (): JSX.Element => {
	return (
		<div { ...useBlockProps.save() }>
			<InnerBlocks.Content />
		</div>
	);
};
